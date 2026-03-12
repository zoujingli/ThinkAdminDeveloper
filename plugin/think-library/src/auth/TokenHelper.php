<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\auth;

use think\admin\extend\codec\CodeToolkit;
use think\admin\Helper;
use think\admin\Library;
use think\exception\HttpResponseException;

/**
 * 表单令牌验证器.
 * @class TokenHelper
 */
class TokenHelper extends Helper
{
    /**
     * 表单令牌缓存前缀.
     */
    private const CACHE_PREFIX = 'think.admin.form.token.';

    /**
     * 初始化验证码器.
     * @return bool|void
     */
    public function init(bool $return = false)
    {
        $this->class->csrf_state = true;
        if (!$this->app->request->isPost()) {
            return true;
        }
        $token = $this->app->request->post('_token_');
        $extra = ['_token_' => $token ?: $this->app->request->header('User-Form-Token')];
        if (static::check(strval($extra['_token_'] ?? ''))) {
            return true;
        }
        if ($return) {
            return false;
        }
        $this->class->error($this->class->csrf_message ?: '表单令牌验证失败！');
    }

    /**
     * 返回视图内容.
     * @param string $tpl 模板名称
     * @param array $vars 模板变量
     * @param null|string $node 授权节点
     */
    public static function fetch(string $tpl = '', array $vars = [], ?string $node = null)
    {
        throw new HttpResponseException(view($tpl, $vars, 200, static function ($html) {
            return preg_replace_callback('/<\/form>/i', static function () {
                return sprintf("<input type='hidden' name='_token_' value='%s'></form>", static::token());
            }, $html);
        }));
    }

    /**
     * 生成一次性表单令牌。
     */
    public static function token(): string
    {
        $token = CodeToolkit::uuid() . md5(uniqid((string)mt_rand(1000, 9999), true));
        Library::$sapp->cache->set(self::CACHE_PREFIX . $token, 1, 1800);
        return $token;
    }

    /**
     * 检查表单令牌.
     */
    private static function check(string $token): bool
    {
        if ($token === '' || $token === '--') {
            return false;
        }
        $cache = Library::$sapp->cache;
        $key = self::CACHE_PREFIX . $token;
        if ($cache->get($key, 0)) {
            $cache->delete($key);
            return true;
        }
        return false;
    }
}
