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

use plugin\payment\service\Balance;
use think\admin\Exception;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class BalanceIntegrationTest extends SqliteIntegrationTestCase
{
    public function testInsufficientDeductionReturnsEnglishMessageWhenLangSetIsEnUs(): void
    {
        $user = $this->createAccountUser([
            'phone' => $this->randomPhone('1331013'),
            'username' => 'balance-' . random_int(100, 999),
            'nickname' => '余额用户',
        ]);
        Balance::create(intval($user->getAttr('id')), 'charge-enough', '余额发放', '10.00', '英文提示测试');
        $this->switchPaymentLang('en-us');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Insufficient balance for deduction');
        Balance::create(intval($user->getAttr('id')), 'charge-minus', '余额扣减', '-20.00', '超额扣减');
    }

    public function testCreateRecordsBalanceAndRecountsUserExtra(): void
    {
        $user = $this->createAccountUser();
        $model = Balance::create(intval($user->getAttr('id')), 'charge-create', '充值测试', '50.00', '首次充值');
        $user = $user->refresh();

        $this->assertTrue($model->isExists());
        $this->assertSame('0.00', $this->decimal($model->getAttr('amount_prev')));
        $this->assertSame('50.00', $this->decimal($model->getAttr('amount_next')));
        $this->assertSame(0, intval($model->getAttr('unlock')));
        $this->assertSame('50.00', $this->decimal($user->getAttr('extra')['balance_lock'] ?? 0));
        $this->assertSame('50.00', $this->decimal($user->getAttr('extra')['balance_total'] ?? 0));
        $this->assertSame('50.00', $this->decimal($user->getAttr('extra')['balance_usable'] ?? 0));
    }

    public function testUnlockAndCancelRefreshStoredState(): void
    {
        $user = $this->createAccountUser();
        Balance::create(intval($user->getAttr('id')), 'charge-cancel', '充值测试', '20.00', '用于状态变更');

        $unlocked = Balance::unlock('charge-cancel');
        $this->assertSame(1, intval($unlocked->getAttr('unlock')));
        $this->assertNotEmpty($unlocked->getAttr('unlock_time'));

        $cancelled = Balance::cancel('charge-cancel');
        $user = $user->refresh();

        $this->assertSame(1, intval($cancelled->getAttr('cancel')));
        $this->assertNotEmpty($cancelled->getAttr('cancel_time'));
        $this->assertSame('0.00', $this->decimal($user->getAttr('extra')['balance_lock'] ?? 0));
        $this->assertSame('0.00', $this->decimal($user->getAttr('extra')['balance_total'] ?? 0));
        $this->assertSame('0.00', $this->decimal($user->getAttr('extra')['balance_usable'] ?? 0));
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentBalanceTable();
    }

    private function switchPaymentLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        $file = TEST_PROJECT_ROOT . "/plugin/think-plugs-payment/src/lang/{$langSet}.php";
        if (is_file($file)) {
            $this->app->lang->load($file, $langSet);
        }
    }
}
