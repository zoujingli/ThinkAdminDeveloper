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

use plugin\payment\controller\Record as PaymentRecordController;
use plugin\payment\controller\Refund as PaymentRefundController;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\model\PluginPaymentRefund;
use plugin\payment\service\Payment;
use plugin\wemall\Service as WemallService;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

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

    protected function afterSchemaCreated(): void
    {
        $this->app->setAppPath(TEST_PROJECT_ROOT . '/plugin/think-plugs-payment/src/');
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-payment/src/view' . DIRECTORY_SEPARATOR,
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

    public function testAuditGetRendersBuilderForm(): void
    {
        $record = PluginPaymentRecord::mk();
        $record->save([
            'unid' => 1,
            'usid' => 1,
            'code' => 'PAYAUDITHTML001',
            'order_no' => 'PAY-AUDIT-HTML-001',
            'order_name' => '后台审核表单',
            'order_amount' => '5.00',
            'channel_type' => Payment::VOUCHER,
            'channel_code' => Payment::VOUCHER,
            'payment_images' => 'https://example.com/payment-audit.png',
            'payment_status' => 0,
            'payment_amount' => '5.00',
            'used_payment' => '5.00',
            'audit_status' => 1,
        ]);

        $html = $this->callRecordHtml('audit', ['id' => intval($record->getAttr('id'))], 'GET');

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('data-tips-image', $html);
    }

    public function testRecordIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchPaymentLang('en-us');

        $html = $this->callActionHtml(PaymentRecordController::class, 'index');

        $this->assertStringContainsString('Payment Activity Management', $html);
        $this->assertStringContainsString('User Account', $html);
        $this->assertStringContainsString('Order Content', $html);
        $this->assertStringContainsString('Payment Description', $html);
        $this->assertStringContainsString('Search', $html);
        $this->assertStringContainsString('Export', $html);
        $this->assertStringContainsString('Payment Behavior Data', $html);
        $this->assertStringNotContainsString('支付行为管理', $html);
    }

    public function testAuditGetRendersEnglishBuilderFormWhenLangSetIsEnUs(): void
    {
        $record = PluginPaymentRecord::mk();
        $record->save([
            'unid' => 1,
            'usid' => 1,
            'code' => 'PAYAUDITHTMLEN001',
            'order_no' => 'PAY-AUDIT-HTML-EN-001',
            'order_name' => '英文审核表单',
            'order_amount' => '8.00',
            'channel_type' => Payment::VOUCHER,
            'channel_code' => Payment::VOUCHER,
            'payment_images' => 'https://example.com/payment-audit-en.png',
            'payment_status' => 0,
            'payment_amount' => '8.00',
            'used_payment' => '8.00',
            'audit_status' => 1,
        ]);

        $this->switchPaymentLang('en-us');
        $html = $this->callRecordHtml('audit', ['id' => intval($record->getAttr('id'))], 'GET');

        $this->assertStringContainsString('Business Order No.', $html);
        $this->assertStringContainsString('Audit Action Type', $html);
        $this->assertStringContainsString('Order Audit Remark', $html);
        $this->assertStringContainsString('Payment Voucher', $html);
        $this->assertStringNotContainsString('审核操作类型', $html);
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

    public function testRefundIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $user = $this->createAccountUser([
            'username' => 'refund-english-user',
            'nickname' => '退款英文用户',
        ]);

        $record = PluginPaymentRecord::mk();
        $record->save([
            'unid' => intval($user->getAttr('id')),
            'usid' => 1,
            'code' => 'PAYREFUNDHTML001',
            'order_no' => 'PAY-REFUND-HTML-001',
            'order_name' => '退款页面订单',
            'order_amount' => '12.00',
            'channel_type' => Payment::VOUCHER,
            'channel_code' => Payment::VOUCHER,
            'payment_trade' => 'TRADE-REFUND-001',
            'payment_status' => 1,
            'payment_amount' => '12.00',
            'used_payment' => '12.00',
            'audit_status' => 2,
            'payment_time' => date('Y-m-d H:i:s'),
            'payment_remark' => '退款前已支付',
        ]);

        $refund = PluginPaymentRefund::mk();
        $refund->save([
            'unid' => intval($user->getAttr('id')),
            'record_code' => 'PAYREFUNDHTML001',
            'code' => 'RFD-HTML-001',
            'refund_account' => Payment::EMPTY,
            'refund_amount' => '12.00',
            'used_payment' => '12.00',
            'refund_remark' => '全额退款',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);

        $this->switchPaymentLang('en-us');
        $html = $this->callActionHtml(PaymentRefundController::class, 'index');

        $this->assertStringContainsString('Payment Refund Management', $html);
        $this->assertStringContainsString('Refund Content', $html);
        $this->assertStringContainsString('Payment Description', $html);
        $this->assertStringContainsString('Search', $html);
        $this->assertStringContainsString('Refund Data', $html);
        $this->assertStringNotContainsString('支付退款管理', $html);
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

    private function callRecordHtml(string $action, array $data, string $method = 'GET'): string
    {
        RequestContext::clear();
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

        $this->activateApplicationContext($request);

        try {
            $controller = new PaymentRecordController($this->app);
            $controller->{$action}();
            self::fail("Expected {$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callRecordController(string $action, array $data, string $method = 'POST'): array
    {
        RequestContext::clear();
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

        $this->activateApplicationContext($request);

        try {
            $controller = new PaymentRecordController($this->app);
            $controller->{$action}();
            self::fail("Expected {$action} to throw HttpResponseException.");
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
}
