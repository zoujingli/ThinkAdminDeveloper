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

namespace think\admin\tests;

use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\wemall\controller\user\Admin as UserAdminController;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class UserAdminPasswordTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/wemall-account.png',
            'userPrefix' => '商城账号',
        ]);
    }

    public function testEditFilterKeepsExistingPasswordWhenMaskIsSubmitted(): void
    {
        $account = $this->createBoundAccountFixture(Account::WAP, ['phone' => '13900139000']);
        $account->pwdModify('Secret@123', false);

        $request = $this->app->request
            ->withGet([])
            ->withPost([
                'unid' => $account->getUnid(),
                'user' => [
                    'phone' => '13900139000',
                    'nickname' => '商城星号用户',
                    'password' => password_mask(),
                ],
            ])
            ->setMethod('POST')
            ->setController('user.admin')
            ->setAction('edit');

        RequestContext::clear();
        $this->app->instance('request', $request);

        $controller = new UserAdminController($this->app);
        $method = new \ReflectionMethod($controller, '_edit_form_filter');
        $method->setAccessible(true);
        $method->invoke($controller, [
            'unid' => $account->getUnid(),
            'user' => [
                'phone' => '13900139000',
                'nickname' => '商城星号用户',
                'password' => password_mask(),
            ],
        ]);

        $bind = PluginAccountBind::mk()->findOrEmpty($account->getUsid());
        $user = PluginAccountUser::mk()->findOrEmpty($account->getUnid());

        $this->assertTrue(password_verify('Secret@123', strval($bind->getAttr('password'))));
        $this->assertTrue(password_verify('Secret@123', strval($user->getAttr('password'))));
        $this->assertSame('商城星号用户', strval($user->getAttr('nickname')));
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
    }
}
