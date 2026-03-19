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
