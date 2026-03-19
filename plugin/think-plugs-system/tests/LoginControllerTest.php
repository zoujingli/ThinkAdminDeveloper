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

use plugin\system\controller\Login as LoginController;
use plugin\system\model\SystemConfig;
use plugin\system\model\SystemOplog;
use plugin\system\service\SystemAuthService;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\runtime\RequestTokenService;
use think\admin\service\CacheSession;
use think\admin\service\JwtToken;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class LoginControllerTest extends SqliteIntegrationTestCase
{
    public function testCaptchaReturnsCodeAndUniqidForFreshToken(): void
    {
        $type = 'LoginCaptcha';
        $token = 'login-page-token-1';

        $result = $this->callActionController('captcha', [
            'type' => $type,
            'token' => $token,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('生成验证码成功', $result['info'] ?? '');
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{4}$/', strval($result['data']['code'] ?? ''));
        $this->assertStringStartsWith('captcha', strval($result['data']['uniqid'] ?? ''));
        $this->assertStringStartsWith('data:image/png;base64,', strval($result['data']['image'] ?? ''));
        $this->assertSame([
            'type' => $type,
            'token' => $token,
        ], $this->app->cache->get($this->captchaMapKey(strval($result['data']['uniqid'] ?? '')), []));
    }

    public function testFailedLoginMarksCaptchaAndNextCaptchaHidesCode(): void
    {
        $type = 'LoginCaptcha';
        $token = 'login-page-token-2';

        $captcha = $this->callActionController('captcha', [
            'type' => $type,
            'token' => $token,
        ]);

        $failed = $this->callActionController('index', [
            'username' => 'missing-user',
            'password' => 'wrong-password',
            'verify' => strval($captcha['data']['code'] ?? ''),
            'uniqid' => strval($captcha['data']['uniqid'] ?? ''),
        ]);
        $nextCaptcha = $this->callActionController('captcha', [
            'type' => $type,
            'token' => $token,
        ]);

        $this->assertSame(0, intval($failed['code'] ?? 1));
        $this->assertSame('登录账号或密码错误，请重新输入!', $failed['info'] ?? '');
        $this->assertArrayNotHasKey('code', $nextCaptcha['data'] ?? []);
        $this->assertSame('1', strval($this->app->cache->get($this->captchaFailKey($type, $token), 0)));
    }

    public function testIndexLogsInUserUpdatesStatsAndWritesOplog(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('secret123'),
            'status' => 1,
        ]);
        $captcha = $this->callActionController('captcha', [
            'type' => 'LoginCaptcha',
            'token' => 'login-page-token-3',
        ]);

        $result = $this->callActionController('index', [
            'username' => 'tester',
            'password' => 'secret123',
            'verify' => strval($captcha['data']['code'] ?? ''),
            'uniqid' => strval($captcha['data']['uniqid'] ?? ''),
        ]);

        $user = $user->refresh();
        $payload = JwtToken::verify(strval($result['token'] ?? ''));
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();
        $sessionId = RequestContext::instance()->sessionId();
        $queuedCookie = strval($this->app->cookie->getCookie()[SystemAuthService::getTokenCookie()][0] ?? '');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('登录成功', $result['info'] ?? '');
        $this->assertStringContainsString('/system', strval($result['data'] ?? ''));
        $this->assertSame('system-auth', $payload['typ'] ?? '');
        $this->assertSame(9101, intval($payload['uid'] ?? 0));
        $this->assertSame(9101, intval(RequestContext::instance()->user()['id'] ?? 0));
        $this->assertNotSame('', $sessionId);
        $this->assertTrue(CacheSession::exists("sid:{$sessionId}"));
        $this->assertSame(1, intval($user->getData('login_num')));
        $this->assertNotSame('', strval($user->getData('login_ip')));
        $this->assertNotSame('', strval($user->getData('login_at')));
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统用户登录', $oplog->getData('action'));
        $this->assertSame('登录系统后台成功', $oplog->getData('content'));
        $this->assertSame('tester', $oplog->getData('username'));
        $this->assertFalse($this->app->cache->has($this->captchaMapKey(strval($captcha['data']['uniqid'] ?? ''))));
        $this->assertStringStartsWith('enc:', $queuedCookie);
        $this->assertNotSame(strval($result['token'] ?? ''), $queuedCookie);
        $this->assertSame(strval($result['token'] ?? ''), RequestTokenService::decodeCookieToken($queuedCookie));
    }

    public function testOutClearsCurrentSessionAndReturnsLoginRedirect(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9201,
            'username' => 'logout-user',
            'password' => $this->hashSystemPassword('logout-pass'),
            'status' => 1,
        ]);
        $sessionId = 'logout-session-id';

        RequestContext::instance()->setAuth($user->toArray(), '', true)->setSessionId($sessionId);
        CacheSession::put([], 600, "sid:{$sessionId}", true);

        $result = $this->callActionController('out', [], 'GET');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('退出登录成功!', $result['info'] ?? '');
        $this->assertStringContainsString('/system/login', strval($result['data'] ?? ''));
        $this->assertSame('', strval($result['token'] ?? 'x'));
        $this->assertSame([], RequestContext::instance()->user());
        $this->assertSame('', RequestContext::instance()->sessionId());
        $this->assertFalse(CacheSession::exists("sid:{$sessionId}"));
        $this->assertFalse(SystemAuthService::isLogin());
    }

    public function testRequestTokenCanDecryptEncryptedCookie(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9251,
            'username' => 'cookie-user',
            'password' => $this->hashSystemPassword('cookie-pass'),
            'status' => 1,
        ])->toArray();
        $token = SystemAuthService::buildToken($user);
        $encodedCookie = RequestTokenService::encodeCookieToken($token);
        $request = (new Request())->withCookie([
            SystemAuthService::getTokenCookie() => $encodedCookie,
        ]);

        RequestContext::clear();
        $this->app->instance('request', $request);

        $this->assertNotSame($token, $encodedCookie);
        $this->assertSame($token, SystemAuthService::requestCookieToken($request));
        $this->assertSame($token, SystemAuthService::requestToken($request));
    }

    public function testRequestTokenUpgradesLegacyPlainCookie(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9261,
            'username' => 'legacy-cookie-user',
            'password' => $this->hashSystemPassword('legacy-cookie-pass'),
            'status' => 1,
        ])->toArray();
        $token = SystemAuthService::buildToken($user);
        $request = (new Request())->withCookie([
            SystemAuthService::getTokenCookie() => $token,
        ]);

        RequestContext::clear();
        $this->app->instance('request', $request);

        $this->assertSame($token, SystemAuthService::requestToken($request));

        $queuedCookie = strval($this->app->cookie->getCookie()[SystemAuthService::getTokenCookie()][0] ?? '');
        $this->assertStringStartsWith('enc:', $queuedCookie);
        $this->assertNotSame($token, $queuedCookie);
        $this->assertSame($token, RequestTokenService::decodeCookieToken($queuedCookie));
    }

    public function testGetIndexRedirectsLoggedInUsersToAdminHome(): void
    {
        RequestContext::instance()->setAuth([
            'id' => 9301,
            'username' => 'redirect-user',
        ], '', true);

        $response = $this->callPageResponse('index', [], 'GET');

        $this->assertSame('/system.html', strval($response->getHeader('Location')));
        $this->assertSame(302, $response->getCode());
    }

    public function testGetIndexRendersLoginPageAndSyncsSiteHostForGuests(): void
    {
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_name',
            'value' => '测试站点',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'app_name',
            'value' => 'ThinkAdmin Test',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'app_version',
            'value' => 'v1.0.0',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_copy',
            'value' => 'Copyright Test',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'login_name',
            'value' => '管理后台登录',
        ]);

        $response = $this->callPageResponse('index', [], 'GET', true);
        $content = $response->getContent();

        $siteHost = SystemConfig::mk()->where(['type' => 'base', 'name' => 'site_host'])->value('value', '');

        $this->assertStringContainsString('<title>系统登录', $content);
        $this->assertStringContainsString('管理后台登录', $content);
        $this->assertStringContainsString('data-captcha-type="LoginCaptcha"', $content);
        $this->assertStringContainsString('background-image:url(/static/theme/img/login/bg1.jpg)', $content);
        $this->assertStringContainsString('data-bg-transition="/static/theme/img/login/bg1.jpg,/static/theme/img/login/bg2.jpg"', $content);
        $this->assertStringContainsString('ThinkAdmin Test', $content);
        $this->assertStringContainsString('Copyright Test', $content);
        $this->assertSame('https://admin.example.com', $siteHost);
    }

    protected function defineSchema(): void
    {
        $this->createSystemConfigTable();
        $this->createSystemUserTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
        $this->app->config->set(array_merge(include TEST_PROJECT_ROOT . '/config/view.php', [
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-system/src/view' . DIRECTORY_SEPARATOR,
        ]), 'view');
    }

    private function callActionController(string $action, array $payload = [], string $method = 'POST'): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod($method)
            ->setController('login')
            ->setAction($action);

        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new LoginController($this->app);
            $controller->{$action}();
            self::fail("Expected LoginController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callPageResponse(string $action, array $payload = [], string $method = 'GET', bool $secure = false)
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->withServer($secure ? ['HTTPS' => 'on'] : [])
            ->setHost($secure ? 'admin.example.com' : '127.0.0.1')
            ->setMethod($method)
            ->setController('login')
            ->setAction($action);

        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new LoginController($this->app);
            $controller->{$action}();
            self::fail("Expected LoginController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        }
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }

    private function captchaMapKey(string $uniqid): string
    {
        return 'think.admin.login.captcha.map.' . hash('sha256', $uniqid);
    }

    private function captchaFailKey(string $type, string $token): string
    {
        return 'think.admin.login.captcha.fail.' . hash('sha256', "{$type}:{$token}");
    }
}
