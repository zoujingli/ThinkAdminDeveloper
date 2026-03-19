<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\account\controller\api;

use plugin\account\service\Account;
use plugin\account\service\contract\AccountInterface;
use think\admin\Controller;
use think\exception\HttpResponseException;

/**
 * 接口授权抽象类.
 * @class Auth
 */
abstract class Auth extends Controller
{
    /**
     * 接口类型.
     * @var string
     */
    protected $type;

    /**
     * 主账号编号.
     * @var int
     */
    protected $unid;

    /**
     * 子账号编号.
     * @var int
     */
    protected $usid;

    /**
     * 终端账号接口.
     * @var AccountInterface
     */
    protected $account;

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        try {
            // 统一识别 Authorization，未携带请求头时再读取认证 Cookie。
            $token = Account::requestToken($this->request);
            if (empty($token)) {
                $this->error('需要登录授权', [], 401);
            }
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
