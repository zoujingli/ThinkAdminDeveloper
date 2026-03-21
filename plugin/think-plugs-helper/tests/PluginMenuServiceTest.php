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
use plugin\helper\plugin\PluginMenuService;
use plugin\system\Service;
use think\admin\Exception;
use think\admin\service\RuntimeService;
use think\App;

/**
 * @internal
 * @coversNothing
 */
class PluginMenuServiceTest extends TestCase
{
    protected function setUp(): void
    {
        function_exists('test_reset_model_makers') && test_reset_model_makers();
        $app = new App(HELPER_TEST_PROJECT_ROOT);
        RuntimeService::init($app);
        $app->config->set([
            'default' => 'file',
            'stores' => [
                'file' => ['type' => 'File', 'path' => sys_get_temp_dir() . '/thinkadmin-helper-menu-cache'],
            ],
        ], 'cache');
    }

    public function testItReadsSystemPluginMenuMetadata(): void
    {
        $menus = PluginMenuService::menus(Service::class);
        $root = PluginMenuService::menuRoot(Service::class);
        $exists = PluginMenuService::menuExists(Service::class);

        $this->assertNotEmpty($menus);
        $this->assertSame('系统管理', $root['name'] ?? '');
        $this->assertSame('system/config/index', $exists['url|node'] ?? '');
    }

    public function testItValidatesSystemPluginMenusAgainstControllerAnnotations(): void
    {
        PluginMenuService::assertMenus(Service::class);
        $this->addToAssertionCount(1);
    }

    public function testItRejectsUnknownServiceClass(): void
    {
        $this->expectException(Exception::class);
        PluginMenuService::menus('plugin\helper\tests\MissingService');
    }
}
