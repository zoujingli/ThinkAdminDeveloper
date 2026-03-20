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
use think\admin\service\AppService;
use think\admin\service\RuntimeService;
use think\App;

/**
 * @internal
 * @coversNothing
 */
class AppServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $app = new App(TEST_PROJECT_ROOT);
        RuntimeService::init($app);
        $app->initialize();
        AppService::clear();
    }

    public function testMenusAcceptPluginDefinitionArray(): void
    {
        $plugin = AppService::resolvePlugin('system', true);

        $this->assertIsArray($plugin);
        $this->assertNotSame([], $plugin);
        $this->assertSame(
            AppService::menus('system', false, true),
            AppService::menus($plugin, false, true)
        );
    }
}
