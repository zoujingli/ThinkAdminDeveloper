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

namespace plugin\helper\tests;

use PHPUnit\Framework\TestCase;
use plugin\helper\service\IndexNameService;

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
