<?php

declare(strict_types=1);

namespace think\admin\tests;

use plugin\system\controller\Queue as QueueController;
use plugin\worker\model\SystemQueue;
use think\Request;
use think\admin\service\ProcessService as ProcessRuntime;
use think\admin\service\QueueService as QueueRuntime;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;

/**
 * @internal
 * @coversNothing
 */
class QueueControllerTest extends SqliteIntegrationTestCase
{
    protected function defineSchema(): void
    {
        $this->createSystemQueueTable();
    }

    protected function afterSchemaCreated(): void
    {
        $this->app->bind([
            ProcessRuntime::BIND_NAME => \plugin\worker\service\ProcessService::class,
            QueueRuntime::BIND_NAME   => \plugin\worker\service\QueueService::class,
        ]);
    }

    public function testIndexFiltersQueueRowsAndBuildsStatusSummary(): void
    {
        $this->createSystemQueueFixture([
            'code'        => 'QHIT000000000001',
            'title'       => '命中任务',
            'command'     => 'xadmin:test queue --hit',
            'status'      => 1,
            'loops_time'  => 0,
            'create_time' => '2026-03-10 08:00:00',
        ]);
        $this->createSystemQueueFixture([
            'code'        => 'QLOCK00000000001',
            'title'       => '执行任务',
            'command'     => 'xadmin:test queue --run',
            'status'      => 2,
            'loops_time'  => 30,
            'create_time' => '2026-03-10 09:00:00',
        ]);
        $this->createSystemQueueFixture([
            'code'        => 'QDONE00000000001',
            'title'       => '完成任务',
            'command'     => 'xadmin:test queue --done',
            'status'      => 3,
            'loops_time'  => 0,
            'create_time' => '2026-03-10 10:00:00',
        ]);
        $this->createSystemQueueFixture([
            'code'        => 'QERR000000000001',
            'title'       => '失败任务',
            'command'     => 'xadmin:test queue --error',
            'status'      => 4,
            'loops_time'  => 0,
            'create_time' => '2026-03-10 11:00:00',
        ]);
        $this->createSystemQueueFixture([
            'code'        => 'QOLD000000000001',
            'title'       => '跨日任务',
            'command'     => 'xadmin:test queue --old',
            'status'      => 1,
            'loops_time'  => 0,
            'create_time' => '2026-03-09 08:00:00',
        ]);

        $result = $this->callIndexController([
            'output'      => 'json',
            'status'      => 1,
            'title'       => '命中',
            'create_time' => '2026-03-10 - 2026-03-10',
            '_field_'     => 'id',
            '_order_'     => 'asc',
            'page'        => 1,
            'limit'       => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('QHIT000000000001', $result['data']['list'][0]['code'] ?? '');
        $this->assertSame(2, intval($result['data']['extra']['pre'] ?? 0));
        $this->assertSame(1, intval($result['data']['extra']['dos'] ?? 0));
        $this->assertSame(1, intval($result['data']['extra']['oks'] ?? 0));
        $this->assertSame(1, intval($result['data']['extra']['ers'] ?? 0));
    }

    public function testIndexPaginatesQueuesAndFallsBackToDefaultLimit(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->createSystemQueueFixture([
                'code'        => sprintf('QPAGE%011d', $i),
                'title'       => sprintf('分页任务-%02d', $i),
                'command'     => sprintf('xadmin:test queue --page=%02d', $i),
                'status'      => 1,
                'create_time' => sprintf('2026-03-10 08:%02d:00', $i % 60),
            ]);
        }

        $result = $this->callIndexController([
            'output'  => 'json',
            'status'  => 1,
            '_field_' => 'id',
            '_order_' => 'asc',
            'page'    => 2,
            'limit'   => 999,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(21, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(20, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('分页任务-21', $result['data']['list'][0]['title'] ?? '');
    }

    public function testRedoResetsFinishedQueueToWaitingState(): void
    {
        $queue = $this->createSystemQueueFixture([
            'code'       => 'QREDO00000000001',
            'status'     => 4,
            'exec_pid'   => 12345,
            'exec_time'  => time() - 300,
            'attempts'   => 2,
            'message'    => '旧执行日志',
            'exec_desc'  => '旧失败结果',
        ]);

        $before = time();
        $result = $this->callActionController('redo', [
            'code' => 'QREDO00000000001',
        ]);
        $afterQueue = SystemQueue::mk()->where(['id' => $queue->getAttr('id')])->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('任务重置成功！', $result['info'] ?? '');
        $this->assertSame('QREDO00000000001', $result['data'] ?? '');
        $this->assertSame(1, intval($afterQueue->getAttr('status')));
        $this->assertSame(0, intval($afterQueue->getAttr('exec_pid')));
        $this->assertGreaterThanOrEqual($before, intval(SystemQueue::mk()->where(['id' => $queue->getAttr('id')])->value('exec_time')));
    }

    public function testCleanRegistersScheduledCleanupQueue(): void
    {
        $result = $this->callActionController('clean');

        $queue = SystemQueue::mk()->where(['title' => '定时清理系统运行数据'])->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('创建任务成功！', $result['info'] ?? '');
        $this->assertTrue($queue->isExists());
        $this->assertSame('xadmin:queue clean', $queue->getAttr('command'));
        $this->assertSame(3600, intval($queue->getAttr('loops_time')));
        $this->assertSame($queue->getAttr('code'), $result['data'] ?? '');
    }

    public function testRemoveDeletesSelectedQueues(): void
    {
        $first = $this->createSystemQueueFixture(['code' => 'QREMOVE000000001']);
        $second = $this->createSystemQueueFixture(['code' => 'QREMOVE000000002']);
        $keep = $this->createSystemQueueFixture(['code' => 'QREMOVE000000003']);

        $result = $this->callActionController('remove', [
            'id' => $first->getAttr('id') . ',' . $second->getAttr('id'),
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('数据删除成功！', $result['info'] ?? '');
        $this->assertSame(0, SystemQueue::mk()->whereIn('id', [$first->getAttr('id'), $second->getAttr('id')])->count());
        $this->assertTrue(SystemQueue::mk()->where(['id' => $keep->getAttr('id')])->findOrEmpty()->isExists());
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('queue')
            ->setAction('index');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new QueueController($this->app);
            $controller->index();
            self::fail('Expected QueueController::index to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callActionController(string $action, array $post = []): array
    {
        $request = (new Request())
            ->withGet($post)
            ->withPost($post)
            ->setMethod('POST')
            ->setController('queue')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new QueueController($this->app);
            $controller->{$action}();
            self::fail("Expected QueueController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        RequestContext::instance()->setAuth([
            'id'       => 9101,
            'username' => 'tester',
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
