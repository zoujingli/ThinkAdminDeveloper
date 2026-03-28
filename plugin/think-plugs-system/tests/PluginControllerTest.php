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

use plugin\system\controller\Plugin as PluginController;
use plugin\system\service\LangService;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\service\AppService;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class PluginControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexRendersPageBuilderMarkupWhenEnabled(): void
    {
        $html = $this->callActionHtml('index');

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('插件应用中心', $html);
        $this->assertStringContainsString('应用总数', $html);
        $this->assertStringContainsString('可进入工作台', $html);
        $this->assertStringContainsString('接入概览', $html);
        $this->assertStringContainsString('注释式 RBAC', $html);
        $this->assertStringContainsString('每插件单主安装脚本', $html);
        $this->assertStringContainsString('插件列表', $html);
        $this->assertStringContainsString('中心配置', $html);
    }

    public function testIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('index');

        $this->assertStringContainsString('Plugin Center', $html);
        $this->assertStringContainsString('Integration Overview', $html);
        $this->assertStringContainsString('Center Settings', $html);
        $this->assertStringContainsString('Workbench Ready', $html);
        $this->assertStringNotContainsString('插件应用中心', $html);
    }

    public function testIndexRendersDisabledBuilderPageWhenPluginCenterDisabled(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.plugin_center',
            'value' => json_encode([
                'enabled' => 0,
                'show_menu' => 1,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $html = $this->callActionHtml('index');

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('插件中心已禁用', $html);
        $this->assertStringContainsString('系统参数配置', $html);
        $this->assertStringContainsString('处理建议', $html);
    }

    public function testLayoutDisabledRendersBuilderErrorContentInsidePluginShell(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.site',
            'value' => [
                'website_name' => '测试站点',
                'application_name' => 'Plugin Hub',
                'application_version' => 'v3.2.1',
                'browser_icon' => 'https://static.example.com/plugin-logo.png',
            ],
        ]);
        $this->createSystemDataFixture([
            'name' => 'system.plugin_center',
            'value' => json_encode([
                'enabled' => 0,
                'show_menu' => 1,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $html = $this->callActionHtml('layout');

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('插件页面暂时无法打开', $html);
        $this->assertStringContainsString('插件中心已禁用，请在系统参数中重新启用。', $html);
        $this->assertStringContainsString('返回插件中心', $html);
        $this->assertStringContainsString('排查建议', $html);
        $this->assertStringContainsString('测试站点', $html);
        $this->assertStringContainsString('Plugin Hub', $html);
        $this->assertStringContainsString('v3.2.1', $html);
        $this->assertStringContainsString('https://static.example.com/plugin-logo.png', $html);
        $this->assertStringContainsString('admin', $html);
    }

    public function testLayoutRendersWorkbenchShellWhenPluginHasAuthorizedMenus(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.site',
            'value' => [
                'website_name' => '测试站点',
                'application_name' => 'Plugin Hub',
                'application_version' => 'v3.2.1',
                'browser_icon' => 'https://static.example.com/plugin-logo.png',
            ],
        ]);
        $this->createSystemDataFixture([
            'name' => 'system.plugin_center',
            'value' => json_encode([
                'enabled' => 1,
                'show_menu' => 1,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $html = $this->callActionHtml('layout', [
            'encode' => encode('system'),
        ]);

        $this->assertStringContainsString('layui-layout-admin', $html);
        $this->assertStringContainsString('系统管理', $html);
        $this->assertStringContainsString('系统配置', $html);
        $this->assertStringContainsString('系统参数配置', $html);
        $this->assertStringContainsString('返回插件中心', $html);
        $this->assertStringContainsString('Plugin Hub', $html);
        $this->assertStringContainsString('admin', $html);
    }

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
        $this->app->initialize();
        AppService::clear();
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-system/src/view' . DIRECTORY_SEPARATOR,
        ]);
    }

    private function callActionHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('plugin')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new PluginController($this->app);
            $controller->{$action}();
            self::fail("Expected PluginController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function bindAdminUser(): void
    {
        $auth = [
            'id' => 10000,
            'username' => 'admin',
        ];
        $nodes = [
            'system/plugin/index',
            'system/config/index',
            'system/config/system',
        ];

        $this->context->setUser($auth, true, true)->setNodes($nodes);
        RequestContext::instance()->setAuth(array_merge($auth, ['nodes' => $nodes]), '', true);
    }

    private function switchSystemLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        LangService::load($this->app, $langSet);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
