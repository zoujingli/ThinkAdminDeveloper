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

use plugin\payment\service\Integral;
use think\admin\Exception;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class IntegralIntegrationTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->context->setData('plugin.payment.config', ['integral' => 100]);
    }

    public function testRatioAndCreateRefreshIntegralSummary(): void
    {
        $user = $this->createAccountUser([
            'phone' => $this->randomPhone('1330013'),
            'username' => 'integral-' . random_int(100, 999),
            'nickname' => '积分用户',
        ]);
        $this->assertSame('2.500000', Integral::ratio('250'));

        $model = Integral::create(intval($user->getAttr('id')), 'integral-create', '积分发放', '30.00', '签到积分');
        $user = $user->refresh();

        $this->assertTrue($model->isExists());
        $this->assertSame('0.00', $this->decimal($model->getAttr('amount_prev')));
        $this->assertSame('30.00', $this->decimal($model->getAttr('amount_next')));
        $this->assertSame('30.00', $this->decimal($user->getAttr('extra')['integral_lock'] ?? 0));
        $this->assertSame('30.00', $this->decimal($user->getAttr('extra')['integral_total'] ?? 0));
        $this->assertSame('30.00', $this->decimal($user->getAttr('extra')['integral_usable'] ?? 0));
    }

    public function testUnlockCancelAndInsufficientDeduction(): void
    {
        $user = $this->createAccountUser([
            'phone' => $this->randomPhone('1330013'),
            'username' => 'integral-' . random_int(100, 999),
            'nickname' => '积分用户',
        ]);
        Integral::create(intval($user->getAttr('id')), 'integral-state', '积分发放', '12.00', '用于状态变更');

        $unlocked = Integral::unlock('integral-state');
        $this->assertSame(1, intval($unlocked->getAttr('unlock')));
        $this->assertNotEmpty($unlocked->getAttr('unlock_time'));

        $cancelled = Integral::cancel('integral-state');
        $user = $user->refresh();
        $this->assertSame(1, intval($cancelled->getAttr('cancel')));
        $this->assertSame('0.00', $this->decimal($user->getAttr('extra')['integral_lock'] ?? 0));
        $this->assertSame('0.00', $this->decimal($user->getAttr('extra')['integral_total'] ?? 0));
        $this->assertSame('0.00', $this->decimal($user->getAttr('extra')['integral_usable'] ?? 0));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('扣减积分不足');
        Integral::create(intval($user->getAttr('id')), 'integral-minus', '积分扣减', '-20.00', '超额扣减');
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentIntegralTable();
    }
}
