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

use plugin\system\controller\Config as ConfigController;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class ConfigPageRenderTest extends SqliteIntegrationTestCase
{
    public function testIndexRendersDashboardWithoutPluginCards(): void
    {
        $this->bindAdminUser();

        $html = $this->callPageHtml('index');

        $this->assertStringContainsString('统一管理运行模式、存储中心与系统基础参数', $html);
        $this->assertStringContainsString('站点名称', $html);
        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('系统参数配置', $html);
        $this->assertStringNotContainsString('插件应用', $html);
        $this->assertStringNotContainsString('插件标识', $html);
    }

    public function testSystemRendersGroupedConfigurationSections(): void
    {
        $this->bindAdminUser();

        $html = $this->callPageHtml('system');

        $this->assertStringContainsString('统一管理登录入口、品牌信息与安全配置', $html);
        $this->assertStringContainsString('系统参数设置', $html);
        $this->assertStringContainsString('返回配置首页', $html);
        $this->assertStringContainsString('class="system-config-form layui-form"', $html);
        $this->assertStringContainsString('data-builder-scope="page"', $html);
        $this->assertStringContainsString('站点品牌信息', $html);
        $this->assertStringContainsString('data-open-site-theme', $html);
        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('运行参数', $html);
    }

    public function testStorageRendersBuilderDashboard(): void
    {
        $this->bindAdminUser();

        $html = $this->callPageHtml('storage');

        $this->assertStringContainsString('统一管理文件上传、命名策略与外链输出', $html);
        $this->assertStringContainsString('存储引擎', $html);
        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('当前默认驱动', $html);
    }

    public function testStorageDriverFormRendersWithBuilder(): void
    {
        $this->bindAdminUser();

        $html = $this->callPageHtml('storage', ['type' => 'local']);

        $this->assertStringContainsString('统一维护上传策略与驱动参数', $html);
        $this->assertStringContainsString('本地服务器存储 存储配置', $html);
        $this->assertStringContainsString('返回存储中心', $html);
        $this->assertStringContainsString('全局上传策略', $html);
        $this->assertStringContainsString('本地服务器存储 驱动参数', $html);
        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('name="storage[default_driver]" value="local"', $html);
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
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-system/src/view' . DIRECTORY_SEPARATOR,
        ]);
    }

    private function callPageHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->setMethod('GET')
            ->setController('config')
            ->setAction($action)
            ->withGet($query);

        $this->app->instance('request', $request);

        try {
            $controller = new ConfigController($this->app);
            $controller->{$action}();
            self::fail("Expected ConfigController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function bindAdminUser(): void
    {
        RequestContext::instance()->setAuth([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('changed-password'),
        ], '', true);
    }
}
