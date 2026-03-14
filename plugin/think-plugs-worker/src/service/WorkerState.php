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
        if ($pid < 1) {
            return ['running' => false, 'pid' => 0, 'processes' => []];
        }

        $processes = ProcessService::isWin()
            ? $this->queryWindowsProcesses($pid)
            : $this->queryUnixProcesses($pid);

        if ($processes === []) {
            $this->clearPidFile();
            return ['running' => false, 'pid' => 0, 'processes' => []];
        }

        return ['running' => true, 'pid' => $pid, 'processes' => $processes];
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
        $pid = $this->pid();
        if ($pid < 1) {
            return true;
        }

        if (ProcessService::isWin()) {
            ProcessService::exec("taskkill /PID {$pid} /T /F");
            return $this->waitStopped($timeout);
        }

        ProcessService::exec("kill {$pid}");
        if ($this->waitStopped($timeout)) {
            return true;
        }

        ProcessService::exec('kill -' . \SIGKILL . " {$pid}");
        return $this->waitStopped(1);
    }

    /**
     * Send a reload signal on POSIX platforms.
     */
    public function reload(): bool
    {
        $pid = $this->pid();
        if ($pid < 1 || ProcessService::isWin()) {
            return false;
        }

        ProcessService::exec('kill -' . \SIGUSR2 . " {$pid}");
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
     * Query a Windows runtime command line.
     *
     * @return array<int, array{pid:string,cmd:string}>
     */
    protected function queryWindowsProcesses(int $pid): array
    {
        if ($item = ProcessService::queryPid($pid)) {
            return [$item];
        }

        return [];
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
