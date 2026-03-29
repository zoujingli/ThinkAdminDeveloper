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

use plugin\account\controller\api\Login as LoginController;
use plugin\account\service\Account;
use plugin\account\service\Message;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class AccountLoginControllerTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess();
    }

    public function testSmsLoginReturnsEnglishSuccessMessageWhenLangSetIsEnUs(): void
    {
        $phone = $this->randomPhone('1390088');
        $this->switchAccountLang('en-us');
        $this->rememberVerifyCode($phone, '246810');

        $response = $this->callLoginController('in', [
            'type' => Account::WAP,
            'phone' => $phone,
            'verify' => '246810',
        ]);

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('Login successful', $response['info'] ?? '');
        $this->assertSame($phone, $response['data']['phone'] ?? '');
        $this->assertNotEmpty($response['data']['token'] ?? '');
    }

    public function testAutoReturnsEnglishValidationMessageWhenCodeIsMissing(): void
    {
        $this->switchAccountLang('en-us');

        $response = $this->callLoginController('auto', []);

        $this->assertSame(0, intval($response['code'] ?? 0));
        $this->assertSame('Authorization code is required', $response['info'] ?? '');
    }

    public function testVerifyReturnsEnglishValidationMessageWhenPayloadIsMissing(): void
    {
        $this->switchAccountLang('en-us');

        $response = $this->callLoginController('verify', []);

        $this->assertSame(0, intval($response['code'] ?? 0));
        $this->assertSame('Slider verification is required', $response['info'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
    }

    private function callLoginController(string $action, array $data): array
    {
        $request = (new Request())
            ->withGet($data)
            ->withPost($data)
            ->setMethod('POST')
            ->setController('api.login')
            ->setAction($action);

        $this->setRequestPayload($request, $data);
        RequestContext::clear();
        $this->activateApplicationContext($request);

        try {
            $controller = new LoginController($this->app);
            $controller->{$action}();
            self::fail("Expected LoginController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function rememberVerifyCode(string $phone, string $code, string $scene = Message::tLogin): void
    {
        $this->app->cache->set(md5(strtolower("sms-{$scene}-{$phone}")), [
            'code' => $code,
            'time' => time(),
        ], 600);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
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
