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

namespace plugin\helper\tests;

use PHPUnit\Framework\TestCase;
use plugin\helper\database\IndexNameService;

/**
 * @internal
 * @coversNothing
 */
class IndexNameServiceTest extends TestCase
{
    public function testSingleColumnIndexNameMatchesStableRule(): void
    {
        $name = IndexNameService::generate('system_queue', 'status');

        $this->assertSame('idx_sq_2849_status', $name);
    }

    public function testCompositeUniqueIndexUsesColumnHashSuffix(): void
    {
        $name = IndexNameService::generate('system_queue', ['title', 'status'], true);

        $this->assertSame('uni_sq_2849_title_55530d49', $name);
    }
}
