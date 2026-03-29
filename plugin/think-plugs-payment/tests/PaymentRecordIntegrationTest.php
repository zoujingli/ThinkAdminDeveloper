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

use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\model\PluginPaymentRefund;
use plugin\payment\service\Payment;
use think\admin\Exception;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class PaymentRecordIntegrationTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/payment-account.png',
            'userPrefix' => '支付账号',
        ]);
    }

    public function testEmptyPaymentCreatesPaidRecordAndSupportsRefundSummary(): void
    {
        $account = $this->createBoundAccountFixture();
        $response = Payment::mk(Payment::EMPTY)->create(
            $account,
            'ORDER-EMPTY-001',
            '空支付订单',
            '10.00',
            '10.00',
            '无需第三方支付'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $this->assertTrue($response->status);
        $this->assertTrue($record->isExists());
        $this->assertSame(Payment::EMPTY, $record->getAttr('channel_type'));
        $this->assertSame(1, intval($record->getAttr('payment_status')));
        $this->assertSame(2, intval($record->getAttr('audit_status')));
        $this->assertNotEmpty($record->getAttr('payment_trade'));
        $this->assertSame('10.00', $this->decimal(Payment::paidAmount('ORDER-EMPTY-001')));
        $this->assertSame('10.00', Payment::leaveAmount('ORDER-EMPTY-001', '20.00'));

        $total = Payment::totalPaymentAmount('ORDER-EMPTY-001');
        $this->assertSame('10.00', $this->decimal($total['amount']));
        $this->assertSame('10.00', $this->decimal($total['payment']));
        $this->assertSame('0.00', $this->decimal($total['balance']));
        $this->assertSame('0.00', $this->decimal($total['integral']));

        [$status, $message] = Payment::mk(Payment::EMPTY)->refund($record->getAttr('code'), '4.00', '部分退款');
        $refund = PluginPaymentRefund::mk()->where(['record_code' => $record->getAttr('code')])->findOrEmpty();
        $refundTotal = Payment::totalRefundAmount($record->getAttr('code'));

        $this->assertSame([1, '发起退款成功！'], [$status, $message]);
        $this->assertTrue($refund->isExists());
        $this->assertSame(Payment::EMPTY, $refund->getAttr('refund_account'));
        $this->assertSame(1, intval($refund->getAttr('refund_status')));
        $this->assertSame('4.00', $this->decimal($refund->getAttr('refund_amount')));
        $this->assertSame('4.00', $this->decimal($refundTotal['amount']));
        $this->assertSame('4.00', $this->decimal($refundTotal['payment']));
        $this->assertSame('6.00', $this->decimal(Payment::paidAmount('ORDER-EMPTY-001', true)));
    }

    public function testVoucherPaymentCreatesPendingAuditRecordAndBlocksDuplicatePendingOrder(): void
    {
        $account = $this->createBoundAccountFixture('web');
        $response = Payment::mk(Payment::VOUCHER)->create(
            $account,
            'ORDER-VOUCHER-001',
            '凭证支付订单',
            '15.00',
            '8.00',
            '上传转账凭证',
            '',
            'https://example.com/voucher.png'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $this->assertTrue($response->status);
        $this->assertTrue($record->isExists());
        $this->assertSame(Payment::VOUCHER, $record->getAttr('channel_type'));
        $this->assertSame(1, intval($record->getAttr('audit_status')));
        $this->assertSame(0, intval($record->getAttr('payment_status')));
        $this->assertSame('8.00', $this->decimal($record->getAttr('payment_amount')));
        $this->assertSame('https://example.com/voucher.png', $record->getAttr('payment_images'));
        $this->assertSame('0.00', $this->decimal(Payment::paidAmount('ORDER-VOUCHER-001')));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('凭证待审核');
        Payment::mk(Payment::VOUCHER)->create(
            $account,
            'ORDER-VOUCHER-001',
            '凭证支付订单',
            '15.00',
            '2.00',
            '重复提交凭证',
            '',
            'https://example.com/voucher-2.png'
        );
    }

    public function testRefundAcceptsCustomCodeAndRejectsDuplicateCustomCode(): void
    {
        $first = $this->createPaidEmptyOrderFixture('ORDER-REFUND-CODE-1');
        $second = $this->createPaidEmptyOrderFixture('ORDER-REFUND-CODE-2');
        $customCode = 'R-CUSTOM-0001';

        [$status, $message] = Payment::mk(Payment::EMPTY)->refund($first->getAttr('code'), '2.00', '首次退款', $customCode);
        $refund = PluginPaymentRefund::mk()->where(['code' => $customCode])->findOrEmpty();

        $this->assertSame([1, '发起退款成功！'], [$status, $message]);
        $this->assertTrue($refund->isExists());
        $this->assertSame($first->getAttr('code'), $refund->getAttr('record_code'));

        $duplicateCode = $customCode;
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('退款单已存在');
        Payment::mk(Payment::EMPTY)->refund($second->getAttr('code'), '1.00', '重复退款单号', $duplicateCode);
    }

    public function testRefundRejectsOverflowAndDoesNotCreateExtraRefundRecord(): void
    {
        $record = $this->createPaidEmptyOrderFixture('ORDER-REFUND-OVERFLOW');
        Payment::mk(Payment::EMPTY)->refund($record->getAttr('code'), '8.00', '首次退款');

        $this->assertSame(1, PluginPaymentRefund::mk()->where(['record_code' => $record->getAttr('code')])->count());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('退款金额溢出');
        try {
            Payment::mk(Payment::EMPTY)->refund($record->getAttr('code'), '3.00', '超额退款');
        } finally {
            $this->assertSame(1, PluginPaymentRefund::mk()->where(['record_code' => $record->getAttr('code')])->count());
            $this->assertSame('8.00', $this->decimal(Payment::totalRefundAmount($record->getAttr('code'))['amount']));
        }
    }

    public function testEmptyPaymentReturnsEnglishMessagesWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $this->switchPaymentLang('en-us');

        $response = Payment::mk(Payment::EMPTY)->create(
            $account,
            'ORDER-EMPTY-EN-001',
            'English empty order',
            '10.00',
            '10.00',
            'No gateway required'
        );

        [$status, $message] = Payment::mk(Payment::EMPTY)->refund($response->record['code'], '4.00', 'English refund');

        $this->assertTrue($response->status);
        $this->assertSame('No payment is required for this order', $response->message);
        $this->assertSame([1, 'Refund requested successfully'], [$status, $message]);
    }

    public function testVoucherCreateReturnsEnglishPendingAuditMessageWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture('web');
        $this->switchPaymentLang('en-us');

        Payment::mk(Payment::VOUCHER)->create(
            $account,
            'ORDER-VOUCHER-EN-001',
            'Voucher payment order',
            '15.00',
            '8.00',
            'Upload voucher',
            '',
            'https://example.com/voucher-en.png'
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Voucher pending review');
        Payment::mk(Payment::VOUCHER)->create(
            $account,
            'ORDER-VOUCHER-EN-001',
            'Voucher payment order',
            '15.00',
            '2.00',
            'Duplicate voucher',
            '',
            'https://example.com/voucher-en-2.png'
        );
    }

    public function testRefundRejectsOverflowWithEnglishMessageWhenLangSetIsEnUs(): void
    {
        $record = $this->createPaidEmptyOrderFixture('ORDER-REFUND-OVERFLOW-EN');
        $this->switchPaymentLang('en-us');
        Payment::mk(Payment::EMPTY)->refund($record->getAttr('code'), '8.00', 'First refund');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Refund amount exceeds the paid amount');
        Payment::mk(Payment::EMPTY)->refund($record->getAttr('code'), '3.00', 'Overflow refund');
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentRecordTable();
        $this->createPaymentRefundTable();
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
