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

use plugin\payment\model\PluginPaymentBalance;
use plugin\payment\model\PluginPaymentIntegral;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\model\PluginPaymentRefund;
use plugin\payment\service\Balance;
use plugin\payment\service\Integral;
use plugin\payment\service\Payment;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class PaymentLedgerIntegrationTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess([
            'headimg' => 'https://example.com/payment-ledger-account.png',
            'userPrefix' => '台账账号',
        ]);
    }

    public function testBalancePaymentRefundRoundTripsBalanceLedger(): void
    {
        $account = $this->createBoundAccountFixture();
        $unid = $account->getUnid();

        Balance::create($unid, 'balance-seed', '初始余额', '50.00', '初始余额注入', true);
        $response = Payment::mk(Payment::BALANCE)->create(
            $account,
            'ORDER-BALANCE-001',
            '余额支付订单',
            '20.00',
            '20.00',
            '余额支付下单'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $this->assertSame(Payment::BALANCE, $record->getAttr('channel_type'));
        $this->assertSame('20.00', $this->decimal($record->getAttr('used_balance')));
        $this->assertSame('20.00', $this->decimal($record->getAttr('payment_amount')));
        $this->assertSame(1, intval($record->getAttr('payment_status')));
        $this->assertSame('30.00', $this->decimal(Balance::recount($unid)['usable']));

        $refundCode = '';
        [$status, $message] = Payment::mk(Payment::BALANCE)->refund($record->getAttr('code'), '5.00', '余额退款', $refundCode);
        $refund = PluginPaymentRefund::mk()->where(['code' => $refundCode])->findOrEmpty();
        $refundBalance = PluginPaymentBalance::mk()->where(['code' => $refundCode])->findOrEmpty();

        $this->assertSame([1, '发起退款成功！'], [$status, $message]);
        $this->assertTrue($refund->isExists());
        $this->assertTrue($refundBalance->isExists());
        $this->assertSame(Payment::BALANCE, $refund->getAttr('refund_account'));
        $this->assertSame('5.00', $this->decimal($refund->getAttr('used_balance')));
        $this->assertSame('5.00', $this->decimal($refundBalance->getAttr('amount')));
        $this->assertSame('来自订单 ORDER-BALANCE-001 退回余额', $refundBalance->getAttr('remark'));
        $this->assertSame('35.00', $this->decimal(Balance::recount($unid)['usable']));
        $this->assertSame('5.00', $this->decimal(Payment::totalRefundAmount($record->getAttr('code'))['balance']));
    }

    public function testIntegralPaymentRefundRoundTripsIntegralLedger(): void
    {
        $account = $this->createBoundAccountFixture();
        $unid = $account->getUnid();

        Integral::create($unid, 'integral-seed', '初始积分', '30.00', '初始积分注入', true);
        $response = Payment::mk(Payment::INTEGRAL)->create(
            $account,
            'ORDER-INTEGRAL-001',
            '积分支付订单',
            '12.00',
            '12.00',
            '积分支付下单'
        );

        $record = PluginPaymentRecord::mk()->where(['code' => $response->record['code']])->findOrEmpty();
        $this->assertSame(Payment::INTEGRAL, $record->getAttr('channel_type'));
        $this->assertSame('12.00', $this->decimal($record->getAttr('used_integral')));
        $this->assertSame('12.00', $this->decimal($record->getAttr('payment_amount')));
        $this->assertSame(1, intval($record->getAttr('payment_status')));
        $this->assertSame('18.00', $this->decimal(Integral::recount($unid)['usable']));

        $refundCode = '';
        [$status, $message] = Payment::mk(Payment::INTEGRAL)->refund($record->getAttr('code'), '4.00', '积分退款', $refundCode);
        $refund = PluginPaymentRefund::mk()->where(['code' => $refundCode])->findOrEmpty();
        $refundIntegral = PluginPaymentIntegral::mk()->where(['code' => $refundCode])->findOrEmpty();

        $this->assertSame([1, '发起退款成功！'], [$status, $message]);
        $this->assertTrue($refund->isExists());
        $this->assertTrue($refundIntegral->isExists());
        $this->assertSame(Payment::INTEGRAL, $refund->getAttr('refund_account'));
        $this->assertSame('4.00', $this->decimal($refund->getAttr('used_integral')));
        $this->assertSame('4.00', $this->decimal($refundIntegral->getAttr('amount')));
        $this->assertSame('来自订单 ORDER-INTEGRAL-001 退回积分', $refundIntegral->getAttr('remark'));
        $this->assertSame('22.00', $this->decimal(Integral::recount($unid)['usable']));
        $this->assertSame('4.00', $this->decimal(Payment::totalRefundAmount($record->getAttr('code'))['integral']));
    }

    public function testBalancePaymentRefundUsesEnglishRemarkWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $unid = $account->getUnid();

        Balance::create($unid, 'balance-seed-en', 'Initial balance', '50.00', 'Initial balance seed', true);
        $this->switchPaymentLang('en-us');

        $response = Payment::mk(Payment::BALANCE)->create(
            $account,
            'ORDER-BALANCE-EN-001',
            'Balance payment order',
            '20.00',
            '20.00',
            'Balance payment create'
        );

        $refundCode = '';
        [$status, $message] = Payment::mk(Payment::BALANCE)->refund($response->record['code'], '5.00', 'Balance refund', $refundCode);
        $refundBalance = PluginPaymentBalance::mk()->where(['code' => $refundCode])->findOrEmpty();

        $this->assertSame('Balance payment completed', $response->message);
        $this->assertSame([1, 'Refund requested successfully'], [$status, $message]);
        $this->assertSame('Balance Refund', $refundBalance->getAttr('name'));
        $this->assertSame('Refund balance from order ORDER-BALANCE-EN-001', $refundBalance->getAttr('remark'));
    }

    public function testIntegralPaymentRefundUsesEnglishRemarkWhenLangSetIsEnUs(): void
    {
        $account = $this->createBoundAccountFixture();
        $unid = $account->getUnid();

        Integral::create($unid, 'integral-seed-en', 'Initial integral', '30.00', 'Initial integral seed', true);
        $this->switchPaymentLang('en-us');

        $response = Payment::mk(Payment::INTEGRAL)->create(
            $account,
            'ORDER-INTEGRAL-EN-001',
            'Integral payment order',
            '12.00',
            '12.00',
            'Integral payment create'
        );

        $refundCode = '';
        [$status, $message] = Payment::mk(Payment::INTEGRAL)->refund($response->record['code'], '4.00', 'Integral refund', $refundCode);
        $refundIntegral = PluginPaymentIntegral::mk()->where(['code' => $refundCode])->findOrEmpty();

        $this->assertSame('Integral deduction completed', $response->message);
        $this->assertSame([1, 'Refund requested successfully'], [$status, $message]);
        $this->assertSame('Integral Refund', $refundIntegral->getAttr('name'));
        $this->assertSame('Refund integral from order ORDER-INTEGRAL-EN-001', $refundIntegral->getAttr('remark'));
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentRecordTable();
        $this->createPaymentRefundTable();
        $this->createPaymentBalanceTable();
        $this->createPaymentIntegralTable();
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
