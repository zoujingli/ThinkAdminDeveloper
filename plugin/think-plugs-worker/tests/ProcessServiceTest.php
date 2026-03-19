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

use PHPUnit\Framework\TestCase;
use plugin\worker\service\ProcessService;
use think\admin\service\RuntimeService;
use think\App;

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

    public function testWorkerSignatureUsesCanonicalServeCommand(): void
    {
        $this->assertSame('xadmin:worker serve queue', ProcessService::workerSignature('queue'));
    }

    public function testEntryScriptPrefersThinkInProjectMode(): void
    {
        $this->assertSame(WORKER_TEST_PROJECT_ROOT . DIRECTORY_SEPARATOR . 'think', ProcessService::entryScript());
    }

    public function testWorkingDirectoryPrefersProjectRootInProjectMode(): void
    {
        $this->assertSame(WORKER_TEST_PROJECT_ROOT, ProcessService::workingDirectory());
    }

    public function testQueryPidCanDescribeCurrentProcess(): void
    {
        $process = ProcessService::queryPid(getmypid());

        $this->assertNotNull($process);
        $this->assertSame((string)getmypid(), $process['pid']);
        $this->assertNotSame('', $process['cmd']);
    }
}
