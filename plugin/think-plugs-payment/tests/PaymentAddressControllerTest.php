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

use plugin\payment\controller\api\auth\Address as AuthAddressController;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class PaymentAddressControllerTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/payment-address.png',
            'userPrefix' => '地址账号',
        ]);
    }

    public function testGetControllerReturnsEnglishInfoWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $login = $account->token()->get(true);
        $this->createPaymentAddressFixture($account->getUnid());
        $this->switchPaymentLang('en-us');

        $response = $this->callAuthApiController('GET', 'get', [], strval($login['token'] ?? ''));

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('Address data loaded successfully', $response['info'] ?? '');
        $this->assertNotEmpty($response['data'] ?? []);
    }

    public function testSetControllerReturnsEnglishValidationInfoWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $login = $account->token()->get(true);
        $this->switchPaymentLang('en-us');

        $response = $this->callAuthApiController('POST', 'set', [
            'user_phone' => $this->randomPhone('1361100'),
            'region_prov' => 'Guangdong',
            'region_city' => 'Shenzhen',
            'region_area' => 'Nanshan',
            'region_addr' => 'Science Park',
        ], strval($login['token'] ?? ''));

        $this->assertSame(0, intval($response['code'] ?? 0));
        $this->assertSame('Recipient name is required', $response['info'] ?? '');
    }

    public function testSetStateAndRemoveReturnEnglishInfosWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $login = $account->token()->get(true);
        $this->switchPaymentLang('en-us');

        $create = $this->callAuthApiController('POST', 'set', [
            'type' => 0,
            'user_name' => 'Alice Receiver',
            'user_phone' => $this->randomPhone('1361101'),
            'region_prov' => 'Guangdong',
            'region_city' => 'Shenzhen',
            'region_area' => 'Nanshan',
            'region_addr' => 'No. 1 Science Park',
        ], strval($login['token'] ?? ''));

        $addressId = intval($create['data']['id'] ?? 0);
        $state = $this->callAuthApiController('POST', 'state', [
            'id' => $addressId,
            'type' => 1,
        ], strval($login['token'] ?? ''));
        $remove = $this->callAuthApiController('POST', 'remove', [
            'id' => $addressId,
        ], strval($login['token'] ?? ''));

        $this->assertSame(1, intval($create['code'] ?? 0));
        $this->assertSame('Saved successfully', $create['info'] ?? '');
        $this->assertGreaterThan(0, $addressId);
        $this->assertSame(1, intval($state['code'] ?? 0));
        $this->assertSame('Default address set successfully', $state['info'] ?? '');
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('Deleted successfully', $remove['info'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentAddressTable();
    }

    private function callAuthApiController(string $method, string $action, array $data, string $token): array
    {
        $request = (new Request())
            ->withGet($data)
            ->withPost($data)
            ->withHeader(['authorization' => "Bearer {$token}"])
            ->setMethod($method)
            ->setController('api.auth.address')
            ->setAction($action);

        $this->setRequestPayload($request, $data);
        RequestContext::clear();
        $this->app->instance('request', $request);

        try {
            $controller = new AuthAddressController($this->app);
            $controller->{$action}();
            self::fail("Expected {$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }

    private function switchPaymentLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        foreach ([
            TEST_PROJECT_ROOT . "/plugin/think-plugs-payment/src/lang/{$langSet}.php",
            TEST_PROJECT_ROOT . "/plugin/think-plugs-account/src/lang/{$langSet}.php",
        ] as $file) {
            if (is_file($file)) {
                $this->app->lang->load($file, $langSet);
            }
        }
    }
}
