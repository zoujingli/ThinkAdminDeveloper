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

    private function seedCompletedPayment(string $paymentCode): void
    {
        Db::table('plugin_payment_record')->insert([
            'unid' => 1,
            'usid' => 1,
            'code' => $paymentCode,
            'order_no' => 'ORDER-' . $paymentCode,
            'channel_code' => Payment::COUPON,
            'channel_type' => Payment::COUPON,
            'payment_status' => 1,
            'payment_amount' => '100.00',
            'used_payment' => '100.00',
        ]);
    }
}
