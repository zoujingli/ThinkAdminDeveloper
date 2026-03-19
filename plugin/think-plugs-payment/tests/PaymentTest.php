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

use PHPUnit\Framework\TestCase;
use plugin\account\service\Account;
use plugin\payment\service\Payment;

/**
 * @internal
 * @coversNothing
 */
class PaymentTest extends TestCase
{
    public function testGetTypes()
    {
        $all = Payment::types();
        $this->assertNotEmpty($all);
    }

    public function testGetTypesByChannel()
    {
        $all = Payment::typesByAccess(Account::WXAPP);
        $this->assertNotEmpty($all);
    }
}
