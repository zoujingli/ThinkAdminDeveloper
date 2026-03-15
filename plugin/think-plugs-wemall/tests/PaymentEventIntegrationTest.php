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

use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\Balance;
use plugin\payment\service\Integral;
use plugin\payment\service\Payment;
use plugin\wemall\controller\api\auth\Order as AuthOrderController;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderSender;
use plugin\wemall\model\PluginWemallUserCreate;
use plugin\wemall\model\PluginWemallUserRebate;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\Service as WemallService;
use plugin\wemall\service\UserOrder;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;

/**
 * @internal
 * @coversNothing
 */
class PaymentEventIntegrationTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/wemall-account.png',
            'userPrefix' => '商城账号',
        ]);
    }

    public function testSuccessEventPromotesOrderToPaidDeliveryState(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-SUCCESS-001',
            'amount_real' => '10.00',
            'amount_total' => '10.00',
            'delivery_type' => 1,
        ]);

        $response = Payment::mk(Payment::EMPTY)->create(
            $account,
            $order->getAttr('order_no'),
            '商城成功支付订单',
            '10.00',
            '10.00',
            '商城成功支付'
        );

        $order = PluginWemallOrder::mk()->withTrashed()->findOrEmpty($order->getKey());
        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();

        $this->assertTrue($response->status);
        $this->assertTrue($record->isExists());
        $this->assertSame(4, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
        $this->assertSame('10.00', $this->decimal($order->getAttr('payment_amount')));
        $this->assertSame('10.00', $this->decimal($order->getAttr('amount_payment')));
        $this->assertSame('0.00', $this->decimal($order->getAttr('amount_balance')));
        $this->assertSame('0.00', $this->decimal($order->getAttr('amount_integral')));
        $this->assertNotEmpty($order->getAttr('payment_time'));
    }

    public function testAuditAndRefuseEventsMoveVoucherOrderBetweenPendingAndPayable(): void
    {
        $account = $this->createBoundAccountFixture(Account::WEB);
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-VOUCHER-001',
            'amount_real' => '8.00',
            'amount_total' => '8.00',
            'delivery_type' => 1,
        ]);

        $response = Payment::mk(Payment::VOUCHER)->create(
            $account,
            $order->getAttr('order_no'),
            '商城凭证支付订单',
            '8.00',
            '8.00',
            '商城凭证支付',
            '',
            'https://example.com/wemall-voucher.png'
        );

        $order = PluginWemallOrder::mk()->withTrashed()->findOrEmpty($order->getKey());
        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();

        $this->assertSame(3, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
        $this->assertSame('0.00', $this->decimal($order->getAttr('payment_amount')));

        $record->save(['audit_status' => 0, 'payment_status' => 0]);
        $this->app->event->trigger('PluginPaymentRefuse', $record->refresh());
        $order = $order->refresh();

        $this->assertSame(2, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
    }

    public function testCancelEventRefreshesOrderPaymentStatisticsAfterRefund(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-CANCEL-001',
            'status' => 4,
            'payment_status' => 1,
            'amount_real' => '10.00',
            'amount_total' => '10.00',
            'payment_amount' => '10.00',
            'amount_payment' => '10.00',
            'delivery_type' => 1,
        ]);

        $response = Payment::mk(Payment::EMPTY)->create(
            $account,
            $order->getAttr('order_no'),
            '商城退款订单',
            '10.00',
            '10.00',
            '商城退款前支付'
        );
        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();

        Payment::mk(Payment::EMPTY)->refund($record->getAttr('code'), '4.00', '商城部分退款');
        $order = $order->refresh();

        $this->assertSame(4, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
        $this->assertSame('6.00', $this->decimal($order->getAttr('payment_amount')));
        $this->assertSame('6.00', $this->decimal($order->getAttr('amount_payment')));
    }

    public function testOrderConfirmEventUnlocksRewardLedgers(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-CONFIRM-001',
            'status' => 6,
            'payment_status' => 1,
            'amount_real' => '12.00',
            'amount_total' => '12.00',
            'payment_amount' => '12.00',
            'amount_payment' => '12.00',
            'reward_balance' => '2.50',
            'reward_integral' => '4.00',
        ]);

        $rewardCode = "CZ{$order->getAttr('order_no')}";
        $balance = Balance::create($account->getUnid(), $rewardCode, '购物奖励余额', '2.50', '确认收货前锁定奖励', false);
        $integral = Integral::create($account->getUnid(), $rewardCode, '购物奖励积分', '4.00', '确认收货前锁定奖励', false);

        $this->assertSame(0, intval($balance->getAttr('unlock')));
        $this->assertSame(0, intval($integral->getAttr('unlock')));

        $this->app->event->trigger('PluginWemallOrderConfirm', $order->refresh());

        $balance = $balance->refresh();
        $integral = $integral->refresh();

        $this->assertSame(1, intval($balance->getAttr('unlock')));
        $this->assertSame(1, intval($integral->getAttr('unlock')));
        $this->assertNotEmpty($balance->getAttr('unlock_time'));
        $this->assertNotEmpty($integral->getAttr('unlock_time'));
    }

    public function testOrderConfirmEventConfirmsLockedRebatesAndRefreshesUserStats(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-REBATE-001',
            'status' => 6,
            'payment_status' => 1,
            'amount_real' => '18.00',
            'amount_total' => '18.00',
            'payment_amount' => '18.00',
            'amount_payment' => '18.00',
        ]);

        $rebate = PluginWemallUserRebate::mk();
        $rebate->save([
            'unid' => $account->getUnid(),
            'order_unid' => $account->getUnid(),
            'layer' => 1,
            'status' => 0,
            'delete_time' => null,
            'code' => 'REBATE-LOCK-001',
            'hash' => 'hash-rebate-001',
            'name' => '一级返佣',
            'type' => 'platform',
            'date' => date('Ymd'),
            'order_no' => $order->getAttr('order_no') . '-A',
            'remark' => '待确认收货返佣',
            'amount' => '3.50',
            'order_amount' => '18.00',
        ]);

        $this->app->event->trigger('PluginWemallOrderConfirm', $order->refresh());

        $rebate = $rebate->refresh();
        $user = PluginAccountUser::mk()->findOrEmpty($account->getUnid());
        $extra = $user->getAttr('extra');

        $this->assertSame(1, intval($rebate->getAttr('status')));
        $this->assertSame('订单已确认收货！', $rebate->getAttr('remark'));
        $this->assertNotEmpty($rebate->getAttr('confirm_time'));
        $this->assertSame('3.50', $this->decimal($extra['rebate_total'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['rebate_used'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['rebate_lock'] ?? 0));
        $this->assertSame('3.50', $this->decimal($extra['rebate_usable'] ?? 0));
    }

    public function testPaymentConfirmEventConfirmsLockedRebatesByOrderNumber(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-PAYCONFIRM-001',
            'status' => 6,
            'payment_status' => 1,
            'amount_real' => '21.00',
            'amount_total' => '21.00',
            'payment_amount' => '21.00',
            'amount_payment' => '21.00',
        ]);

        $rebate = PluginWemallUserRebate::mk();
        $rebate->save([
            'unid' => $account->getUnid(),
            'order_unid' => $account->getUnid(),
            'layer' => 1,
            'status' => 0,
            'delete_time' => null,
            'code' => 'REBATE-LOCK-002',
            'hash' => 'hash-rebate-002',
            'name' => '支付确认返佣',
            'type' => 'platform',
            'date' => date('Ymd'),
            'order_no' => $order->getAttr('order_no') . '-B',
            'remark' => '待支付确认返佣',
            'amount' => '4.20',
            'order_amount' => '21.00',
        ]);

        $this->app->event->trigger('PluginPaymentConfirm', ['order_no' => $order->getAttr('order_no')]);

        $rebate = $rebate->refresh();
        $user = PluginAccountUser::mk()->findOrEmpty($account->getUnid());
        $extra = $user->getAttr('extra');

        $this->assertSame(1, intval($rebate->getAttr('status')));
        $this->assertSame('订单已确认收货！', $rebate->getAttr('remark'));
        $this->assertNotEmpty($rebate->getAttr('confirm_time'));
        $this->assertSame('4.20', $this->decimal($extra['rebate_total'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['rebate_used'] ?? 0));
        $this->assertSame('0.00', $this->decimal($extra['rebate_lock'] ?? 0));
        $this->assertSame('4.20', $this->decimal($extra['rebate_usable'] ?? 0));
    }

    public function testAccountBindEventInitializesMissingWemallRelation(): void
    {
        $account = $this->createAccountFixture();
        $current = $account->get();
        $user = $this->createAccountUser(['phone' => $current['phone']]);
        PluginWemallUserRelation::mk()->save([
            'unid' => $user->getAttr('id'),
            'path' => '',
            'level_name' => '',
            'agent_level_name' => '',
        ]);

        $this->registerWemallService();
        $this->app->request->withPost([]);
        $account->bind(['id' => $user->getAttr('id')], ['phone' => $user->getAttr('phone')]);

        $relation = PluginWemallUserRelation::mk()->where(['unid' => $user->getAttr('id')])->findOrEmpty();

        $this->assertTrue($relation->isExists());
        $this->assertSame(',', $relation->getAttr('path'));
        $this->assertSame('普通用户', $relation->getAttr('level_name'));
        $this->assertSame('会员用户', $relation->getAttr('agent_level_name'));
        $this->assertSame(0, intval($relation->getAttr('entry_agent')));
        $this->assertSame(0, intval($relation->getAttr('entry_member')));
    }

    public function testAccountBindEventBindsInviterWhenFphoneProvided(): void
    {
        $parentAccount = $this->createBoundAccountFixture();
        $parentUser = PluginAccountUser::mk()->findOrEmpty($parentAccount->getUnid());
        $this->createWemallRelationFixture($parentUser->getAttr('id'), [
            'entry_agent' => 1,
            'path' => ',',
        ]);

        $childAccount = $this->createAccountFixture(Account::WEB);
        $childCurrent = $childAccount->get();
        $childUser = $this->createAccountUser(['phone' => $childCurrent['phone']]);
        $this->createWemallRelationFixture($childUser->getAttr('id'), [
            'entry_agent' => 0,
            'entry_member' => 0,
            'path' => ',',
        ]);

        $this->registerWemallService();
        $this->app->request->withPost([
            'phone' => $childUser->getAttr('phone'),
            'fphone' => $parentUser->getAttr('phone'),
        ]);
        $childAccount->bind(['id' => $childUser->getAttr('id')], ['phone' => $childUser->getAttr('phone')]);

        $relation = PluginWemallUserRelation::mk()->where(['unid' => $childUser->getAttr('id')])->findOrEmpty();

        $this->assertSame($parentUser->getAttr('id'), intval($relation->getAttr('puid1')));
        $this->assertSame(0, intval($relation->getAttr('puids')));
        $this->assertSame(',' . $parentUser->getAttr('id') . ',', $relation->getAttr('path'));
        $this->assertSame(2, intval($relation->getAttr('layer')));
    }

    public function testEntryPublishesAgentCreateWhenUserHasAgentPrivilege(): void
    {
        $user = $this->createAccountUser();
        $relation = $this->createWemallRelationFixture($user->getAttr('id'));
        PluginWemallUserCreate::mk()->save([
            'unid' => $user->getAttr('id'),
            'status' => 1,
            'delete_time' => null,
            'agent_entry' => 1,
            'phone' => $user->getAttr('phone'),
            'name' => $user->getAttr('nickname'),
        ]);

        $events = [];
        $this->app->event->listen('PluginWemallAgentCreate', static function (PluginWemallUserRelation $payload) use (&$events) {
            $events[] = ['event' => 'create', 'unid' => intval($payload->getAttr('unid'))];
        });

        $result = UserOrder::entry($relation)->refresh();

        $this->assertSame(1, intval($result->getAttr('entry_agent')));
        $this->assertSame(0, intval($result->getAttr('entry_member')));
        $this->assertSame([['event' => 'create', 'unid' => $user->getAttr('id')]], $events);
    }

    public function testEntryPublishesAgentCancelWhenUserHasNoAgentPrivilege(): void
    {
        $user = $this->createAccountUser();
        $relation = $this->createWemallRelationFixture($user->getAttr('id'), [
            'entry_agent' => 1,
            'entry_member' => 1,
        ]);

        $events = [];
        $this->app->event->listen('PluginWemallAgentCancel', static function (PluginWemallUserRelation $payload) use (&$events) {
            $events[] = ['event' => 'cancel', 'unid' => intval($payload->getAttr('unid'))];
        });

        $result = UserOrder::entry($relation)->refresh();

        $this->assertSame(0, intval($result->getAttr('entry_agent')));
        $this->assertSame(0, intval($result->getAttr('entry_member')));
        $this->assertSame([['event' => 'cancel', 'unid' => $user->getAttr('id')]], $events);
    }

    public function testPerfectUpdatesOrderAndPublishesPerfectEvent(): void
    {
        $account = $this->createBoundAccountFixture();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-PERFECT-001',
            'status' => 1,
            'amount_goods' => '10.00',
            'amount_discount' => '9.00',
            'amount_reduct' => '1.00',
            'amount_express' => '0.00',
            'amount_real' => '8.00',
            'amount_total' => '10.00',
        ]);
        $this->createWemallOrderItemFixture($account, $order->getAttr('order_no'), [
            'delivery_code' => 'NONE',
            'delivery_count' => 2,
        ]);
        $address = $this->createPaymentAddressFixture($account->getUnid(), [
            'user_name' => '张三',
            'user_phone' => '13800138000',
            'region_prov' => '广东省',
            'region_city' => '深圳市',
            'region_area' => '南山区',
            'region_addr' => '科苑路 8 号',
        ]);

        $events = [];
        $this->app->event->listen('PluginWemallOrderPerfect', static function ($payload) use (&$events) {
            $events[] = $payload->getAttr('order_no');
        });

        $result = UserOrder::perfect($order, $address);
        $order = $order->refresh();
        $sender = PluginWemallOrderSender::mk()->where(['order_no' => $order->getAttr('order_no')])->findOrEmpty();

        $this->assertTrue($result);
        $this->assertSame(['WEMALL-PERFECT-001'], $events);
        $this->assertSame(2, intval($order->getAttr('status')));
        $this->assertSame('0.00', $this->decimal($order->getAttr('amount_express')));
        $this->assertSame('8.00', $this->decimal($order->getAttr('amount_real')));
        $this->assertSame('10.00', $this->decimal($order->getAttr('amount_total')));
        $this->assertTrue($sender->isExists());
        $this->assertSame('张三', $sender->getAttr('user_name'));
        $this->assertSame('13800138000', $sender->getAttr('user_phone'));
        $this->assertSame('广东省', $sender->getAttr('region_prov'));
        $this->assertSame('深圳市', $sender->getAttr('region_city'));
        $this->assertSame('南山区', $sender->getAttr('region_area'));
        $this->assertSame('科苑路 8 号', $sender->getAttr('region_addr'));
        $this->assertSame('无需运费', $sender->getAttr('delivery_remark'));
        $this->assertSame(2, intval($sender->getAttr('delivery_count')));
        $this->assertSame('0.00', $this->decimal($sender->getAttr('delivery_amount')));
    }

    public function testRemoveControllerMarksOrderDeletedAndPublishesRemoveEvent(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->createWemallRelationFixture($account->getUnid());
        $login = $account->token()->get(true);
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'WEMALL-REMOVE-001',
            'status' => 0,
            'deleted_status' => 0,
        ]);

        $events = [];
        $this->app->event->listen('PluginWemallOrderRemove', static function (PluginWemallOrder $payload) use (&$events) {
            $events[] = $payload->getAttr('order_no');
        });

        $response = $this->callOrderController('remove', [
            'order_no' => $order->getAttr('order_no'),
        ], strval($login['token'] ?? ''));

        $order = PluginWemallOrder::mk()->withTrashed()->findOrEmpty($order->getKey());

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('删除成功！', $response['info'] ?? '');
        $this->assertSame(['WEMALL-REMOVE-001'], $events);
        $this->assertSame(0, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('deleted_status')));
        $this->assertSame('用户主动删除订单！', $order->getAttr('deleted_remark'));
        $this->assertNotEmpty($order->getAttr('delete_time'));
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentBalanceTable();
        $this->createPaymentIntegralTable();
        $this->createPaymentRecordTable();
        $this->createPaymentRefundTable();
        $this->createPaymentAddressTable();
        $this->createWemallOrderTable();
        $this->createWemallOrderItemTable();
        $this->createWemallOrderSenderTable();
        $this->createWemallConfigLevelTable();
        $this->createWemallConfigAgentTable();
        $this->createWemallUserCreateTable();
        $this->createWemallUserRelationTable();
        $this->createWemallUserRebateTable();
        $this->createWemallUserTransferTable();
    }

    private function registerWemallService(): void
    {
        (new WemallService($this->app))->register();
    }

    private function callOrderController(string $action, array $post, string $token): array
    {
        $request = $this->app->request
            ->withGet($post)
            ->withPost($post)
            ->withHeader(['authorization' => "Bearer {$token}"])
            ->setController('api.auth.order')
            ->setAction($action);

        $this->app->instance('request', $request);

        try {
            $controller = new AuthOrderController($this->app);
            $controller->{$action}();
            self::fail("Expected {$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }
}
