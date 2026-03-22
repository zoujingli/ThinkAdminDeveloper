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

use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;

/**
 * @internal
 * @coversNothing
 */
class PluginControllerTest extends SqliteIntegrationTestCase
{
    public function testAdminMenuFilterHidesPluginCenterNodeWhenMenuIsDisabled(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.plugin_center',
            'value' => json_encode([
                'enabled' => 1,
                'show_menu' => 0,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $menus = [
            ['node' => 'system/config/index', 'title' => '系统参数'],
            ['node' => 'system/plugin/index', 'title' => '插件中心'],
            ['url' => 'system/plugin/index', 'title' => '插件中心链接'],
        ];
        $filtered = admin_menu_filter($menus);

        $this->assertCount(1, $filtered);
        $this->assertSame('system/config/index', $filtered[0]['node']);
    }

    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
    }
}
