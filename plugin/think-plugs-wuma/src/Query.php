<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wuma;

use plugin\wuma\service\CodeService;
use think\admin\Controller;
use think\admin\Exception;

/**
 * 物码访问验证入口.
 * @class Query
 */
class Query extends Controller
{
    /**
     * 当前标签编码
     * @var string
     */
    protected $code;

    /**
     * 标签查询验证
     * @param string $mode 匹配模式
     * @param string $code 标签内容
     * @param string $verify 安全验证
     * @throws Exception
     */
    public function index(string $mode, string $code, string $verify)
    {
        $this->code = $code;
        if (strtolower($mode) === 'n') {
            $min = CodeService::num2min($code);
        }
        if (strtolower($mode) === 'c') {
            $min = CodeService::enc2min($code);
        }
        if (isset($min)) {
            if ($verify === CodeService::url2ver($min)) {
                echo '验证成功！后面再显示对应页面';
                dump($mode, $min, $code, $verify, CodeService::min2ver($min));
                dump(CodeService::find($code, is_numeric($code) ? 'number' : 'encode'));
            } else {
                echo '验证失败！';
            }
        } else {
            echo '不支持的模式';
        }
    }
}
