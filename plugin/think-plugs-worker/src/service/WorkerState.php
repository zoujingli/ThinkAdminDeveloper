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

namespace plugin\worker\service;

/**
 * Cross-platform worker process inspector.
 */
class WorkerState
{
    public function __construct(protected array $service) {}

    /**
     * Describe current runtime state.
     */
    public function describe(): array
    {
        $pid = $this->pid();
        if (!$this->isWindows() && $pid > 0) {
            $processes = $this->queryUnixProcesses($pid);
            if ($processes !== []) {
                return ['running' => true, 'pid' => $pid, 'processes' => $processes];
            }

            $this->clearPidFile();
        }

        $processes = $this->queryServiceProcesses();

        if ($processes === []) {
            return ['running' => false, 'pid' => 0, 'processes' => []];
        }

        return ['running' => true, 'pid' => $this->resolveProcessPid($processes), 'processes' => $processes];
    }

    /**
     * Read master pid from runtime file.
     */
    public function pid(): int
    {
        $file = $this->pidFile();
        if (!is_file($file)) {
            return 0;
        }

        return max(0, intval(trim((string)file_get_contents($file))));
    }

    /**
     * Send a graceful stop signal.
     */
    public function stop(int $timeout = 5): bool
    {
        if ($this->isWindows()) {
            $pids = $this->normalizeProcessIds($this->queryServiceProcesses());
            if ($pids === []) {
                $this->clearPidFile();
                return true;
            }

            foreach ($pids as $pid) {
                $this->closeProcess($pid);
            }
            return $this->waitStopped($timeout);
        }

        $info = $this->describe();
        if (!$info['running']) {
            return true;
        }

        if ($info['pid'] > 0) {
            $this->stopProcess($info['pid']);
            if ($this->waitStopped($timeout)) {
                return true;
            }
        }

        foreach ($this->normalizeProcessIds($this->queryServiceProcesses()) as $pid) {
            $this->killProcess($pid);
        }

        return $this->waitStopped(1);
    }

    /**
     * Send a reload signal on POSIX platforms.
     */
    public function reload(): bool
    {
        if ($this->isWindows()) {
            return false;
        }

        $pid = $this->pid();
        if ($pid < 1) {
            $pid = $this->resolveProcessPid($this->queryServiceProcesses());
        }
        if ($pid < 1) {
            return false;
        }

        $this->reloadProcess($pid);
        return true;
    }

    /**
     * Wait until the runtime is online.
     */
    public function waitStarted(int $timeout = 5): bool
    {
        $started = microtime(true);
        do {
            if ($this->describe()['running']) {
                return true;
            }
            usleep(100000);
        } while (microtime(true) < $started + $timeout);

        return false;
    }

    /**
     * Wait until the runtime is offline.
     */
    public function waitStopped(int $timeout = 5): bool
    {
        $started = microtime(true);
        do {
            if (!$this->describe()['running']) {
                return true;
            }
            usleep(100000);
        } while (microtime(true) < $started + $timeout);

        return false;
    }

    /**
     * Resolve runtime pid file path.
     */
    protected function pidFile(): string
    {
        return (string)$this->service['runtime']['pidFile'];
    }

    /**
     * Remove stale runtime pid file.
     */
    protected function clearPidFile(): void
    {
        @unlink($this->pidFile());
    }

    /**
     * Determine whether the runtime is running on Windows.
     */
    protected function isWindows(): bool
    {
        return ProcessService::isWin();
    }

    /**
     * Query service processes using the worker serve command signature.
     *
     * @return array<int, array{pid:string,cmd:string}>
     */
    protected function queryServiceProcesses(): array
    {
        return ProcessService::workerQuery((string)($this->service['name'] ?? ''));
    }

    /**
     * Terminate a single process.
     */
    protected function closeProcess(int $pid): bool
    {
        return ProcessService::close($pid);
    }

    /**
     * Gracefully stop a Unix process.
     */
    protected function stopProcess(int $pid): void
    {
        ProcessService::exec("kill {$pid}");
    }

    /**
     * Force kill a Unix process.
     */
    protected function killProcess(int $pid): void
    {
        ProcessService::exec('kill -' . \SIGKILL . " {$pid}");
    }

    /**
     * Reload a Unix process.
     */
    protected function reloadProcess(int $pid): void
    {
        ProcessService::exec('kill -' . \SIGUSR2 . " {$pid}");
    }

    /**
     * Extract unique numeric PIDs from a process list.
     *
     * @param array<int, array{pid:string,cmd:string}> $processes
     * @return int[]
     */
    protected function normalizeProcessIds(array $processes): array
    {
        $items = [];
        foreach ($processes as $process) {
            $pid = intval($process['pid'] ?? 0);
            if ($pid > 0) {
                $items[$pid] = $pid;
            }
        }

        return array_values($items);
    }

    /**
     * Resolve the most likely master PID from a process list.
     *
     * @param array<int, array{pid:string,cmd:string}> $processes
     */
    protected function resolveProcessPid(array $processes): int
    {
        $pids = $this->normalizeProcessIds($processes);
        if ($pids === []) {
            return 0;
        }

        sort($pids, \SORT_NUMERIC);
        return $pids[0];
    }

    /**
     * Query a Unix master process and its worker children.
     *
     * @return array<int, array{pid:string,cmd:string}>
     */
    protected function queryUnixProcesses(int $masterPid): array
    {
        $items = [];
        foreach (ProcessService::exec("ps -p {$masterPid} -o pid=,command=", true) as $line) {
            if ($item = $this->parseUnixProcess($line, 2)) {
                $items[] = $item;
            }
        }

        foreach (ProcessService::exec('ps -ax -o pid=,ppid=,command=', true) as $line) {
            $item = $this->parseUnixProcess($line, 3);
            if ($item === null) {
                continue;
            }

            $parts = preg_split('#\s+#', trim($line), 3);
            $pid = intval($parts[0] ?? 0);
            $ppid = intval($parts[1] ?? 0);
            if ($ppid === $masterPid) {
                $items[] = ['pid' => (string)$pid, 'cmd' => $item['cmd']];
            }
        }

        return $items;
    }

    /**
     * Parse a ps line with 2 or 3 columns.
     */
    protected function parseUnixProcess(string $line, int $parts): ?array
    {
        $line = trim(preg_replace('#\s+#', ' ', trim($line)));
        if ($line === '') {
            return null;
        }

        $attr = preg_split('#\s+#', $line, $parts);
        if (count($attr) < $parts || !is_numeric($attr[0])) {
            return null;
        }

        return ['pid' => $attr[0], 'cmd' => $attr[$parts - 1] ?? ''];
    }
}
