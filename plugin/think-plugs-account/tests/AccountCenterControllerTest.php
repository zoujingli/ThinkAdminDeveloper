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

use plugin\account\controller\api\auth\Center as AuthCenterController;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\account\service\Message;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;

/**
 * @internal
 * @coversNothing
 */
class AccountCenterControllerTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess();
    }

    public function testBindControllerAssociatesUserAndClearsVerifyCode(): void
    {
        $phone = $this->randomPhone('1360099');
        $account = $this->createAccountFixture(Account::WAP, ['phone' => $phone]);
        $login = $account->get(true);
        $verify = '246810';

        $this->rememberVerifyCode($phone, $verify);
        $response = $this->callCenterController('bind', [
            'phone' => $phone,
            'verify' => $verify,
            'passwd' => 'Secret@123',
        ], strval($login['token'] ?? ''));

        $bind = PluginAccountBind::mk()->where(['phone' => $phone, 'type' => Account::WAP])->findOrEmpty();
        $user = PluginAccountUser::mk()->findOrEmpty(intval($bind->getAttr('unid')));

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('关联成功', $response['info'] ?? '');
        $this->assertNotEmpty($response['data']['token'] ?? '');
        $this->assertSame($phone, $response['data']['user']['phone'] ?? '');
        $this->assertTrue($bind->isExists());
        $this->assertGreaterThan(0, intval($bind->getAttr('unid')));
        $this->assertTrue($user->isExists());
        $this->assertTrue(password_verify('Secret@123', strval($user->getAttr('password'))));
        $this->assertTrue(password_verify('Secret@123', strval($bind->getAttr('password'))));
        $this->assertFalse($this->app->cache->has($this->verifyCacheKey($phone)));
    }

    public function testUnbindControllerReturnsDetachedAccountView(): void
    {
        $phone = $this->randomPhone('1370099');
        $account = $this->createBoundAccountFixture(Account::WAP, ['phone' => $phone]);
        $login = $account->token()->get(true);

        $response = $this->callCenterController('unbind', [], strval($login['token'] ?? ''));
        $bind = PluginAccountBind::mk()->findOrEmpty($account->getUsid());

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('关联成功', $response['info'] ?? '');
        $this->assertSame(0, intval($response['data']['unid'] ?? -1));
        $this->assertArrayNotHasKey('id', $response['data']['user'] ?? []);
        $this->assertSame(0, intval($bind->getAttr('unid')));
    }

    public function testSetControllerKeepsExistingPasswordWhenMaskIsSubmitted(): void
    {
        $phone = $this->randomPhone('1380099');
        $account = $this->createBoundAccountFixture(Account::WAP, ['phone' => $phone]);
        $account->pwdModify('Secret@123', false);
        $login = $account->token()->get(true);

        $response = $this->callCenterController('set', [
            'nickname' => '星号保留昵称',
            'password' => password_mask(),
        ], strval($login['token'] ?? ''));

        $bind = PluginAccountBind::mk()->findOrEmpty($account->getUsid());
        $user = PluginAccountUser::mk()->findOrEmpty($account->getUnid());

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('修改成功', $response['info'] ?? '');
        $this->assertSame('星号保留昵称', $response['data']['user']['nickname'] ?? '');
        $this->assertTrue(password_verify('Secret@123', strval($bind->getAttr('password'))));
        $this->assertTrue(password_verify('Secret@123', strval($user->getAttr('password'))));
    }

    public function testGetControllerReturnsEnglishInfoWhenLangSetIsEnUs(): void
    {
        $phone = $this->randomPhone('1350099');
        $account = $this->createBoundAccountFixture(Account::WAP, ['phone' => $phone]);
        $login = $account->token()->get(true);
        $this->switchAccountLang('en-us');

        $response = $this->callCenterController('get', [], strval($login['token'] ?? ''));

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('Profile loaded successfully', $response['info'] ?? '');
        $this->assertSame($phone, $response['phone'] ?? $response['data']['phone'] ?? '');
    }

    public function testGetControllerReturnsEnglishUnauthorizedInfoWhenTokenMissing(): void
    {
        $this->switchAccountLang('en-us');

        $response = $this->callCenterController('get', [], '');

        $this->assertSame(401, intval($response['code'] ?? 0));
        $this->assertSame('Login authorization required', $response['info'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
    }

    private function callCenterController(string $action, array $post, string $token): array
    {
        $request = $this->app->request
            ->withGet($post)
            ->withPost($post)
            ->withHeader(['authorization' => "Bearer {$token}"])
            ->setController('api.auth.center')
            ->setAction($action);

        RequestContext::clear();
        $this->app->instance('request', $request);

        try {
            $controller = new AuthCenterController($this->app);
            $controller->{$action}();
            self::fail("Expected {$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function rememberVerifyCode(string $phone, string $code, string $scene = Message::tLogin): void
    {
        $this->app->cache->set($this->verifyCacheKey($phone, $scene), [
            'code' => $code,
            'time' => time(),
        ], 600);
    }

    private function verifyCacheKey(string $phone, string $scene = Message::tLogin): string
    {
        return md5(strtolower("sms-{$scene}-{$phone}"));
    }

    private function switchAccountLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        $file = TEST_PROJECT_ROOT . "/plugin/think-plugs-account/src/lang/{$langSet}.php";
        if (is_file($file)) {
            $this->app->lang->load($file, $langSet);
        }
    }
}
