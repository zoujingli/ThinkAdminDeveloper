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
use plugin\account\service\Account;
use plugin\wemall\controller\api\auth\Order;
use plugin\wemall\tests\support\TestDatabase;
use think\admin\Library;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class WemallCouponPaymentTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabase::reset();
        $this->seedAuthenticatedAccount();
    }

    public function testCouponIsAcceptedWhenOrderMeetsThresholdExactly(): void
    {
        $this->seedOrder('ORDER-THRESHOLD', '100.00');
        $this->seedCoupon('COUPON-THRESHOLD', '100.00', '100.00');

        $response = $this->payOrder('ORDER-THRESHOLD', 'COUPON-THRESHOLD');

        $this->assertSame(1, $response['code']);
        $this->assertSame('COUPON-THRESHOLD', $response['data']['record']['payment_trade']);
    }

    public function testCouponWithZeroMinimumIsAccepted(): void
    {
        $this->seedOrder('ORDER-ZERO-MINIMUM', '100.00');
        $this->seedCoupon('COUPON-ZERO-MINIMUM', '100.00', '0.00');

        $response = $this->payOrder('ORDER-ZERO-MINIMUM', 'COUPON-ZERO-MINIMUM');

        $this->assertSame(1, $response['code']);
        $this->assertSame('COUPON-ZERO-MINIMUM', $response['data']['record']['payment_trade']);
    }

    public function testSubmittedCouponCodeSelectsAndConsumesThatExactCoupon(): void
    {
        $this->seedOrder('ORDER-EXACT-COUPON', '100.00');
        $this->seedCoupon('COUPON-FIRST', '100.00', '0.00');
        $this->seedCoupon('COUPON-SUBMITTED', '100.00', '0.00');

        $response = $this->payOrder('ORDER-EXACT-COUPON', 'COUPON-SUBMITTED');

        $firstCoupon = Db::table('plugin_wemall_user_coupon')->where(['code' => 'COUPON-FIRST'])->find();
        $submittedCoupon = Db::table('plugin_wemall_user_coupon')->where(['code' => 'COUPON-SUBMITTED'])->find();
        $order = Db::table('plugin_wemall_order')->where(['order_no' => 'ORDER-EXACT-COUPON'])->find();
        $this->assertSame(1, $response['code']);
        $this->assertSame('COUPON-SUBMITTED', $response['data']['record']['payment_trade']);
        $this->assertSame(0, (int)$firstCoupon['used']);
        $this->assertSame(1, (int)$firstCoupon['status']);
        $this->assertSame(1, (int)$submittedCoupon['used']);
        $this->assertSame(2, (int)$submittedCoupon['status']);
        $this->assertNotEmpty($submittedCoupon['used_time']);
        $this->assertSame('COUPON-SUBMITTED', $order['coupon_code']);
    }

    public function testCouponAssignedToAnotherAccountCannotChangeOrder(): void
    {
        $this->assertInvalidCouponCannotChangeOrder(['unid' => 2], [], '无限优惠券！');
    }

    public function testExpiredCouponAssignmentCannotChangeOrder(): void
    {
        $this->assertInvalidCouponCannotChangeOrder(['expire' => time() - 60], [], '优惠券无效！');
    }

    public function testDeletedCouponAssignmentCannotChangeOrder(): void
    {
        $this->assertInvalidCouponCannotChangeOrder(['deleted' => 1], [], '无限优惠券！');
    }

    public function testUsedCouponAssignmentCannotChangeOrder(): void
    {
        $this->assertInvalidCouponCannotChangeOrder(['used' => 1], [], '无限优惠券！');
    }

    public function testDisabledCouponCannotChangeOrder(): void
    {
        $this->assertInvalidCouponCannotChangeOrder([], ['status' => 0], '无限优惠券！');
    }

    public function testDeletedCouponCannotChangeOrder(): void
    {
        $this->assertInvalidCouponCannotChangeOrder([], ['deleted' => 1], '无限优惠券！');
    }

    public function testCouponIsRejectedWhenOrderIsBelowThreshold(): void
    {
        $this->seedOrder('ORDER-BELOW-THRESHOLD', '99.99');
        $this->seedCoupon('COUPON-BELOW-THRESHOLD', '99.99', '100.00');

        $response = $this->payOrder('ORDER-BELOW-THRESHOLD', 'COUPON-BELOW-THRESHOLD');

        $this->assertSame(0, $response['code']);
        $this->assertSame('未达到使用条件！', $response['info']);
    }

    public function testPaymentWithoutCouponCodeDoesNotAutoSelectCoupon(): void
    {
        $this->seedOrder('ORDER-WITHOUT-COUPON', '100.00');
        $this->seedCoupon('COUPON-NOT-SUBMITTED', '100.00', '0.00');

        $response = $this->payOrder('ORDER-WITHOUT-COUPON');

        $coupon = Db::table('plugin_wemall_user_coupon')->where(['code' => 'COUPON-NOT-SUBMITTED'])->find();
        $order = Db::table('plugin_wemall_order')->where(['order_no' => 'ORDER-WITHOUT-COUPON'])->find();
        $this->assertSame(0, $response['code']);
        $this->assertStringContainsString('missing-channel', $response['info']);
        $this->assertSame(0, (int)$coupon['used']);
        $this->assertSame(1, (int)$coupon['status']);
        $this->assertSame('', $order['coupon_code']);
    }

    private function assertInvalidCouponCannotChangeOrder(array $userCoupon, array $couponConfig, string $message): void
    {
        $this->seedOrder('ORDER-INVALID-COUPON', '100.00');
        $this->seedCoupon('COUPON-INVALID', '100.00', '0.00', $userCoupon, $couponConfig);

        $response = $this->payOrder('ORDER-INVALID-COUPON', 'COUPON-INVALID');

        $order = Db::table('plugin_wemall_order')->where(['order_no' => 'ORDER-INVALID-COUPON'])->find();
        $this->assertSame(0, $response['code']);
        $this->assertSame($message, $response['info']);
        $this->assertSame('', $order['coupon_code']);
        $this->assertSame(0, Db::table('plugin_payment_record')->where(['payment_trade' => 'COUPON-INVALID'])->count());
    }

    private function payOrder(string $orderNo, string $couponCode = ''): array
    {
        $request = (new Request())
            ->withHeader(['Authorization' => 'Bearer tester'])
            ->withPost([
                'order_no' => $orderNo,
                'coupon_code' => $couponCode,
                'channel_code' => 'missing-channel',
            ])
            ->withServer(['REQUEST_METHOD' => 'POST'])
            ->setAction('payment');

        $app = Library::$sapp;
        $app->instance('request', $request);

        try {
            (new Order($app))->payment();
        } catch (HttpResponseException $exception) {
            $response = json_decode($exception->getResponse()->getContent(), true);
            if (!is_array($response)) {
                self::fail('Order::payment() returned invalid JSON: ' . json_last_error_msg());
            }
            return $response;
        }

        self::fail('Order::payment() did not return an HTTP response.');
    }

    private function seedAuthenticatedAccount(): void
    {
        Db::table('plugin_account_user')->insert([
            'id' => 1,
            'code' => 'USER000000000001',
            'phone' => '13800000000',
            'username' => 'Test User',
            'extra' => '{}',
            'status' => 1,
            'deleted' => 0,
        ]);
        Db::table('plugin_account_bind')->insert([
            'id' => 1,
            'unid' => 1,
            'type' => Account::WAP,
            'phone' => '13800000000',
            'extra' => '{}',
            'status' => 1,
            'deleted' => 0,
        ]);
        Db::table('plugin_account_auth')->insert([
            'id' => 1,
            'usid' => 1,
            'type' => Account::WAP,
            'token' => 'tester',
            'time' => 0,
        ]);
        Db::table('plugin_wemall_user_relation')->insert([
            'id' => 1,
            'unid' => 1,
            'path' => ',',
            'level_name' => 'Normal Member',
            'agent_level_name' => 'Normal User',
        ]);
    }

    private function seedCoupon(string $code, string $amount, string $limitAmount, array $userCoupon = [], array $couponConfig = []): void
    {
        $couponId = Db::table('plugin_wemall_config_coupon')->insertGetId(array_merge([
            'name' => $code,
            'amount' => $amount,
            'limit_amount' => $limitAmount,
            'status' => 1,
            'deleted' => 0,
        ], $couponConfig));
        Db::table('plugin_wemall_user_coupon')->insert(array_merge([
            'unid' => 1,
            'coid' => $couponId,
            'code' => $code,
            'used' => 0,
            'status' => 1,
            'deleted' => 0,
            'expire' => 0,
        ], $userCoupon));
    }

    private function seedOrder(string $orderNo, string $amount): void
    {
        Db::table('plugin_wemall_order')->insert([
            'unid' => 1,
            'order_no' => $orderNo,
            'amount_real' => $amount,
            'amount_total' => $amount,
            'amount_goods' => $amount,
            'amount_discount' => $amount,
            'status' => 2,
        ]);
    }
}
