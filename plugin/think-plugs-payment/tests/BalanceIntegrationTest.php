<?php

declare(strict_types=1);

namespace think\admin\tests;

use plugin\account\model\PluginAccountUser;
use plugin\payment\service\Balance;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class BalanceIntegrationTest extends SqliteIntegrationTestCase
{
    protected function defineSchema(): void
    {
        $this->createAccountTables();
        $this->createPaymentBalanceTable();
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
}
