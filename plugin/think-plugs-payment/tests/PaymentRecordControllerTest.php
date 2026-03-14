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

use plugin\payment\controller\Record as PaymentRecordController;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\model\PluginPaymentRefund;
use plugin\payment\service\Payment;
use plugin\wemall\Service as WemallService;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;

/**
 * @internal
 * @coversNothing
 */
class PaymentRecordControllerTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/payment-controller.png',
            'userPrefix' => '支付测试',
        ]);
    }

    public function testAuditControllerApprovesVoucherAndPromotesWemallOrder(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'PAY-AUDIT-PASS-001',
            'amount_real' => '15.00',
            'amount_total' => '15.00',
            'delivery_type' => 1,
        ]);

        $response = Payment::mk(Payment::VOUCHER)->create(
            $account,
            $order->getAttr('order_no'),
            '后台审核通过订单',
            '15.00',
            '15.00',
            '后台审核通过',
            '',
            'https://example.com/voucher-pass.png'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $this->assertSame(3, intval($order->refresh()->getAttr('status')));
        $this->assertSame(1, intval($order->refresh()->getAttr('payment_status')));

        $result = $this->callAuditController([
            'id' => $record->getAttr('id'),
            'status' => 2,
            'remark' => '',
        ]);

        $record = $record->refresh();
        $order = $order->refresh();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('凭证审核通过！', $result['info'] ?? '');
        $this->assertSame(2, intval($record->getAttr('audit_status')));
        $this->assertSame(1, intval($record->getAttr('payment_status')));
        $this->assertSame(9001, intval($record->getAttr('audit_user')));
        $this->assertSame('后台支付凭证已通过', $record->getAttr('payment_remark'));
        $this->assertNotEmpty($record->getAttr('payment_trade'));
        $this->assertNotEmpty($record->getAttr('payment_time'));
        $this->assertSame(4, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
        $this->assertSame('15.00', $this->decimal($order->getAttr('payment_amount')));
    }

    public function testAuditControllerRefusesVoucherAndReturnsWemallOrderToPayable(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'PAY-AUDIT-REFUSE-001',
            'amount_real' => '9.00',
            'amount_total' => '9.00',
            'delivery_type' => 1,
        ]);

        $response = Payment::mk(Payment::VOUCHER)->create(
            $account,
            $order->getAttr('order_no'),
            '后台审核驳回订单',
            '9.00',
            '9.00',
            '后台审核驳回',
            '',
            'https://example.com/voucher-refuse.png'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $this->assertSame(3, intval($order->refresh()->getAttr('status')));
        $this->assertSame(1, intval($order->refresh()->getAttr('payment_status')));

        $result = $this->callAuditController([
            'id' => $record->getAttr('id'),
            'status' => 0,
            'remark' => '凭证信息不足',
        ]);

        $record = $record->refresh();
        $order = $order->refresh();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('凭证审核驳回！', $result['info'] ?? '');
        $this->assertSame(0, intval($record->getAttr('audit_status')));
        $this->assertSame(0, intval($record->getAttr('payment_status')));
        $this->assertSame(9001, intval($record->getAttr('audit_user')));
        $this->assertSame('凭证信息不足', $record->getAttr('payment_remark'));
        $this->assertSame('凭证信息不足', $record->getAttr('audit_remark'));
        $this->assertSame(2, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
        $this->assertSame('0.00', $this->decimal($order->getAttr('payment_amount')));
    }

    public function testNotifyControllerReplaysSuccessEventForPaidRecord(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'PAY-NOTIFY-OK-001',
            'status' => 2,
            'payment_status' => 0,
            'amount_real' => '11.00',
            'amount_total' => '11.00',
            'delivery_type' => 1,
        ]);

        $record = PluginPaymentRecord::mk();
        $record->save([
            'unid' => $account->getUnid(),
            'usid' => $account->getUsid(),
            'code' => 'PAYNOTIFYOK001',
            'order_no' => $order->getAttr('order_no'),
            'order_name' => '后台重放成功订单',
            'order_amount' => '11.00',
            'channel_type' => Payment::EMPTY,
            'channel_code' => Payment::EMPTY,
            'payment_trade' => 'EMT-NOTIFY-001',
            'payment_status' => 1,
            'payment_amount' => '11.00',
            'used_payment' => '11.00',
            'audit_status' => 2,
            'payment_time' => date('Y-m-d H:i:s'),
            'payment_remark' => '已完成支付待重放',
        ]);

        $response = $this->callRecordController('notify', [
            'code' => $record->getAttr('code'),
        ], 'GET');

        $order = $order->refresh();

        $this->assertSame(1, intval($response['code'] ?? 0));
        $this->assertSame('重新触发支付行为！', $response['info'] ?? '');
        $this->assertSame(4, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
        $this->assertSame('11.00', $this->decimal($order->getAttr('payment_amount')));
        $this->assertSame('11.00', $this->decimal($order->getAttr('amount_payment')));
    }

    public function testNotifyControllerRejectsUnpaidRecord(): void
    {
        $record = PluginPaymentRecord::mk();
        $record->save([
            'unid' => 1,
            'usid' => 1,
            'code' => 'PAYNOTIFYFAIL001',
            'order_no' => 'PAY-NOTIFY-FAIL-001',
            'order_name' => '后台重放未支付订单',
            'order_amount' => '6.00',
            'channel_type' => Payment::VOUCHER,
            'channel_code' => Payment::VOUCHER,
            'payment_status' => 0,
            'payment_amount' => '0.00',
            'used_payment' => '6.00',
            'audit_status' => 1,
        ]);

        $response = $this->callRecordController('notify', [
            'code' => $record->getAttr('code'),
        ], 'GET');

        $this->assertSame(0, intval($response['code'] ?? 1));
        $this->assertSame('未完成支付！', $response['info'] ?? '');
    }

    public function testCancelControllerRefundsCouponAdjustedAmountAndRefreshesOrder(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->registerWemallService();
        $order = $this->createWemallOrderFixture($account, [
            'order_no' => 'PAY-CANCEL-001',
            'amount_real' => '11.00',
            'amount_total' => '11.00',
            'delivery_type' => 1,
        ]);

        $response = Payment::mk(Payment::EMPTY)->create(
            $account,
            $order->getAttr('order_no'),
            '后台退款订单',
            '11.00',
            '11.00',
            '后台退款前支付'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $record->save(['payment_coupon' => '2.00']);

        $result = $this->callRecordController('cancel', [
            'code' => $record->getAttr('code'),
        ], 'GET');

        $record = $record->refresh();
        $order = $order->refresh();
        $refund = PluginPaymentRefund::mk()->where(['record_code' => $record->getAttr('code')])->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('退款申请成功！', $result['info'] ?? '');
        $this->assertTrue($refund->isExists());
        $this->assertSame('9.00', $this->decimal($refund->getAttr('refund_amount')));
        $this->assertSame('9.00', $this->decimal($refund->getAttr('used_payment')));
        $this->assertSame(Payment::EMPTY, $refund->getAttr('refund_account'));
        $this->assertSame(1, intval($record->getAttr('refund_status')));
        $this->assertSame('9.00', $this->decimal($record->getAttr('refund_amount')));
        $this->assertSame('2.00', $this->decimal($order->getAttr('payment_amount')));
        $this->assertSame('2.00', $this->decimal($order->getAttr('amount_payment')));
        $this->assertSame(4, intval($order->getAttr('status')));
        $this->assertSame(1, intval($order->getAttr('payment_status')));
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentRecordTable();
        $this->createPaymentRefundTable();
        $this->createWemallOrderTable();
    }

    private function registerWemallService(): void
    {
        (new WemallService($this->app))->register();
    }

    private function callAuditController(array $post): array
    {
        return $this->callRecordController('audit', $post, 'POST');
    }

    private function callRecordController(string $action, array $data, string $method = 'POST'): array
    {
        RequestContext::instance()->setAuth([
            'id' => 9001,
            'username' => 'admin',
            'password' => 'test-admin-password',
        ], '', true);

        $request = $this->app->request
            ->withGet($data)
            ->withPost($data)
            ->setMethod($method)
            ->setController('record')
            ->setAction($action);

        $this->app->instance('request', $request);

        try {
            $controller = new PaymentRecordController($this->app);
            $controller->{$action}();
            self::fail("Expected {$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }
}
