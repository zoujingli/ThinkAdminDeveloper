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

    public function testQueryPidCanDescribeCurrentProcess(): void
    {
        $process = ProcessService::queryPid(getmypid());

        $this->assertNotNull($process);
        $this->assertSame((string)getmypid(), $process['pid']);
        $this->assertNotSame('', $process['cmd']);
    }
}
