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

use plugin\payment\controller\Balance as BalanceController;
use plugin\payment\controller\Config as ConfigController;
use plugin\payment\controller\Integral as IntegralController;
use plugin\payment\controller\api\auth\Balance as AuthBalanceController;
use plugin\payment\controller\api\auth\Integral as AuthIntegralController;
use plugin\payment\model\PluginPaymentBalance;
use plugin\payment\model\PluginPaymentIntegral;
use plugin\payment\service\Balance;
use plugin\payment\service\Integral;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class PaymentLedgerControllerTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/payment-ledger-controller.png',
            'userPrefix' => '台账控制器账号',
        ]);
    }

    protected function afterSchemaCreated(): void
    {
        $this->app->setAppPath(TEST_PROJECT_ROOT . '/plugin/think-plugs-payment/src/');
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-payment/src/view' . DIRECTORY_SEPARATOR,
        ]);
    }

    public function testBalanceControllerUnlockCancelAndRemoveChain(): void
    {
        $user = $this->createAccountUser();
        Balance::create(intval($user->getAttr('id')), 'ledger-balance-001', '余额发放', '30.00', '后台台账测试');

        $unlock = $this->callController(BalanceController::class, 'unlock', [
            'code' => 'ledger-balance-001',
            'unlock' => 1,
        ]);
        $cancel = $this->callController(BalanceController::class, 'cancel', [
            'code' => 'ledger-balance-001',
            'cancel' => 1,
        ]);
        $remove = $this->callController(BalanceController::class, 'remove', [
            'code' => 'ledger-balance-001',
        ]);

        $model = PluginPaymentBalance::mk()->withTrashed()->where(['code' => 'ledger-balance-001'])->findOrEmpty();
        $user = $user->refresh();
        $extra = $user->getAttr('extra');

        $this->assertSame(1, intval($unlock['code'] ?? 0));
        $this->assertSame(1, intval($cancel['code'] ?? 0));
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('交易操作成功！', $remove['info'] ?? '');
        $this->assertSame(1, intval($model->getAttr('unlock')));
        $this->assertSame(1, intval($model->getAttr('cancel')));
        $this->assertNotEmpty($model->getAttr('unlock_time'));
        $this->assertNotEmpty($model->getAttr('cancel_time'));
        $this->assertNotEmpty($model->getAttr('delete_time'));
        $this->assertSame('0.00', $this->decimal($extra['balance_lock'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['balance_total'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['balance_usable'] ?? 0));
    }

    public function testIntegralControllerUnlockCancelAndRemoveChain(): void
    {
        $user = $this->createAccountUser();
        Integral::create(intval($user->getAttr('id')), 'ledger-integral-001', '积分发放', '18.00', '后台台账测试');

        $unlock = $this->callController(IntegralController::class, 'unlock', [
            'code' => 'ledger-integral-001',
            'unlock' => 1,
        ]);
        $cancel = $this->callController(IntegralController::class, 'cancel', [
            'code' => 'ledger-integral-001',
            'cancel' => 1,
        ]);
        $remove = $this->callController(IntegralController::class, 'remove', [
            'code' => 'ledger-integral-001',
        ]);

        $model = PluginPaymentIntegral::mk()->withTrashed()->where(['code' => 'ledger-integral-001'])->findOrEmpty();
        $user = $user->refresh();
        $extra = $user->getAttr('extra');

        $this->assertSame(1, intval($unlock['code'] ?? 0));
        $this->assertSame(1, intval($cancel['code'] ?? 0));
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('交易操作成功！', $remove['info'] ?? '');
        $this->assertSame(1, intval($model->getAttr('unlock')));
        $this->assertSame(1, intval($model->getAttr('cancel')));
        $this->assertNotEmpty($model->getAttr('unlock_time'));
        $this->assertNotEmpty($model->getAttr('cancel_time'));
        $this->assertNotEmpty($model->getAttr('delete_time'));
        $this->assertSame('0.00', $this->decimal($extra['integral_lock'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['integral_total'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['integral_usable'] ?? 0));
    }

    public function testBalanceIndexControllerFiltersActiveLedgersByUserKeyword(): void
    {
        $user = $this->createAccountUser([
            'username' => 'balance-search-user',
            'nickname' => '余额检索用户',
        ]);
        $other = $this->createAccountUser([
            'username' => 'balance-other-user',
            'nickname' => '余额其他用户',
        ]);

        Balance::create(intval($user->getAttr('id')), 'ledger-balance-active', '有效余额', '30.00', '有效余额记录');
        Balance::create(intval($user->getAttr('id')), 'ledger-balance-cancelled', '作废余额', '12.00', '作废余额记录');
        Balance::cancel('ledger-balance-cancelled', 1);
        Balance::create(intval($user->getAttr('id')), 'ledger-balance-deleted', '删除余额', '8.00', '删除余额记录');
        Balance::remove('ledger-balance-deleted');
        Balance::create(intval($other->getAttr('id')), 'ledger-balance-other', '其他余额', '6.00', '其他用户余额');

        $result = $this->callIndexController(BalanceController::class, [
            'output' => 'json',
            'user' => 'balance-search-user',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('ledger-balance-active', $result['data']['list'][0]['code'] ?? '');
        $this->assertSame('balance-search-user', $result['data']['list'][0]['user']['username'] ?? '');
    }

    public function testIntegralIndexControllerShowsCancelledLedgersForHistoryType(): void
    {
        $user = $this->createAccountUser([
            'username' => 'integral-history-user',
            'nickname' => '积分检索用户',
        ]);
        $other = $this->createAccountUser([
            'username' => 'integral-other-user',
            'nickname' => '积分其他用户',
        ]);

        Integral::create(intval($user->getAttr('id')), 'ledger-integral-active', '有效积分', '18.00', '有效积分记录');
        Integral::create(intval($user->getAttr('id')), 'ledger-integral-cancelled', '作废积分', '9.00', '作废积分记录');
        Integral::cancel('ledger-integral-cancelled', 1);
        Integral::create(intval($user->getAttr('id')), 'ledger-integral-deleted', '删除积分', '5.00', '删除积分记录');
        Integral::cancel('ledger-integral-deleted', 1);
        Integral::remove('ledger-integral-deleted');
        Integral::create(intval($other->getAttr('id')), 'ledger-integral-other', '其他积分', '7.00', '其他用户积分');
        Integral::cancel('ledger-integral-other', 1);

        $result = $this->callIndexController(IntegralController::class, [
            'output' => 'json',
            'type' => 'history',
            'user' => 'integral-history-user',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('ledger-integral-cancelled', $result['data']['list'][0]['code'] ?? '');
        $this->assertSame('integral-history-user', $result['data']['list'][0]['user']['username'] ?? '');
    }

    public function testBalanceIndexControllerAppliesDateRangeAndDescendingOrder(): void
    {
        $user = $this->createAccountUser([
            'username' => 'balance-range-user',
            'nickname' => '余额时间用户',
        ]);

        Balance::create(intval($user->getAttr('id')), 'ledger-balance-older', '旧余额', '10.00', '旧余额记录');
        Balance::create(intval($user->getAttr('id')), 'ledger-balance-middle', '中余额', '20.00', '中余额记录');
        Balance::create(intval($user->getAttr('id')), 'ledger-balance-newer', '新余额', '30.00', '新余额记录');

        PluginPaymentBalance::mk()->where(['code' => 'ledger-balance-older'])->update([
            'create_time' => '2026-03-09 08:00:00',
            'update_time' => '2026-03-09 08:00:00',
        ]);
        PluginPaymentBalance::mk()->where(['code' => 'ledger-balance-middle'])->update([
            'create_time' => '2026-03-10 09:00:00',
            'update_time' => '2026-03-10 09:00:00',
        ]);
        PluginPaymentBalance::mk()->where(['code' => 'ledger-balance-newer'])->update([
            'create_time' => '2026-03-10 18:30:00',
            'update_time' => '2026-03-10 18:30:00',
        ]);

        $result = $this->callIndexController(BalanceController::class, [
            'output' => 'json',
            'user' => 'balance-range-user',
            'create_time' => '2026-03-10 - 2026-03-10',
            '_field_' => 'create_time',
            '_order_' => 'desc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(2, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame([
            'ledger-balance-newer',
            'ledger-balance-middle',
        ], array_column($result['data']['list'] ?? [], 'code'));
    }

    public function testIntegralIndexControllerAppliesAmountSortForHistoryView(): void
    {
        $user = $this->createAccountUser([
            'username' => 'integral-sort-user',
            'nickname' => '积分排序用户',
        ]);

        Integral::create(intval($user->getAttr('id')), 'ledger-integral-low', '低积分', '6.00', '低积分记录');
        Integral::cancel('ledger-integral-low', 1);
        Integral::create(intval($user->getAttr('id')), 'ledger-integral-mid', '中积分', '12.00', '中积分记录');
        Integral::cancel('ledger-integral-mid', 1);
        Integral::create(intval($user->getAttr('id')), 'ledger-integral-high', '高积分', '18.00', '高积分记录');
        Integral::cancel('ledger-integral-high', 1);
        Integral::create(intval($user->getAttr('id')), 'ledger-integral-deleted-history', '删积分', '24.00', '删除积分记录');
        Integral::cancel('ledger-integral-deleted-history', 1);
        Integral::remove('ledger-integral-deleted-history');

        $result = $this->callIndexController(IntegralController::class, [
            'output' => 'json',
            'type' => 'history',
            'user' => 'integral-sort-user',
            '_field_' => 'amount',
            '_order_' => 'desc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(3, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame([
            'ledger-integral-high',
            'ledger-integral-mid',
            'ledger-integral-low',
        ], array_column($result['data']['list'] ?? [], 'code'));
    }

    public function testBalanceIndexControllerReturnsSecondPageWithConfiguredLimit(): void
    {
        $user = $this->createAccountUser([
            'username' => 'balance-page-user',
            'nickname' => '余额分页用户',
        ]);

        for ($i = 1; $i <= 11; ++$i) {
            $code = sprintf('ledger-balance-page-%02d', $i);
            Balance::create(intval($user->getAttr('id')), $code, "分页余额{$i}", number_format((float)$i, 2, '.', ''), '余额分页测试');
        }

        $result = $this->callIndexController(BalanceController::class, [
            'output' => 'json',
            'user' => 'balance-page-user',
            '_field_' => 'amount',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 10,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(11, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(10, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('ledger-balance-page-11', $result['data']['list'][0]['code'] ?? '');
        $this->assertSame('11.00', $this->decimal($result['data']['list'][0]['amount'] ?? 0));
    }

    public function testIntegralIndexControllerFallsBackToDefaultLimitWhenRequestedLimitIsInvalid(): void
    {
        $user = $this->createAccountUser([
            'username' => 'integral-page-user',
            'nickname' => '积分分页用户',
        ]);

        for ($i = 1; $i <= 21; ++$i) {
            $code = sprintf('ledger-integral-page-%02d', $i);
            Integral::create(intval($user->getAttr('id')), $code, "分页积分{$i}", number_format((float)$i, 2, '.', ''), '积分分页测试');
            Integral::cancel($code, 1);
        }

        $result = $this->callIndexController(IntegralController::class, [
            'output' => 'json',
            'type' => 'history',
            'user' => 'integral-page-user',
            '_field_' => 'amount',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 999,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(21, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(20, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('ledger-integral-page-21', $result['data']['list'][0]['code'] ?? '');
        $this->assertSame('21.00', $this->decimal($result['data']['list'][0]['amount'] ?? 0));
    }

    public function testConfigIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchPaymentLang('en-us');

        $html = $this->callActionHtml(ConfigController::class, 'index');

        $this->assertStringContainsString('Payment Management', $html);
        $this->assertStringContainsString('Recycle Bin', $html);
        $this->assertStringContainsString('Payment Code', $html);
        $this->assertStringContainsString('Payment Name', $html);
        $this->assertStringContainsString('Payment Type', $html);
        $this->assertStringContainsString('Payment Configuration Management', $html);
        $this->assertStringContainsString('Search', $html);
        $this->assertStringNotContainsString('支付编号', $html);
    }

    public function testConfigAddRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchPaymentLang('en-us');

        $html = $this->callActionHtml(ConfigController::class, 'add');

        $this->assertStringContainsString('Payment Type', $html);
        $this->assertStringContainsString('Payment Name', $html);
        $this->assertStringContainsString('Payment Icon', $html);
        $this->assertStringContainsString('Payment Description', $html);
        $this->assertStringContainsString('Save Data', $html);
        $this->assertStringContainsString('Offline Payment QR Code', $html);
        $this->assertStringNotContainsString('支付方式', $html);
    }

    public function testIntegralIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $user = $this->createAccountUser([
            'username' => 'integral-english-user',
            'nickname' => '积分英文用户',
        ]);

        Integral::create(intval($user->getAttr('id')), 'ledger-integral-en', '英文积分', '16.00', '英文积分记录');
        $this->switchPaymentLang('en-us');

        $html = $this->callActionHtml(IntegralController::class, 'index');

        $this->assertStringContainsString('Integral Statistics', $html);
        $this->assertStringContainsString('Total Issued', $html);
        $this->assertStringContainsString('User Account', $html);
        $this->assertStringContainsString('Transaction Status', $html);
        $this->assertStringContainsString('Operation Remark', $html);
        $this->assertStringContainsString('Actions', $html);
        $this->assertStringNotContainsString('积分统计', $html);
    }

    public function testApiBalanceGetReturnsEnglishInfoWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $login = $account->token()->get(true);
        Balance::create($account->getUnid(), 'api-balance-001', 'API余额', '18.00', 'API余额记录', true);
        $this->switchPaymentLang('en-us');

        $response = $this->callAuthApiController(AuthBalanceController::class, 'get', ['page' => 1], strval($login['token'] ?? ''));

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('Balance records loaded successfully', $response['info'] ?? '');
        $this->assertNotEmpty($response['data'] ?? []);
    }

    public function testApiIntegralGetReturnsEnglishInfoWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $login = $account->token()->get(true);
        Integral::create($account->getUnid(), 'api-integral-001', 'API积分', '16.00', 'API积分记录', true);
        $this->switchPaymentLang('en-us');

        $response = $this->callAuthApiController(AuthIntegralController::class, 'get', ['page' => 1], strval($login['token'] ?? ''));

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('Integral records loaded successfully', $response['info'] ?? '');
        $this->assertNotEmpty($response['data'] ?? []);
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentConfigTable();
        $this->createPaymentBalanceTable();
        $this->createPaymentIntegralTable();
    }

    /**
     * @param class-string $controllerClass
     */
    private function callController(string $controllerClass, string $action, array $data): array
    {
        $parts = explode('\\', $controllerClass);
        $request = (new Request())
            ->withGet($data)
            ->withPost($data)
            ->setMethod('POST')
            ->setController(strtolower(strval(end($parts))))
            ->setAction($action);

        RequestContext::clear();
        $this->app->instance('request', $request);

        try {
            $controller = new $controllerClass($this->app);
            $controller->{$action}();
            self::fail("Expected {$controllerClass}::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    /**
     * @param class-string $controllerClass
     */
    private function callIndexController(string $controllerClass, array $query): array
    {
        $parts = explode('\\', $controllerClass);
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController(strtolower(strval(end($parts))))
            ->setAction('index');

        $this->setRequestPayload($request, $query);
        RequestContext::clear();
        $this->app->instance('request', $request);

        try {
            $controller = new $controllerClass($this->app);
            $controller->index();
            self::fail("Expected {$controllerClass}::index to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    /**
     * @param class-string $controllerClass
     */
    private function callAuthApiController(string $controllerClass, string $action, array $query, string $token): array
    {
        $parts = explode('\\', $controllerClass);
        $request = (new Request())
            ->withGet($query)
            ->withHeader(['authorization' => "Bearer {$token}"])
            ->setMethod('GET')
            ->setController('api.auth.' . strtolower(strval(end($parts))))
            ->setAction($action);

        $this->setRequestPayload($request, $query);
        RequestContext::clear();
        $this->app->instance('request', $request);

        try {
            $controller = new $controllerClass($this->app);
            $controller->{$action}();
            self::fail("Expected {$controllerClass}::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    /**
     * @param class-string $controllerClass
     */
    private function callActionHtml(string $controllerClass, string $action, array $query = []): string
    {
        $parts = explode('\\', $controllerClass);
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController(strtolower(strval(end($parts))))
            ->setAction($action);

        $this->setRequestPayload($request, $query);
        RequestContext::clear();
        RequestContext::instance()->setAuth([
            'id' => 9001,
            'username' => 'admin',
            'password' => 'test-admin-password',
        ], '', true);
        $this->activateApplicationContext($request);

        try {
            $controller = new $controllerClass($this->app);
            $controller->{$action}();
            self::fail("Expected {$controllerClass}::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
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

    private function createPaymentConfigTable(): void
    {
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_payment_config (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT DEFAULT '',
    code TEXT DEFAULT '',
    name TEXT DEFAULT '',
    cover TEXT DEFAULT '',
    remark TEXT DEFAULT '',
    content TEXT DEFAULT '',
    sort INTEGER DEFAULT 0,
    status INTEGER DEFAULT 1,
    delete_time TEXT DEFAULT NULL,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }
}
