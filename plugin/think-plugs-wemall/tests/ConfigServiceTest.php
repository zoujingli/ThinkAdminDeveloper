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

use plugin\wemall\service\ConfigService;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class ConfigServiceTest extends SqliteIntegrationTestCase
{
    public function testGetBuildsBaseDomainFromSystemSiteHost(): void
    {
        sysdata('system.site', ['host' => 'https://shop.example.com/']);

        $this->assertSame('https://shop.example.com/h5', ConfigService::get('base_domain'));
    }

    public function testGetFallsBackToRelativeH5WhenSystemSiteHostIsEmpty(): void
    {
        sysdata('system.site', ['host' => '']);

        $this->assertSame('/h5', ConfigService::get('base_domain'));
    }

    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
    }
}
