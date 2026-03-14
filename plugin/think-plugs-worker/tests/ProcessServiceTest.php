<?php

declare(strict_types=1);

namespace plugin\worker\tests;

use PHPUnit\Framework\TestCase;
use plugin\worker\service\ProcessService;
use think\App;
use think\admin\service\RuntimeService;

/**
 * @internal
 * @coversNothing
 */
class ProcessServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $app = new App(WORKER_TEST_PROJECT_ROOT);
        RuntimeService::init($app);
    }

    public function testWorkerCommandBuildsExpectedFlags(): void
    {
        $command = ProcessService::workerCommand('start', 'queue', true, ['host' => '0.0.0.0', 'port' => 2360]);
        $this->assertSame('xadmin:worker start queue -d --host 0.0.0.0 --port 2360', $command);
    }

    public function testQueryPidCanDescribeCurrentProcess(): void
    {
        $process = ProcessService::queryPid(getmypid());

        $this->assertNotNull($process);
        $this->assertSame((string)getmypid(), $process['pid']);
        $this->assertNotSame('', $process['cmd']);
    }
}
