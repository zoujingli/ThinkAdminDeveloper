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

namespace plugin\worker\tests;

use plugin\worker\model\SystemQueue;
use plugin\worker\service\ProcessService;
use plugin\worker\service\QueueService;
use think\admin\Exception;
use think\admin\service\QueueService as QueueRuntime;
use think\admin\tests\Support\SqliteIntegrationTestCase;

/**
 * @internal
 * @coversNothing
 */
class QueueServiceTest extends SqliteIntegrationTestCase
{
    public function testBuildExecHashOnlyDistinguishesLoopAndSingleType(): void
    {
        $loopA = QueueService::buildExecHash('demo', 'xadmin:test', ['page' => 1], 30);
        $loopB = QueueService::buildExecHash('demo', 'xadmin:test', ['page' => 2], 60);
        $once = QueueService::buildExecHash('demo', 'xadmin:test', ['page' => 1], 0);

        $this->assertSame($loopA, $loopB);
        $this->assertNotSame($loopA, $once);
    }

    public function testRegisterRejectsSameTaskWhenPayloadChanges(): void
    {
        $first = QueueService::register('Shared Title', 'xadmin:test queue', 0, ['page' => 1], 0);

        try {
            QueueService::register('Shared Title', 'xadmin:test queue', 0, ['page' => 2], 0);
            $this->fail('Expected duplicate queue registration to fail.');
        } catch (Exception $exception) {
            $this->assertSame('相同类型的任务已在等待或执行中。', $exception->getMessage());
            $this->assertSame($first->getCode(), $exception->getData());
        }

        $this->assertSame(1, SystemQueue::mk()->count());
    }

    public function testResetClearsStaleRuntimeMetadataAndProgress(): void
    {
        $queue = $this->createSystemQueueFixture([
            'code' => 'QRESET0000000001',
            'exec_pid' => 12345,
            'exec_desc' => 'old failure',
            'enter_time' => '111.0000',
            'outer_time' => '222.0000',
            'status' => QueueService::STATE_ERROR,
            'message' => json_encode(['message' => 'stale'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $before = time();
        QueueService::instance([], true)->initialize($queue->getAttr('code'))->reset(30);
        $after = SystemQueue::mk()->where('code', $queue->getAttr('code'))->findOrEmpty()->toArray();
        $progress = json_decode(strval($after['message'] ?? ''), true) ?: [];

        $this->assertSame(0, intval($after['exec_pid'] ?? 0));
        $this->assertSame('', strval($after['exec_desc'] ?? ''));
        $this->assertSame(0.0, floatval($after['enter_time'] ?? 0));
        $this->assertSame(0.0, floatval($after['outer_time'] ?? 0));
        $this->assertSame(QueueService::STATE_WAIT, intval($after['status'] ?? 0));
        $this->assertGreaterThanOrEqual($before + 30, intval($after['exec_time'] ?? 0));
        $this->assertSame(QueueService::STATE_WAIT, intval($progress['status'] ?? 0));
        $this->assertSame('000.00', strval($progress['progress'] ?? ''));
    }

    protected function defineSchema(): void
    {
        $this->createSystemQueueTable();
    }

    protected function afterSchemaCreated(): void
    {
        $this->app->bind([
            ProcessService::BIND_NAME => ProcessService::class,
            QueueRuntime::BIND_NAME => QueueService::class,
        ]);
    }
}
