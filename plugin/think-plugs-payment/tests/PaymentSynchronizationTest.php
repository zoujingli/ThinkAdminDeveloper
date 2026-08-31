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

use PHPUnit\Framework\TestCase;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\Payment;
use plugin\payment\service\payment\CouponPayment;
use plugin\payment\service\payment\wechat\WechatPaymentV2;
use think\admin\Exception;
use think\admin\Library;
use think\admin\tests\support\TestDatabase;
use think\facade\Db;

/**
 * @internal
 * @coversNothing
 */
class PaymentSynchronizationTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabase::reset();
    }

    public function testWechatV2QueryPersistsTransactionAndCashFee(): void
    {
        Db::table('plugin_payment_record')->insert([
            'code' => 'PAYMENT-QUERY',
            'order_no' => 'ORDER-QUERY',
            'channel_code' => 'wechat-test',
            'channel_type' => Payment::WECHAT_XCX,
            'payment_status' => 0,
        ]);

        $payment = new WechatPaymentV2(Library::$sapp, 'wechat-test', Payment::WECHAT_XCX, [
            'wechat_appid' => 'test-appid',
            'wechat_mch_id' => 'test-merchant',
            'wechat_mch_key' => 'test-secret',
        ]);
        $wechat = new class {
            public function query(array $options): array
            {
                return [
                    'return_code' => 'SUCCESS',
                    'result_code' => 'SUCCESS',
                    'attach' => 'wechat-test',
                    'out_trade_no' => $options['out_trade_no'],
                    'cash_fee' => 1234,
                    'transaction_id' => 'WECHAT-TRANSACTION',
                ];
            }
        };
        $property = new \ReflectionProperty(WechatPaymentV2::class, 'payment');
        $property->setValue($payment, $wechat);

        $payment->query('PAYMENT-QUERY');

        $record = PluginPaymentRecord::mk()->where(['code' => 'PAYMENT-QUERY'])->findOrEmpty();
        $this->assertSame('WECHAT-TRANSACTION', $record->getAttr('payment_trade'));
        $this->assertSame('12.34', strval($record->getAttr('payment_amount')));
    }

    public function testRefundSynchronizationRejectsDuplicateRefundCode(): void
    {
        $this->seedCompletedPayment('PAYMENT-REFUND');
        Db::table('plugin_payment_refund')->insert([
            'code' => 'REFUND-DUPLICATE',
            'record_code' => 'ANOTHER-PAYMENT',
            'refund_status' => 1,
            'refund_amount' => '1.00',
        ]);
        $refundCode = 'REFUND-DUPLICATE';

        $this->expectException(Exception::class);
        $this->expectExceptionCode(2);
        $this->expectExceptionMessage('退款单已存在！');

        CouponPayment::syncRefund('PAYMENT-REFUND', $refundCode, '10.00', 'duplicate test');
    }

    public function testRefundCodeIsUniqueAcrossPaymentRecords(): void
    {
        Db::table('plugin_payment_refund')->insert([
            'code' => 'REFUND-GLOBAL-UNIQUE',
            'record_code' => 'PAYMENT-FIRST',
            'refund_amount' => '1.00',
        ]);

        try {
            Db::table('plugin_payment_refund')->insert([
                'code' => 'REFUND-GLOBAL-UNIQUE',
                'record_code' => 'PAYMENT-SECOND',
                'refund_amount' => '1.00',
            ]);
            self::fail('Refund codes must be unique across payment records.');
        } catch (\Throwable $exception) {
            $this->assertStringContainsString('UNIQUE', strtoupper($exception->getMessage()));
        }

        $this->assertSame(1, Db::table('plugin_payment_refund')->where(['code' => 'REFUND-GLOBAL-UNIQUE'])->count());
    }

    public function testRefundRollsBackWhenCancellationListenerFails(): void
    {
        $this->seedCompletedPayment('PAYMENT-ROLLBACK');
        $refundCode = 'REFUND-ROLLBACK';
        Library::$sapp->event->listen('PluginPaymentCancel', static function (PluginPaymentRecord $record) {
            if ($record->getAttr('code') === 'PAYMENT-ROLLBACK') {
                throw new \RuntimeException('cancel listener failed');
            }
        }, true);

        try {
            Payment::mk(Payment::COUPON)->refund('PAYMENT-ROLLBACK', '10.00', 'rollback test', $refundCode);
            self::fail('Refund should fail when its cancellation listener fails.');
        } catch (Exception $exception) {
            $this->assertSame('cancel listener failed', $exception->getMessage());
        }

        $record = PluginPaymentRecord::mk()->where(['code' => 'PAYMENT-ROLLBACK'])->findOrEmpty();
        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['code' => 'REFUND-ROLLBACK'])->count());
        $this->assertSame(0, bccomp(strval($record->getAttr('refund_amount')), '0.00', 2));
        $this->assertSame(0, (int)$record->getAttr('refund_status'));
    }

    public function testRefundSynchronizationKeepsDifferentRefundCodesDistinct(): void
    {
        $this->seedCompletedPayment('PAYMENT-PARTIAL-REFUNDS');
        $firstRefundCode = 'REFUND-FIRST';
        $secondRefundCode = 'REFUND-SECOND';

        CouponPayment::syncRefund('PAYMENT-PARTIAL-REFUNDS', $firstRefundCode, '10.00', 'first partial refund');
        CouponPayment::syncRefund('PAYMENT-PARTIAL-REFUNDS', $secondRefundCode, '15.00', 'second partial refund');

        $refundCodes = Db::table('plugin_payment_refund')
            ->where(['record_code' => 'PAYMENT-PARTIAL-REFUNDS'])
            ->order('id asc')
            ->column('code');
        $this->assertSame(['REFUND-FIRST', 'REFUND-SECOND'], $refundCodes);
    }

    public function testRefundRejectsAmountGreaterThanRemainingPayment(): void
    {
        $this->seedCompletedPayment('PAYMENT-OVER-REFUND');
        $refundCode = 'REFUND-OVER-LIMIT';

        try {
            Payment::mk(Payment::COUPON)->refund('PAYMENT-OVER-REFUND', '100.01', 'over-refund test', $refundCode);
            self::fail('Refund should reject an amount greater than the remaining payment.');
        } catch (Exception $exception) {
            $this->assertSame('退款金额超出可退金额！', $exception->getMessage());
        }

        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['record_code' => 'PAYMENT-OVER-REFUND'])->count());
    }

    public function testCouponRefundIgnoresNonPositiveAmount(): void
    {
        $this->seedCompletedPayment('PAYMENT-NON-POSITIVE-REFUND');
        $refundCode = 'REFUND-NON-POSITIVE';

        [$status, $message] = Payment::mk(Payment::COUPON)->refund('PAYMENT-NON-POSITIVE-REFUND', '-1.00', 'negative refund test', $refundCode);

        $this->assertSame(1, $status);
        $this->assertSame('无需退款！', $message);
        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['record_code' => 'PAYMENT-NON-POSITIVE-REFUND'])->count());
    }

    public function testRefundRejectsMalformedAmount(): void
    {
        $this->seedCompletedPayment('PAYMENT-MALFORMED-REFUND');
        $refundCode = 'REFUND-MALFORMED';

        try {
            Payment::mk(Payment::COUPON)->refund('PAYMENT-MALFORMED-REFUND', 'abc', 'malformed refund test', $refundCode);
            self::fail('Refund should reject a malformed amount.');
        } catch (Exception $exception) {
            $this->assertSame('退款金额格式无效！', $exception->getMessage());
        }

        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['record_code' => 'PAYMENT-MALFORMED-REFUND'])->count());
    }

    public function testRefundRejectsAmountWithMoreThanTwoDecimalPlaces(): void
    {
        $this->seedCompletedPayment('PAYMENT-SUBCENT-REFUND');
        $refundCode = 'REFUND-SUBCENT';

        try {
            Payment::mk(Payment::COUPON)->refund('PAYMENT-SUBCENT-REFUND', '100.009', 'sub-cent refund test', $refundCode);
            self::fail('Refund should reject an amount with more than two decimal places.');
        } catch (Exception $exception) {
            $this->assertSame('退款金额格式无效！', $exception->getMessage());
        }

        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['record_code' => 'PAYMENT-SUBCENT-REFUND'])->count());
    }

    public function testBalanceRefundRollsBackWhenBalanceLedgerFails(): void
    {
        $this->seedRefundAccount();
        $this->seedCompletedPayment('PAYMENT-BALANCE-ROLLBACK');
        Db::execute("CREATE TRIGGER fail_balance_refund BEFORE INSERT ON plugin_payment_balance BEGIN SELECT RAISE(ABORT, 'balance refund failed'); END");
        $refundCode = 'REFUND-BALANCE-ROLLBACK';

        try {
            Payment::mk(Payment::BALANCE)->refund('PAYMENT-BALANCE-ROLLBACK', '10.00', 'balance rollback test', $refundCode);
            self::fail('Balance refund should fail when its ledger write fails.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('balance refund failed', $exception->getMessage());
        } finally {
            Db::execute('DROP TRIGGER fail_balance_refund');
        }

        $record = PluginPaymentRecord::mk()->where(['code' => 'PAYMENT-BALANCE-ROLLBACK'])->findOrEmpty();
        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['code' => $refundCode])->count());
        $this->assertSame(0, Db::table('plugin_payment_balance')->where(['code' => $refundCode])->count());
        $this->assertSame(0, bccomp(strval($record->getAttr('refund_amount')), '0.00', 2));
    }

    public function testIntegralRefundRollsBackWhenIntegralLedgerFails(): void
    {
        $this->seedRefundAccount();
        $this->seedCompletedPayment('PAYMENT-INTEGRAL-ROLLBACK', Payment::INTEGRAL, ['used_integral' => '100.00']);
        Db::execute("CREATE TRIGGER fail_integral_refund BEFORE INSERT ON plugin_payment_integral BEGIN SELECT RAISE(ABORT, 'integral refund failed'); END");
        $refundCode = 'REFUND-INTEGRAL-ROLLBACK';

        try {
            Payment::mk(Payment::INTEGRAL)->refund('PAYMENT-INTEGRAL-ROLLBACK', '10.00', 'integral rollback test', $refundCode);
            self::fail('Integral refund should fail when its ledger write fails.');
        } catch (Exception $exception) {
            $this->assertStringContainsString('integral refund failed', $exception->getMessage());
        } finally {
            Db::execute('DROP TRIGGER fail_integral_refund');
        }

        $record = PluginPaymentRecord::mk()->where(['code' => 'PAYMENT-INTEGRAL-ROLLBACK'])->findOrEmpty();
        $this->assertSame(0, Db::table('plugin_payment_refund')->where(['code' => $refundCode])->count());
        $this->assertSame(0, Db::table('plugin_payment_integral')->where(['code' => $refundCode])->count());
        $this->assertSame(0, bccomp(strval($record->getAttr('refund_amount')), '0.00', 2));
    }

    public function testRefundSynchronizationPersistsTotalsForCompletedNotification(): void
    {
        $this->seedCompletedPayment('PAYMENT-REFUND-NOTIFY');
        Db::table('plugin_payment_refund')->insert([
            'code' => 'REFUND-NOTIFY',
            'record_code' => 'PAYMENT-REFUND-NOTIFY',
            'refund_status' => 1,
            'refund_amount' => '10.00',
            'refund_account' => Payment::COUPON,
            'used_payment' => '10.00',
        ]);
        $refundCode = null;

        CouponPayment::syncRefund('PAYMENT-REFUND-NOTIFY', $refundCode);

        $record = PluginPaymentRecord::mk()->where(['code' => 'PAYMENT-REFUND-NOTIFY'])->findOrEmpty();
        $this->assertSame(0, bccomp(strval($record->getAttr('refund_amount')), '10.00', 2));
        $this->assertSame(0, bccomp(strval($record->getAttr('refund_payment')), '10.00', 2));
        $this->assertSame(1, (int)$record->getAttr('refund_status'));
    }

    public function testRefundSynchronizationGeneratesCodeWhenOmitted(): void
    {
        $this->seedCompletedPayment('PAYMENT-GENERATED-REFUND');
        $refundCode = null;

        CouponPayment::syncRefund('PAYMENT-GENERATED-REFUND', $refundCode, '10.00', 'generated refund code');

        $this->assertIsString($refundCode);
        $this->assertNotSame('', $refundCode);
        $this->assertSame(1, Db::table('plugin_payment_refund')->where([
            'record_code' => 'PAYMENT-GENERATED-REFUND',
            'code' => $refundCode,
        ])->count());
    }

    private function seedCompletedPayment(string $paymentCode, string $channel = Payment::COUPON, array $extra = []): void
    {
        Db::table('plugin_payment_record')->insert(array_merge([
            'unid' => 1,
            'usid' => 1,
            'code' => $paymentCode,
            'order_no' => 'ORDER-' . $paymentCode,
            'channel_code' => $channel,
            'channel_type' => $channel,
            'payment_status' => 1,
            'payment_amount' => '100.00',
            'used_payment' => '100.00',
        ], $extra));
    }

    private function seedRefundAccount(): void
    {
        Db::table('plugin_account_user')->insert([
            'id' => 1,
            'code' => 'USER-REFUND-TEST',
            'phone' => '13800000000',
            'username' => 'Refund User',
            'extra' => '{}',
            'status' => 1,
            'deleted' => 0,
        ]);
    }
}
