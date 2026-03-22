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
use plugin\system\model\SystemData;
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
class PluginControllerTest extends SqliteIntegrationTestCase
{
    public function testAdminMenuFilterHidesPluginCenterNodeWhenMenuIsDisabled(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.plugin.center.config',
            'value' => json_encode([[
                'enabled' => 1,
                'show_menu' => 0,
                'default' => '',
            ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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

    public function testSetDefaultAppCanClearDefaultEntry(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.plugin.center.config',
            'value' => json_encode([[
                'enabled' => 1,
                'show_menu' => 1,
                'default' => 'storage',
            ]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $this->bindAdminUser();
        $result = $this->callActionPost('setDefaultApp', ['default' => '']);
        $row = SystemData::mk()->where(['name' => 'system.plugin.center.config'])->findOrEmpty();
        $payload = json_decode(strval($row->getData('value')), true)[0] ?? [];

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('已取消默认插件入口。', $result['info'] ?? '');
        $this->assertTrue($row->isExists());
        $this->assertSame('', strval($payload['default'] ?? ''));
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

    private function callActionPost(string $action, array $payload): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod('POST')
            ->setController('plugin')
            ->setAction($action);

        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new PluginController($this->app);
            $controller->{$action}();
            self::fail("Expected PluginController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
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

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
