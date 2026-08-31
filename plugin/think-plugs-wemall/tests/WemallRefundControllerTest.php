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

namespace plugin\wemall\tests;

use PHPUnit\Framework\TestCase;
use plugin\payment\service\Payment;
use plugin\wemall\controller\shop\Refund;
use plugin\wemall\tests\support\TestDatabase;
use think\admin\Library;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class WemallRefundControllerTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabase::reset();
        Db::table('plugin_account_user')->insert([
            'id' => 1,
            'code' => 'USER-REFUND-CONTROLLER',
            'phone' => '13800000000',
            'username' => 'Refund Controller User',
            'extra' => '{}',
            'status' => 1,
            'deleted' => 0,
        ]);
        Db::table('plugin_wemall_order')->insert([
            'id' => 1,
            'unid' => 1,
            'order_no' => 'ORDER-REFUND-CONTROLLER',
            'status' => 5,
            'amount_real' => '100.00',
            'payment_status' => 1,
        ]);
        Db::table('plugin_wemall_order_refund')->insert([
            'id' => 1,
            'unid' => 1,
            'code' => 'AFTERSALE-CONTROLLER',
            'order_no' => 'ORDER-REFUND-CONTROLLER',
            'status' => 4,
            'amount' => '10.00',
        ]);
        Db::table('plugin_payment_record')->insert([
            'unid' => 1,
            'usid' => 1,
            'code' => 'PAYMENT-REFUND-CONTROLLER',
            'order_no' => 'ORDER-REFUND-CONTROLLER',
            'channel_code' => Payment::BALANCE,
            'channel_type' => Payment::BALANCE,
            'payment_status' => 1,
            'payment_amount' => '100.00',
            'used_payment' => '100.00',
            'used_balance' => '100.00',
        ]);
    }

    public function testRefundFormUsesEachSubmittedPaymentAmount(): void
    {
        $response = $this->submitRefund('10.00');

        $refund = Db::table('plugin_wemall_order_refund')->where(['id' => 1])->find();
        $this->assertSame(1, $response['code']);
        $this->assertSame(1, Db::table('plugin_payment_refund')->where(['record_code' => 'PAYMENT-REFUND-CONTROLLER'])->count());
        $this->assertSame(1, Db::table('plugin_payment_balance')->where(['code' => $refund['balance_code']])->count());
        $this->assertSame(0, bccomp(strval($refund['balance_amount']), '10.00', 2));
    }

    public function testRefundFormRejectsMalformedPaymentAmount(): void
    {
        $response = $this->submitRefund('abc');

        $this->assertSame(0, $response['code']);
        $this->assertSame('退款金额格式无效！', $response['info']);
        $this->assertSame(0, Db::table('plugin_payment_refund')->count());
        $this->assertSame(0, Db::table('plugin_payment_balance')->count());
    }

    private function submitRefund(string $amount): array
    {
        $request = (new Request())->withPost([
            'id' => 1,
            'ptypes' => ['PAYMENT-REFUND-CONTROLLER' => Payment::BALANCE],
            'refunds' => ['PAYMENT-REFUND-CONTROLLER' => $amount],
            'pcodes' => ['PAYMENT-REFUND-CONTROLLER' => Payment::BALANCE],
            'status' => 5,
            'remark' => 'controller regression',
        ])->withServer(['REQUEST_METHOD' => 'POST'])->setAction('edit');
        $app = Library::$sapp;
        $app->instance('request', $request);
        $app->instance('session', new class {
            public function get(string $key, $default = null)
            {
                return $key === 'user.id' ? 1 : $default;
            }
        });

        try {
            (new Refund($app))->edit();
            self::fail('Refund form should return an HTTP response.');
        } catch (HttpResponseException $exception) {
            $response = json_decode($exception->getResponse()->getContent(), true);
            if (!is_array($response)) {
                self::fail('Refund form returned invalid JSON.');
            }
            return $response;
        }
    }
}
