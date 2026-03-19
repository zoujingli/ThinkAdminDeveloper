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
use plugin\payment\controller\Integral as IntegralController;
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

    protected function defineSchema(): void
    {
        $this->createAccountTables();
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

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
