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

use plugin\system\controller\api\Queue as QueueController;
use plugin\system\model\SystemOplog;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

final class FakeConsoleCallResult
{
    public function __construct(private readonly string $message) {}

    public function fetch(): string
    {
        return $this->message;
    }
}

final class FakeConsole
{
    public array $calls = [];

    /**
     * @param array<string,string> $responses
     */
    public function __construct(private readonly array $responses = [], private readonly ?\Throwable $exception = null) {}

    public function call(string $command, array $parameters = [], ?string $scene = null): FakeConsoleCallResult
    {
        $this->calls[] = compact('command', 'parameters', 'scene');

        if ($this->exception instanceof \Throwable) {
            throw $this->exception;
        }

        $key = trim($command . ' ' . join(' ', $parameters));
        $message = $this->responses[$key] ?? $this->responses[$command] ?? '';

        return new FakeConsoleCallResult($message);
    }
}

/**
 * @internal
 * @coversNothing
 */
class ApiQueueControllerTest extends SqliteIntegrationTestCase
{
    public function testStartInvokesWorkerCommandAndWritesOplogForSuperAdmin(): void
    {
        $console = $this->bindConsole([
            'xadmin:worker start queue --daemon' => 'Queue started successfully for pid 1234',
        ]);

        $result = $this->callActionController('start');
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('任务监听服务启动成功！', $result['info'] ?? '');
        $this->assertCount(1, $console->calls);
        $this->assertSame('xadmin:worker', $console->calls[0]['command']);
        $this->assertSame(['start', 'queue', '--daemon'], $console->calls[0]['parameters']);
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统运维管理', $oplog->getData('action'));
        $this->assertSame('尝试启动任务监听服务', $oplog->getData('content'));
        $this->assertSame('admin', $oplog->getData('username'));
    }

    public function testStartRejectsNonSuperAdmin(): void
    {
        $console = $this->bindConsole([
            'xadmin:worker start queue --daemon' => 'Queue started successfully for pid 1234',
        ]);

        $result = $this->callActionController('start', [], false);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('请使用超管账号操作！', $result['info'] ?? '');
        $this->assertCount(0, $console->calls);
        $this->assertSame(0, SystemOplog::mk()->count());
    }

    public function testStopInvokesWorkerCommandAndWritesOplogForSuperAdmin(): void
    {
        $console = $this->bindConsole([
            'xadmin:worker stop queue' => 'stop signal sent to 1234',
        ]);

        $result = $this->callActionController('stop');
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('停止任务监听服务成功！', $result['info'] ?? '');
        $this->assertCount(1, $console->calls);
        $this->assertSame('xadmin:worker', $console->calls[0]['command']);
        $this->assertSame(['stop', 'queue'], $console->calls[0]['parameters']);
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统运维管理', $oplog->getData('action'));
        $this->assertSame('尝试停止任务监听服务', $oplog->getData('content'));
        $this->assertSame('admin', $oplog->getData('username'));
    }

    public function testStatusRendersRunningBadgeForSuperAdmin(): void
    {
        $console = $this->bindConsole([
            'xadmin:worker status queue' => 'process queue 1234 running',
        ]);

        $output = $this->callStatusController(true);

        $this->assertStringContainsString('color-green', $output);
        $this->assertStringContainsString('已启动', $output);
        $this->assertStringContainsString('process queue 1234 running', $output);
        $this->assertCount(1, $console->calls);
        $this->assertSame(['status', 'queue'], $console->calls[0]['parameters']);
    }

    public function testStatusRendersNoPermissionBadgeForNonSuperAdmin(): void
    {
        $console = $this->bindConsole([
            'xadmin:worker status queue' => 'process queue 1234 running',
        ]);

        $output = $this->callStatusController(false);

        $this->assertStringContainsString('color-red', $output);
        $this->assertStringContainsString('无权限', $output);
        $this->assertStringContainsString('只有超级管理员才能操作！', $output);
        $this->assertCount(0, $console->calls);
    }

    public function testProgressReturnsDecodedQueueMessage(): void
    {
        $queue = $this->createSystemQueueFixture([
            'code' => 'QUEUE_PROGRESS_001',
            'message' => json_encode([
                'status' => 'working',
                'progress' => 66,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $result = $this->callActionController('progress', ['code' => $queue->getData('code')]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('获取任务进度成功！', $result['info'] ?? '');
        $this->assertSame('working', $result['data']['status'] ?? null);
        $this->assertSame(66, $result['data']['progress'] ?? null);
    }

    protected function defineSchema(): void
    {
        $this->createSystemQueueTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
    }

    private function bindConsole(array $responses = [], ?\Throwable $exception = null): FakeConsole
    {
        $console = new FakeConsole($responses, $exception);
        $this->app->instance('console', $console);

        return $console;
    }

    private function callStatusController(bool $super): string
    {
        $request = (new Request())
            ->setMethod('GET')
            ->setController('queue')
            ->setAction('status');

        $this->bindAdminUser($super);
        $this->setRequestPayload($request, []);
        $this->app->instance('request', $request);

        ob_start();
        (new QueueController($this->app))->status();

        return strval(ob_get_clean());
    }

    private function callActionController(string $action, array $payload = [], bool $super = true): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod('POST')
            ->setController('queue')
            ->setAction($action);

        $this->bindAdminUser($super);
        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new QueueController($this->app);
            $controller->{$action}();
            self::fail("Expected QueueController::{$action} to throw HttpResponseException.");
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

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
