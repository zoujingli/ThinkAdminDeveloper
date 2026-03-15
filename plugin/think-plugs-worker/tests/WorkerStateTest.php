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
use plugin\worker\service\WorkerState;

/**
 * @internal
 * @coversNothing
 */
class WorkerStateTest extends TestCase
{
    public function testDescribeFallsBackToServiceQueryOnWindows(): void
    {
        $state = new class($this->service()) extends WorkerState {
            public array $serviceProcesses = [];

            protected function isWindows(): bool
            {
                return true;
            }

            protected function queryServiceProcesses(): array
            {
                return $this->serviceProcesses;
            }
        };

        $state->serviceProcesses = [
            ['pid' => '321', 'cmd' => 'php think xadmin:worker serve queue -d'],
            ['pid' => '654', 'cmd' => 'php think xadmin:worker serve queue -d'],
        ];

        $info = $state->describe();

        $this->assertTrue($info['running']);
        $this->assertSame(321, $info['pid']);
        $this->assertCount(2, $info['processes']);
    }

    public function testStopTerminatesAllMatchedWindowsProcesses(): void
    {
        $state = new class($this->service()) extends WorkerState {
            public array $serviceProcesses = [];

            public array $closedPids = [];

            protected function isWindows(): bool
            {
                return true;
            }

            protected function queryServiceProcesses(): array
            {
                return $this->serviceProcesses;
            }

            protected function closeProcess(int $pid): bool
            {
                $this->closedPids[] = $pid;
                return true;
            }

            public function waitStopped(int $timeout = 5): bool
            {
                return true;
            }
        };

        $state->serviceProcesses = [
            ['pid' => '321', 'cmd' => 'php think xadmin:worker serve queue -d'],
            ['pid' => '321', 'cmd' => 'php think xadmin:worker serve queue -d'],
            ['pid' => '654', 'cmd' => 'php think xadmin:worker serve queue -d'],
        ];

        $this->assertTrue($state->stop());
        $this->assertSame([321, 654], $state->closedPids);
    }

    public function testDescribeFallsBackToServiceQueryOnUnixWhenPidIsMissing(): void
    {
        $state = new class($this->service()) extends WorkerState {
            public array $serviceProcesses = [];

            public function pid(): int
            {
                return 0;
            }

            protected function isWindows(): bool
            {
                return false;
            }

            protected function queryServiceProcesses(): array
            {
                return $this->serviceProcesses;
            }
        };

        $state->serviceProcesses = [
            ['pid' => '654', 'cmd' => 'php think xadmin:worker serve queue -d'],
            ['pid' => '321', 'cmd' => 'php think xadmin:worker serve queue -d'],
        ];

        $info = $state->describe();

        $this->assertTrue($info['running']);
        $this->assertSame(321, $info['pid']);
        $this->assertCount(2, $info['processes']);
    }

    public function testReloadFallsBackToResolvedUnixPidWhenPidFileIsMissing(): void
    {
        $state = new class($this->service()) extends WorkerState {
            public array $serviceProcesses = [];

            public array $reloadedPids = [];

            public function pid(): int
            {
                return 0;
            }

            protected function isWindows(): bool
            {
                return false;
            }

            protected function queryServiceProcesses(): array
            {
                return $this->serviceProcesses;
            }

            protected function reloadProcess(int $pid): void
            {
                $this->reloadedPids[] = $pid;
            }
        };

        $state->serviceProcesses = [
            ['pid' => '654', 'cmd' => 'php think xadmin:worker serve queue -d'],
            ['pid' => '321', 'cmd' => 'php think xadmin:worker serve queue -d'],
        ];

        $this->assertTrue($state->reload());
        $this->assertSame([321], $state->reloadedPids);
    }

    /**
     * @return array<string, mixed>
     */
    private function service(): array
    {
        return [
            'name' => 'queue',
            'runtime' => ['pidFile' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'worker-state-test.pid'],
        ];
    }
}
