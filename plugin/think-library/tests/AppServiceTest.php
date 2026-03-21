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
use think\admin\Plugin;
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
        function_exists('test_reset_model_makers') && test_reset_model_makers();
        $app = new App(TEST_PROJECT_ROOT);
        RuntimeService::init($app);
        $app->initialize();
        AppService::clear();
    }

    public function testMenusAcceptPluginDefinitionArray(): void
    {
        $plugin = AppService::resolvePlugin('system', true);
        $menus = AppService::menus('system', false, true);

        $this->assertIsArray($plugin);
        $this->assertNotSame([], $plugin);
        $this->assertSame('plugin\\system\\Service', $plugin['service'] ?? null);
        $this->assertTrue(boolval($plugin['show'] ?? false));
        $this->assertFalse(method_exists($plugin['service'], 'menu'));
        $this->assertNotSame([], $menus);
        $this->assertSame('system/config/index', $menus[0]['subs'][0]['node'] ?? null);
        $this->assertSame(
            $menus,
            AppService::menus($plugin, false, true)
        );
    }

    public function testRuntimeMenusAreLoadedFromComposerMetadata(): void
    {
        foreach ($this->pluginComposerManifests() as $service => $manifest) {
            $app = (array)($manifest['extra']['xadmin']['app'] ?? []);
            $meta = (array)($manifest['extra']['xadmin']['menu'] ?? []);
            $menus = (array)($manifest['extra']['xadmin']['menu']['items'] ?? []);
            $show = !array_key_exists('show', $meta) || !empty($meta['show']);
            $this->assertTrue(class_exists($service), "{$service} must be autoloadable");
            $this->assertFalse(method_exists($service, 'menu'), "{$service} should not declare menu()");
            if (array_key_exists('code', $app)) {
                $this->assertSame(strval($app['code']), $service::getAppCode(), "{$service} code must come from composer metadata");
            }
            if (array_key_exists('name', $app)) {
                $this->assertSame(strval($app['name']), $service::getAppName(), "{$service} name must come from composer metadata");
            }
            if (array_key_exists('prefix', $app)) {
                $this->assertSame(strval($app['prefix']), $service::getAppPrefix(), "{$service} prefix must come from composer metadata");
            }
            $this->assertSame($show, $service::getMenuShow(), "{$service} menu show flag must come from composer metadata");
            $this->assertSame($menus, $service::getMenus(), "{$service} menus must come from composer metadata");
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function pluginComposerManifests(): array
    {
        $items = [];
        foreach (glob(TEST_PROJECT_ROOT . '/plugin/*/composer.json') ?: [] as $file) {
            $manifest = json_decode((string)file_get_contents($file), true);
            if (!is_array($manifest)) {
                continue;
            }
            $services = (array)($manifest['extra']['think']['services'] ?? []);
            $service = strval($services[0] ?? '');
            if ($service === '' || !class_exists($service) || !is_subclass_of($service, Plugin::class)) {
                continue;
            }
            $items[$service] = $manifest;
        }

        ksort($items);
        return $items;
    }
}
