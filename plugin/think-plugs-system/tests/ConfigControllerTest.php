<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\tests;

use plugin\system\controller\Config as ConfigController;
use plugin\system\model\SystemConfig;
use plugin\system\model\SystemOplog;
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
class ConfigControllerTest extends SqliteIntegrationTestCase
{
    public function testSystemPostPersistsConfigsAndWritesOplog(): void
    {
        $result = $this->callPostController('system', [
            'site_name' => '测试后台',
            'site_theme' => 'green-1',
            'site_copy' => 'Unit Test Copy',
            'site_icon' => '/upload/local-icon.png',
            'login_name' => '管理后台',
            'xpath' => '/should-be-ignored',
        ]);

        $configs = SystemConfig::mk()->order('id asc')->column('value', 'name');
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('数据保存成功！', $result['info'] ?? '');
        $this->assertStringContainsString('system/config/index', strval($result['data'] ?? ''));
        $this->assertSame('测试后台', $configs['site_name'] ?? '');
        $this->assertSame('green-1', $configs['site_theme'] ?? '');
        $this->assertSame('Unit Test Copy', $configs['site_copy'] ?? '');
        $this->assertSame('/upload/local-icon.png', $configs['site_icon'] ?? '');
        $this->assertSame('管理后台', $configs['login_name'] ?? '');
        $this->assertArrayNotHasKey('xpath', $configs);
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统配置管理', $oplog->getData('action'));
        $this->assertSame('修改系统参数成功', $oplog->getData('content'));
        $this->assertSame('tester', $oplog->getData('username'));
    }

    public function testSystemPostUpdatesExistingConfigRowsWithoutDuplicates(): void
    {
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_name',
            'value' => '旧站点名',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_theme',
            'value' => 'default',
        ]);

        $result = $this->callPostController('system', [
            'site_name' => '新站点名',
            'site_theme' => 'blue-1',
        ]);

        $siteNameRows = SystemConfig::mk()->where(['type' => 'base', 'name' => 'site_name'])->count();
        $siteThemeRows = SystemConfig::mk()->where(['type' => 'base', 'name' => 'site_theme'])->count();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame(1, $siteNameRows);
        $this->assertSame(1, $siteThemeRows);
        $this->assertSame('新站点名', SystemConfig::mk()->where(['type' => 'base', 'name' => 'site_name'])->value('value'));
        $this->assertSame('blue-1', SystemConfig::mk()->where(['type' => 'base', 'name' => 'site_theme'])->value('value'));
    }

    protected function defineSchema(): void
    {
        $this->createSystemConfigTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
    }

    private function callPostController(string $action, array $post): array
    {
        $request = (new Request())
            ->withGet($post)
            ->withPost($post)
            ->setMethod('POST')
            ->setController('config')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new ConfigController($this->app);
            $controller->{$action}();
            self::fail("Expected ConfigController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        RequestContext::instance()->setAuth([
            'id' => 9101,
            'username' => 'tester',
            'password' => md5('changed-password'),
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
