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

use PHPUnit\Framework\TestCase;
use plugin\account\service\Account;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\NullSystemContext;
use think\admin\runtime\RequestContext;
use think\admin\runtime\RequestTokenService;
use think\admin\service\CacheSession;
use think\admin\service\JwtToken;
use think\admin\service\RuntimeService;
use think\App;
use think\Container;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class AccountRuntimeTest extends TestCase
{
    private App $app;

    private array $defaultTypes;

    private AccountRuntimeSystemContextStub $context;

    protected function setUp(): void
    {
        $this->app = new App(dirname(__DIR__, 3));
        RuntimeService::init($this->app);
        $this->app->config->set([
            'default' => 'file',
            'stores' => [
                'file' => ['type' => 'File', 'path' => sys_get_temp_dir() . '/thinkadmin-account-test-cache'],
            ],
        ], 'cache');
        $this->app->config->set(['jwtkey' => 'account-test-jwt'], 'app');

        $this->context = new AccountRuntimeSystemContextStub();
        Container::getInstance()->instance(SystemContextInterface::class, $this->context);

        $this->defaultTypes = (new \ReflectionClass(Account::class))->getDefaultProperties()['types'];
        $this->resetAccountState();
        RequestContext::clear();
        sysvar('', '');
    }

    protected function tearDown(): void
    {
        RequestContext::clear();
        sysvar('', '');
        $this->resetAccountState();
        Container::getInstance()->instance(SystemContextInterface::class, new NullSystemContext());
    }

    public function testTypesExposeKnownChannels(): void
    {
        $types = Account::types(1);

        $this->assertSame('phone', $types[Account::WAP]['field']);
        $this->assertSame('openid', $types[Account::WECHAT]['field']);
        $this->assertSame(Account::WXAPP, $types[Account::WXAPP]['code']);
    }

    public function testDisabledChannelReturnsEmptyField(): void
    {
        $this->context->setData('plugin.account.denys', [Account::WEB]);
        $this->resetAccountState();

        $this->assertSame('', Account::field(Account::WEB));
        $this->assertArrayNotHasKey(Account::WEB, Account::types(1));
    }

    public function testBuildJwtTokenBindsSessionPayload(): void
    {
        $token = Account::buildJwtToken(Account::WAP, 'token-123', 'sid-account-test');
        $data = JwtToken::verify($token, 'account-test-jwt');

        $this->assertSame(Account::getTokenType(), $data['typ']);
        $this->assertSame(Account::WAP, $data['type']);
        $this->assertSame('token-123', $data['token']);
        $this->assertSame('sid-account-test', $data['sid']);
        $this->assertTrue(CacheSession::exists('sid:sid-account-test'));
    }

    public function testRequestTokenPrefersAuthorizationHeader(): void
    {
        RequestContext::clear();
        $headerToken = JwtToken::token([
            'typ' => Account::getTokenType(),
            'type' => Account::WAP,
            'token' => 'header-token',
        ], 'account-test-jwt');
        $cookieToken = JwtToken::token([
            'typ' => Account::getTokenType(),
            'type' => Account::WAP,
            'token' => 'cookie-token',
        ], 'account-test-jwt');
        $encodedCookie = RequestTokenService::encodeCookieToken($cookieToken);

        $request = (new Request())
            ->withHeader(['authorization' => "Bearer {$headerToken}"])
            ->withCookie([Account::getTokenCookie() => $encodedCookie]);

        $this->assertSame($headerToken, Account::requestToken($request));
    }

    public function testRequestTokenCanDecryptEncryptedCookie(): void
    {
        RequestContext::clear();
        $cookieToken = JwtToken::token([
            'typ' => Account::getTokenType(),
            'type' => Account::WAP,
            'token' => 'cookie-only-token',
        ], 'account-test-jwt');
        $encodedCookie = RequestTokenService::encodeCookieToken($cookieToken);
        $request = (new Request())->withCookie([Account::getTokenCookie() => $encodedCookie]);

        $this->assertNotSame($cookieToken, $encodedCookie);
        $this->assertSame($cookieToken, Account::requestToken($request));
    }

    public function testRequestTokenUpgradesLegacyPlainCookie(): void
    {
        RequestContext::clear();
        $cookieToken = JwtToken::token([
            'typ' => Account::getTokenType(),
            'type' => Account::WAP,
            'token' => 'legacy-cookie-token',
        ], 'account-test-jwt');
        $request = (new Request())->withCookie([Account::getTokenCookie() => $cookieToken]);

        $this->app->instance('request', $request);

        $this->assertSame($cookieToken, Account::requestToken($request));

        $queuedCookie = strval($this->app->cookie->getCookie()[Account::getTokenCookie()][0] ?? '');
        $this->assertStringStartsWith('enc:', $queuedCookie);
        $this->assertNotSame($cookieToken, $queuedCookie);
        $this->assertSame($cookieToken, RequestTokenService::decodeCookieToken($queuedCookie));
    }

    public function testAccountTokenCookieCanBeConfigured(): void
    {
        app()->config->set(['account_token_cookie' => 'custom_account_cookie'], 'app');

        $this->assertSame('custom_account_cookie', Account::getTokenCookie());
    }

    private function resetAccountState(): void
    {
        $reflection = new \ReflectionClass(Account::class);

        $types = $reflection->getProperty('types');
        $types->setValue(null, $this->defaultTypes);

        $denys = $reflection->getProperty('denys');
        $denys->setValue(null, null);
    }
}

class AccountRuntimeSystemContextStub implements SystemContextInterface
{
    private array $config = [];

    private array $data = [];

    public function buildToken(): string
    {
        return '';
    }

    public function getTokenHeader(): string
    {
        return 'Authorization';
    }

    public function getTokenCookie(): string
    {
        return 'system_access_token';
    }

    public function getTokenType(): string
    {
        return 'system-auth';
    }

    public function syncTokenCookie(?string $token = null): string
    {
        return strval($token);
    }

    public function check(?string $node = ''): bool
    {
        return false;
    }

    public function getUser(?string $field = null, $default = null)
    {
        return is_null($field) ? [] : $default;
    }

    public function getUserId(): int
    {
        return 0;
    }

    public function isSuper(): bool
    {
        return false;
    }

    public function isLogin(): bool
    {
        return false;
    }

    public function withUploadUnid(?string $uptoken = null): array
    {
        return [0, []];
    }

    public function clearAuth(): bool
    {
        return true;
    }

    public function getConfig(string $name = '', string $default = '')
    {
        return $this->config[$name] ?? $default;
    }

    public function setConfig(string $name, $value = '')
    {
        $this->config[$name] = $value;
        return $value;
    }

    public function getData(string $name, $default = [])
    {
        return $this->data[$name] ?? $default;
    }

    public function setData(string $name, $value): bool
    {
        $this->data[$name] = $value;
        return true;
    }

    public function setOplog(string $action, string $content): bool
    {
        return true;
    }

    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array
    {
        return [];
    }
}
