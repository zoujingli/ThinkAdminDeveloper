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

namespace plugin\worker\tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CommonFunctionsTest extends TestCase
{
    public function testSysqueueIsLoadedFromWorkerPackage(): void
    {
        $this->assertTrue(function_exists('sysqueue'));

        $reflection = new \ReflectionFunction('sysqueue');

        $this->assertSame(
            realpath(WORKER_TEST_PACKAGE_ROOT . '/src/common.php'),
            realpath((string)$reflection->getFileName())
        );
    }
}
