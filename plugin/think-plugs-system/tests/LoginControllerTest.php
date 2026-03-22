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
use plugin\system\model\SystemOplog;
use plugin\system\service\AuthService;
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
    public function testFailedLoginRequiresSliderVerification(): void
    {
        $ticket = $this->loginPageTicket();

        $result = $this->callActionController('index', [
            'username' => 'missing-user',
            'password' => $this->encodeLoginPassword('wrong-password', $ticket),
            'password_mode' => $ticket['password_mode'],
            'token' => $ticket['token'],
        ]);
        $slider = $this->callActionController('slider', [
            'token' => $ticket['token'],
        ]);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('登录账号或密码错误，请重新输入!', $result['info'] ?? '');
        $this->assertTrue(boolval($result['data']['need_verify'] ?? false));
        $this->assertSame('1', strval($this->app->cache->get($this->verifyFailKey($ticket['token']), 0)));

        $this->assertSame(1, intval($slider['code'] ?? 0));
        $this->assertSame('生成拼图成功', $slider['info'] ?? '');
        $this->assertStringStartsWith('V', strval($slider['data']['uniqid'] ?? ''));
        $this->assertStringStartsWith('data:image/png;base64,', strval($slider['data']['bgimg'] ?? ''));
        $this->assertStringStartsWith('data:image/png;base64,', strval($slider['data']['water'] ?? ''));
        $this->assertSame(600, intval($slider['data']['width'] ?? 0));
        $this->assertSame(100, intval($slider['data']['piece_width'] ?? 0));
    }

    public function testFreshEncryptedLoginSucceedsWithoutSlider(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9051,
            'username' => 'fresh-user',
            'password' => $this->hashSystemPassword('secret123'),
            'status' => 1,
        ]);
        $ticket = $this->loginPageTicket();

        $result = $this->callActionController('index', [
            'username' => 'fresh-user',
            'password' => $this->encodeLoginPassword('secret123', $ticket),
            'password_mode' => $ticket['password_mode'],
            'token' => $ticket['token'],
        ]);

        $user = $user->refresh();
        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('登录成功', $result['info'] ?? '');
        $this->assertSame(1, intval($user->getData('login_num')));
        $this->assertFalse($this->app->cache->has($this->verifyFailKey($ticket['token'])));
    }

    public function testIndexLogsInUserUpdatesStatsAndWritesOplogAfterSliderVerification(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('secret123'),
            'status' => 1,
        ]);
        $ticket = $this->loginPageTicket();

        $failed = $this->callActionController('index', [
            'username' => 'tester',
            'password' => $this->encodeLoginPassword('wrong-password', $ticket),
            'password_mode' => $ticket['password_mode'],
            'token' => $ticket['token'],
        ]);
        $slider = $this->callActionController('slider', [
            'token' => $ticket['token'],
        ]);
        $verify = $this->sliderVerifyValue(strval($slider['data']['uniqid'] ?? ''));
        $checked = $this->callActionController('check', [
            'uniqid' => strval($slider['data']['uniqid'] ?? ''),
            'verify' => $verify,
        ]);

        $result = $this->callActionController('index', [
            'username' => 'tester',
            'password' => $this->encodeLoginPassword('secret123', $ticket),
            'password_mode' => $ticket['password_mode'],
            'token' => $ticket['token'],
            'verify' => $verify,
            'uniqid' => strval($slider['data']['uniqid'] ?? ''),
        ]);

        $user = $user->refresh();
        $payload = JwtToken::verify(strval($result['token'] ?? ''));
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();
        $sessionId = RequestContext::instance()->sessionId();
        $queuedCookie = strval($this->app->cookie->getCookie()[AuthService::getTokenCookie()][0] ?? '');

        $this->assertSame(0, intval($failed['code'] ?? 1));
        $this->assertSame('登录账号或密码错误，请重新输入!', $failed['info'] ?? '');
        $this->assertSame(1, intval($checked['code'] ?? 0));
        $this->assertSame(1, intval($checked['data']['state'] ?? 0));
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
        $this->assertFalse($this->app->cache->has(strval($slider['data']['uniqid'] ?? '')));
        $this->assertFalse($this->app->cache->has($this->verifyFailKey($ticket['token'])));
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
        $this->assertFalse(AuthService::isLogin());
    }

    public function testRequestTokenCanDecryptEncryptedCookie(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9251,
            'username' => 'cookie-user',
            'password' => $this->hashSystemPassword('cookie-pass'),
            'status' => 1,
        ])->toArray();
        $token = AuthService::buildToken($user);
        $encodedCookie = RequestTokenService::encodeCookieToken($token);
        $request = (new Request())->withCookie([
            AuthService::getTokenCookie() => $encodedCookie,
        ]);

        RequestContext::clear();
        $this->app->instance('request', $request);

        $this->assertNotSame($token, $encodedCookie);
        $this->assertSame($token, AuthService::requestCookieToken($request));
        $this->assertSame($token, AuthService::requestToken($request));
    }

    public function testRequestTokenUpgradesLegacyPlainCookie(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9261,
            'username' => 'legacy-cookie-user',
            'password' => $this->hashSystemPassword('legacy-cookie-pass'),
            'status' => 1,
        ])->toArray();
        $token = AuthService::buildToken($user);
        $request = (new Request())->withCookie([
            AuthService::getTokenCookie() => $token,
        ]);

        RequestContext::clear();
        $this->app->instance('request', $request);

        $this->assertSame($token, AuthService::requestToken($request));

        $queuedCookie = strval($this->app->cookie->getCookie()[AuthService::getTokenCookie()][0] ?? '');
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
        $this->createSystemDataFixture([
            'name' => 'system.site',
            'value' => [
                'website_name' => '测试站点',
                'application_name' => 'ThinkAdmin Test',
                'application_version' => 'v1.0.0',
                'copyright' => 'Copyright Test',
                'login_title' => '管理后台登录',
                'login_background_images' => [
                    '/static/theme/img/login/bg1.jpg',
                    '/static/theme/img/login/bg2.jpg',
                ],
            ],
        ]);

        $response = $this->callPageResponse('index', [], 'GET', true);
        $content = $response->getContent();

        $this->assertStringContainsString('<title>系统登录', $content);
        $this->assertStringContainsString('管理后台登录', $content);
        $this->assertMatchesRegularExpression('/data-login-token="[^"]+"/', $content);
        $this->assertMatchesRegularExpression('/data-login-password-key="[^"]*"/', $content);
        $this->assertMatchesRegularExpression('/data-login-slider="[^"]*system\/login\/slider[^"]*"/', $content);
        $this->assertMatchesRegularExpression('/data-login-check="[^"]*system\/login\/check[^"]*"/', $content);
        $this->assertMatchesRegularExpression('/name="password_mode" value="(plain|rsa)"/', $content);
        $this->assertStringContainsString('background-image:url(', $content);
        $this->assertStringContainsString('data-bg-transition="', $content);
        $this->assertStringContainsString('ThinkAdmin Test', $content);
        $this->assertStringContainsString('Copyright Test', $content);
        $this->assertSame('no-store, no-cache, must-revalidate, max-age=0', strval($response->getHeader('Cache-Control')));
        $this->assertSame('no-cache', strval($response->getHeader('Pragma')));
        $this->assertSame('0', strval($response->getHeader('Expires')));
    }

    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
        $this->createSystemUserTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-system/src/view' . DIRECTORY_SEPARATOR,
        ]);
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
            ->withServer($secure ? [
                'HTTPS' => 'on',
                'HTTP_HOST' => 'admin.example.com',
                'SERVER_PORT' => '443',
                'REQUEST_SCHEME' => 'https',
            ] : [
                'HTTP_HOST' => '127.0.0.1',
                'SERVER_PORT' => '80',
                'REQUEST_SCHEME' => 'http',
            ])
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

    private function loginPageTicket(): array
    {
        $response = $this->callPageResponse('index', [], 'GET', true);
        $content = $response->getContent();
        return [
            'content' => $content,
            'token' => $this->extractHtmlAttribute($content, 'data-login-token'),
            'public_key' => $this->extractHtmlAttribute($content, 'data-login-password-key'),
            'password_mode' => $this->extractInputValue($content, 'password_mode', 'plain'),
        ];
    }

    private function extractHtmlAttribute(string $content, string $attribute): string
    {
        preg_match('/' . preg_quote($attribute, '/') . '="([^"]*)"/', $content, $matches);
        return html_entity_decode(strval($matches[1] ?? ''), ENT_QUOTES);
    }

    private function extractInputValue(string $content, string $name, string $default = ''): string
    {
        preg_match('/name="' . preg_quote($name, '/') . '" value="([^"]*)"/', $content, $matches);
        return html_entity_decode(strval($matches[1] ?? $default), ENT_QUOTES);
    }

    private function encodeLoginPassword(string $password, array $ticket): string
    {
        if (strtolower(strval($ticket['password_mode'] ?? 'plain')) !== 'rsa') {
            return $password;
        }

        return $this->encryptLoginPassword($password, strval($ticket['public_key'] ?? ''));
    }

    private function encryptLoginPassword(string $password, string $publicKey): string
    {
        $this->assertNotSame('', $publicKey);
        $pem = "-----BEGIN PUBLIC KEY-----\n" . trim(chunk_split($publicKey, 64, "\n")) . "\n-----END PUBLIC KEY-----\n";
        $this->assertTrue(openssl_public_encrypt($password, $encrypted, $pem, OPENSSL_PKCS1_OAEP_PADDING));
        return base64_encode($encrypted);
    }

    private function sliderVerifyValue(string $uniqid): string
    {
        $range = $this->app->cache->get($uniqid, [])['range'] ?? [0, 0];
        return (string)intval((intval($range[0] ?? 0) + intval($range[1] ?? 0)) / 2);
    }

    private function verifyFailKey(string $token): string
    {
        return 'think.admin.login.verify.fail.' . hash('sha256', $token);
    }
}
