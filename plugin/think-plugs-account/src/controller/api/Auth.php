<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\account\controller\api;

use plugin\account\service\Account;
use think\admin\Controller;
use think\exception\HttpResponseException;

/**
 * 接口授权抽象类
 * @class Auth
 * @package plugin\account\controller\api
 */
abstract class Auth extends Controller
{

    /**
     * 接口类型
     * @var string
     */
    protected $type;

    /**
     * 主账号编号
     * @var integer
     */
    protected $unid;

    /**
     * 子账号编号
     * @var integer
     */
    protected $usid;

    /**
     * 终端账号接口
     * @var \plugin\account\service\contract\AccountInterface
     */
    protected $account;

    /**
     * 控制器初始化
     */
    protected function initialize()
    {
        try {
            // 获取请求令牌内容
            // 优先识别 Bearer Token 机制，再识别 api-token 字段
            $token = $this->request->header('Authorization', '');
            if (!empty($token) && stripos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
            if (empty($token)) {
                $token = $this->request->header('api-token', '');
            }
            if (empty($token)) $this->error('需要登录授权', [], 401);
            // 读取用户账号数据
            $this->account = Account::mk('', $token);
            $login = $this->account->check();
            $this->usid = intval($login['id'] ?? 0);
            $this->unid = intval($login['unid'] ?? 0);
            $this->type = strval($login['type'] ?? '');
            // 临时缓存登录数据
            sysvar('plugin_account_object', $this->account);
            sysvar('plugin_account_user_type', $this->type);
            sysvar('plugin_account_user_usid', $this->usid);
            sysvar('plugin_account_user_unid', $this->unid);
            sysvar('plugin_account_user_code', $this->account->getCode());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage(), [], $exception->getCode());
        }
    }

    /**
     * 检查用户状态
     * @param boolean $isBind
     * @return $this
     */
    protected function checkUserStatus(bool $isBind = true): Auth
    {
        $login = $this->account->get();
        if (empty($login['status'])) {
            $this->error('终端已冻结', $login, 403);
        } elseif ($isBind) {
            if (empty($login['user'])) {
                $this->error('请绑定账号', $login, 402);
            }
            if (empty($login['user']['status'])) {
                $this->error('账号已冻结', $login, 403);
            }
        }
        return $this;
    }
}