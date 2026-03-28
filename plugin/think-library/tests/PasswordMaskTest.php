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

/**
 * @internal
 * @coversNothing
 */
class PasswordMaskTest extends TestCase
{
    public function testPasswordMaskDefaultsToSixStars(): void
    {
        $this->assertSame('******', password_mask());
    }

    public function testPasswordIsMaskAcceptsOnlyAsterisks(): void
    {
        $this->assertTrue(password_is_mask('******'));
        $this->assertTrue(password_is_mask('************'));
        $this->assertFalse(password_is_mask(''));
        $this->assertFalse(password_is_mask('***abc'));
    }

    public function testPasswordIsUnchangedSupportsBlankAndMaskedValues(): void
    {
        $this->assertTrue(password_is_unchanged(''));
        $this->assertTrue(password_is_unchanged('******'));
        $this->assertTrue(password_is_unchanged('********'));
        $this->assertFalse(password_is_unchanged('Secret@123'));
    }
}
