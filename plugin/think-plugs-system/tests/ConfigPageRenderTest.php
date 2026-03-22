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
    public function testIndexRendersDashboardAndAllPluginCards(): void
    {
        $this->bindAdminUser();

        $html = $this->callPageHtml('index');
        $expected = $this->countLocalPluginApps();

        $this->assertStringContainsString('统一管理运行模式、存储中心与系统基础参数', $html);
        $this->assertStringContainsString('插件应用', $html);
        $this->assertSame($expected, substr_count($html, 'class="plugin-showcase-card"'));
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

    private function callPageHtml(string $action): string
    {
        $request = (new Request())
            ->setMethod('GET')
            ->setController('config')
            ->setAction($action);

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

    private function countLocalPluginApps(): int
    {
        $count = 0;
        $pluginPath = TEST_PROJECT_ROOT . '/plugin';
        foreach (glob($pluginPath . '/*/composer.json') ?: [] as $file) {
            $content = file_get_contents($file);
            $config = is_string($content) ? json_decode($content, true) : null;
            $app = is_array($config) ? ($config['extra']['xadmin']['app'] ?? null) : null;
            $code = is_array($app) ? trim(strval($app['code'] ?? '')) : '';
            if ($code !== '' && $code !== 'system') {
                ++$count;
            }
        }
        return $count;
    }
}
