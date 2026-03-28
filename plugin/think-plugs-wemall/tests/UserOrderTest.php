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

use plugin\wemall\service\UserOrder;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class UserOrderTest extends SqliteIntegrationTestCase
{
    public function testReductReturnsZeroWhenRandomReductionIsDisabled(): void
    {
        sysdata('plugin.wemall.config', ['enable_reduct' => 0]);

        $this->assertSame('0.00', UserOrder::reduct());
    }

    public function testReductUsesConfiguredReductionRange(): void
    {
        sysdata('plugin.wemall.config', [
            'enable_reduct' => 1,
            'reduct_min' => '1.23',
            'reduct_max' => '1.23',
        ]);

        $this->assertSame('1.23', UserOrder::reduct());
    }

    protected function defineSchema(): void
    {
    }
}
