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
        $this->assertSame('关联成功!', $response['info'] ?? '');
        $this->assertNotEmpty($response['data']['token'] ?? '');
        $this->assertSame($phone, $response['data']['user']['phone'] ?? '');
        $this->assertTrue($bind->isExists());
        $this->assertGreaterThan(0, intval($bind->getAttr('unid')));
        $this->assertTrue($user->isExists());
        $this->assertSame(md5('Secret@123'), strval($user->getAttr('password')));
        $this->assertSame(md5('Secret@123'), strval($bind->getAttr('password')));
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
}
