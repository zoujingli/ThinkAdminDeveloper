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

use plugin\system\controller\api\System as SystemController;
use plugin\system\model\SystemConfig;
use plugin\system\model\SystemOplog;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\service\RuntimeService;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class ApiSystemControllerTest extends SqliteIntegrationTestCase
{
    private string $runtimeEnvFile = '';

    private string $originRuntimeEnvFile = '';

    protected function tearDown(): void
    {
        if ($this->originRuntimeEnvFile !== '') {
            $this->setRuntimeEnvFile($this->originRuntimeEnvFile);
        }
        sysvar('think.admin.runtime', []);
        parent::tearDown();
    }

    public function testEditorUpdatesBaseEditorConfigAndWritesOplogForSuperAdmin(): void
    {
        $result = $this->callActionController('editor', ['editor' => 'tinymce'], true);

        $config = SystemConfig::mk()->where(['type' => 'base', 'name' => 'editor'])->findOrEmpty();
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('已切换后台编辑器！', $result['info'] ?? '');
        $this->assertStringContainsString('javascript:location.reload()', strval($result['data'] ?? ''));
        $this->assertTrue($config->isExists());
        $this->assertSame('tinymce', $config->getData('value'));
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统运维管理', $oplog->getData('action'));
        $this->assertSame('切换编辑器为tinymce', $oplog->getData('content'));
        $this->assertSame('admin', $oplog->getData('username'));
    }

    public function testEditorRejectsNonSuperAdmin(): void
    {
        $result = $this->callActionController('editor', ['editor' => 'tinymce'], false);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('请使用超管账号操作！', $result['info'] ?? '');
        $this->assertSame(0, SystemConfig::mk()->where(['type' => 'base', 'name' => 'editor'])->count());
        $this->assertSame(0, SystemOplog::mk()->count());
    }

    public function testConfigCompactsRowsWithoutLosingValuesForSuperAdmin(): void
    {
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_name',
            'value' => '测试站点',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_theme',
            'value' => 'green-1',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'storage',
            'name' => 'driver',
            'value' => 'local',
        ]);

        $result = $this->callActionController('config', [], true);

        $configs = SystemConfig::mk()->order('type asc,name asc')->select()->toArray();
        $map = [];
        foreach ($configs as $item) {
            $map[$item['type'] . '.' . $item['name']] = $item['value'];
        }
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('清理系统配置成功！', $result['info'] ?? '');
        $this->assertCount(3, $configs);
        $this->assertSame('测试站点', $map['base.site_name'] ?? '');
        $this->assertSame('green-1', $map['base.site_theme'] ?? '');
        $this->assertSame('local', $map['storage.driver'] ?? '');
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统运维管理', $oplog->getData('action'));
        $this->assertSame('清理系统配置参数', $oplog->getData('content'));
        $this->assertSame('admin', $oplog->getData('username'));
    }

    public function testConfigRejectsNonSuperAdmin(): void
    {
        $this->createSystemConfigFixture([
            'type' => 'base',
            'name' => 'site_name',
            'value' => '原始站点',
        ]);

        $result = $this->callActionController('config', [], false);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('请使用超管账号操作！', $result['info'] ?? '');
        $this->assertSame('原始站点', SystemConfig::mk()->where(['type' => 'base', 'name' => 'site_name'])->value('value'));
        $this->assertSame(0, SystemOplog::mk()->count());
    }

    public function testPushCallsOptimizeSchemaAndWritesOplogForSuperAdmin(): void
    {
        $console = $this->bindConsole();

        $result = $this->callActionController('push', [], true);
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('网站缓存加速成功！', $result['info'] ?? '');
        $this->assertStringContainsString('javascript:location.reload()', strval($result['data'] ?? ''));
        $this->assertCount(1, $console->calls);
        $this->assertSame('optimize:schema', $console->calls[0]['command']);
        $this->assertSame(['--connection=sqlite'], $console->calls[0]['parameters']);
        $this->assertStringContainsString('mode = product', file_get_contents($this->runtimeEnvFile) ?: '');
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统运维管理', $oplog->getData('action'));
        $this->assertSame('刷新发布运行缓存', $oplog->getData('content'));
        $this->assertSame('admin', $oplog->getData('username'));
    }

    public function testClearInvokesClearCommandPreservesModeAndWritesOplogForSuperAdmin(): void
    {
        RuntimeService::set('product');
        $this->app->cache->set('runtime-clear-key', 'present');
        $console = $this->bindConsole();

        $result = $this->callActionController('clear', [], true);
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('清空日志缓存成功！', $result['info'] ?? '');
        $this->assertCount(1, $console->calls);
        $this->assertSame('clear', $console->calls[0]['command']);
        $this->assertSame(['--dir'], $console->calls[0]['parameters']);
        $this->assertNull($this->app->cache->get('runtime-clear-key'));
        $this->assertStringContainsString('mode = product', file_get_contents($this->runtimeEnvFile) ?: '');
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统运维管理', $oplog->getData('action'));
        $this->assertSame('清理网站日志缓存', $oplog->getData('content'));
        $this->assertSame('admin', $oplog->getData('username'));
    }

    public function testDebugSwitchesRuntimeModesAndWritesOplogForSuperAdmin(): void
    {
        $toProduct = $this->callActionController('debug', ['state' => '1'], true);
        $productOplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($toProduct['code'] ?? 0));
        $this->assertSame('已切换为生产模式！', $toProduct['info'] ?? '');
        $this->assertStringContainsString('mode = product', file_get_contents($this->runtimeEnvFile) ?: '');
        $this->assertSame('开发模式切换为生产模式', $productOplog->getData('content'));

        $toDebug = $this->callActionController('debug', [], true);
        $debugOplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($toDebug['code'] ?? 0));
        $this->assertSame('已切换为开发模式！', $toDebug['info'] ?? '');
        $this->assertStringContainsString('mode = debug', file_get_contents($this->runtimeEnvFile) ?: '');
        $this->assertSame('生产模式切换为开发模式', $debugOplog->getData('content'));
        $this->assertSame('admin', $debugOplog->getData('username'));
    }

    public function testPushRejectsNonSuperAdmin(): void
    {
        $console = $this->bindConsole();

        $result = $this->callActionController('push', [], false);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('请使用超管账号操作！', $result['info'] ?? '');
        $this->assertCount(0, $console->calls);
        $this->assertFalse(is_file($this->runtimeEnvFile));
        $this->assertSame(0, SystemOplog::mk()->count());
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
        $this->originRuntimeEnvFile = $this->getRuntimeEnvFile();
        $this->runtimeEnvFile = $this->sandboxPath . '/runtime/.env';
        is_dir(dirname($this->runtimeEnvFile)) || mkdir(dirname($this->runtimeEnvFile), 0777, true);
        $this->setRuntimeEnvFile($this->runtimeEnvFile);
        sysvar('think.admin.runtime', []);
    }

    private function callActionController(string $action, array $payload = [], bool $super = true): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod('POST')
            ->setController('system')
            ->setAction($action);

        $this->bindAdminUser($super);
        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new SystemController($this->app);
            $controller->{$action}();
            self::fail("Expected SystemController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(bool $super): void
    {
        RequestContext::instance()->setAuth([
            'id' => $super ? 10000 : 9101,
            'username' => $super ? 'admin' : 'tester',
            'password' => $this->hashSystemPassword('changed-password'),
        ], '', true);
    }

    private function bindConsole(): object
    {
        $console = new class {
            public array $calls = [];

            public function call(string $command, array $parameters = [], ?string $scene = null): object
            {
                $this->calls[] = compact('command', 'parameters', 'scene');

                return new class {
                    public function fetch(): string
                    {
                        return '';
                    }
                };
            }
        };

        $this->app->instance('console', $console);
        return $console;
    }

    private function getRuntimeEnvFile(): string
    {
        $property = new \ReflectionProperty(RuntimeService::class, 'envFile');
        $property->setAccessible(true);
        return strval($property->getValue());
    }

    private function setRuntimeEnvFile(string $path): void
    {
        $property = new \ReflectionProperty(RuntimeService::class, 'envFile');
        $property->setAccessible(true);
        $property->setValue($path);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
