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

use Symfony\Component\Process\Process;
use think\admin\extend\CodeToolkit;
use think\admin\Library;
use think\admin\service\ModuleService;
use think\admin\service\Service;

/**
 * Worker-backed process runtime.
 * Provides shell operations and worker lifecycle controls.
 */
class ProcessService extends Service
{
    protected ?WorkerConfig $workers = null;

    public function workerConfig(): WorkerConfig
    {
        return $this->workers ??= new WorkerConfig($this->app);
    }

    /**
     * 生成 PHP 指令.
     */
    public static function php(string $args = ''): string
    {
        return ModuleService::getPhpExec() . ' ' . $args;
    }

    /**
     * 生成 Think 指令.
     */
    public static function think(string $args = '', bool $simple = false): string
    {
        $command = syspath('think') . ' ' . $args;
        return $simple ? $command : static::php($command);
    }

    /**
     * 生成 Composer 指令.
     */
    public static function composer(string $args = ''): string
    {
        static $comExec;
        if (empty($comExec)) {
            $comExec = ModuleService::getRunVar('com');
            $comExec = static::isFile($comExec) ? static::php($comExec) : 'composer';
        }
        $root = Library::$sapp->getRootPath();
        return "{$comExec} -d {$root} {$args}";
    }

    /**
     * 创建 Think 进程.
     */
    public static function thinkExec(string $args, int $usleep = 0, bool $doQuery = false): array
    {
        static::create(static::think($args), $usleep);
        return $doQuery ? static::query(static::think($args, true)) : [];
    }

    /**
     * 检查 Think 进程.
     */
    public static function thinkQuery(string $args): array
    {
        return static::query(static::think($args, true));
    }

    /**
     * 创建异步进程.
     */
    public static function create(string $command, int $usleep = 0): void
    {
        if (static::isWin()) {
            $binary = __DIR__ . '/bin/console.exe';
            static::exec("\"{$binary}\" {$command}");
        } else {
            static::exec("{$command} > /dev/null 2>&1 &");
        }
        $usleep > 0 && usleep($usleep);
    }

    /**
     * 查询进程列表.
     *
     * @return array<int, array{pid:string,cmd:string}>
     */
    public static function query(string $cmd, string $name = 'php.exe'): array
    {
        $list = [];
        if (static::isWin()) {
            foreach (static::queryWindows($cmd, $name) as $item) {
                $list[] = $item;
            }
        } else {
            $lines = static::exec("ps ax|grep -v grep|grep \"{$cmd}\"", true);
            foreach ($lines as $line) {
                if (is_numeric(stripos($line, $cmd))) {
                    $attr = explode(' ', trim((string)preg_replace('#\s+#', ' ', $line)));
                    [$pid] = [array_shift($attr), array_shift($attr), array_shift($attr), array_shift($attr)];
                    $list[] = ['pid' => (string)$pid, 'cmd' => join(' ', $attr)];
                }
            }
        }
        return $list;
    }

    /**
     * 关闭指定进程.
     */
    public static function close(int $pid): bool
    {
        if (static::isWin()) {
            static::exec("taskkill /PID {$pid} /T /F");
        } else {
            static::exec("kill -9 {$pid}");
        }
        return true;
    }

    /**
     * Query one process by PID.
     *
     * @return null|array{pid:string,cmd:string}
     */
    public static function queryPid(int $pid): ?array
    {
        if ($pid < 1) {
            return null;
        }

        if (static::isWin()) {
            $script = sprintf(
                '$item = Get-CimInstance Win32_Process -Filter "ProcessId = %d" | Select-Object -First 1;' . "\n"
                . 'if ($null -ne $item) { "{0}`t{1}" -f $item.ProcessId, $item.CommandLine }',
                $pid,
            );
            foreach (static::powershell($script, true) as $line) {
                if ($item = static::parseWindowsProcessLine($line)) {
                    return $item;
                }
            }

            return null;
        }

        foreach (static::exec("ps -p {$pid} -o pid=,command=", true) as $line) {
            $line = trim((string)preg_replace('#\s+#', ' ', trim($line)));
            if ($line === '') {
                continue;
            }

            $attr = preg_split('#\s+#', $line, 2);
            if (count($attr) === 2 && is_numeric($attr[0])) {
                return ['pid' => $attr[0], 'cmd' => $attr[1]];
            }
        }

        return null;
    }

    /**
     * 立即执行指令.
     *
     * @return array<int, string>|string
     */
    public static function exec(string $command, bool $outarr = false, ?callable $callable = null)
    {
        $process = Process::fromShellCommandline($command)->setWorkingDirectory(Library::$sapp->getRootPath());
        $process->run(is_callable($callable) ? static function ($type, $text) use ($callable, $process) {
            call_user_func($callable, $process, $type, trim(CodeToolkit::text2utf8($text))) === true && $process->stop();
        } : null);
        $output = str_replace("\r\n", "\n", CodeToolkit::text2utf8($process->getOutput()));
        return $outarr ? explode("\n", $output) : trim($output);
    }

    /**
     * 输出命令行消息.
     */
    public static function message(string $message, int $backline = 0): void
    {
        while ($backline-- > 0) {
            $message = "\033[1A\r\033[K{$message}";
        }
        print_r($message . PHP_EOL);
    }

    /**
     * 判断系统类型 WINDOWS.
     */
    public static function isWin(): bool
    {
        return PATH_SEPARATOR === ';';
    }

    /**
     * 判断系统类型 UNIX.
     */
    public static function isUnix(): bool
    {
        return PATH_SEPARATOR !== ';';
    }

    /**
     * 检查文件是否存在.
     */
    public static function isFile(string $file): bool
    {
        try {
            return $file !== '' && is_file($file);
        } catch (\Error|\Exception $exception) {
            try {
                if (static::isWin()) {
                    return static::exec("if exist \"{$file}\" echo 1") === '1';
                }
                return static::exec("if [ -f \"{$file}\" ];then echo 1;fi") === '1';
            } catch (\Error|\Exception $exception) {
                return false;
            }
        }
    }

    /**
     * @param array<string, mixed>|string $service
     */
    public function workerService(array|string $service): array
    {
        return is_array($service) ? $service : $this->workerConfig()->service($service);
    }

    /**
     * @param array<string, mixed>|string $service
     */
    public function workerState(array|string $service): WorkerState
    {
        return new WorkerState($this->workerService($service));
    }

    /**
     * @param array<string, mixed>|string $service
     */
    public function workerDescribe(array|string $service): array
    {
        return $this->workerState($service)->describe();
    }

    /**
     * @param array<string, mixed>|string $service
     */
    public function workerStart(array|string $service, bool $daemon = true, array $options = [], int $timeout = 5): bool
    {
        $service = $this->workerService($service);
        $state = $this->workerState($service);
        if ($state->describe()['running']) {
            return true;
        }

        static::thinkExec(self::workerCommand('serve', $service['name'], $daemon, $options));
        return $state->waitStarted($timeout);
    }

    /**
     * @param array<string, mixed>|string $service
     */
    public function workerStop(array|string $service, int $timeout = 5): bool
    {
        return $this->workerState($service)->stop($timeout);
    }

    /**
     * @param array<string, mixed>|string $service
     */
    public function workerRestart(array|string $service, bool $daemon = true, array $options = [], int $timeout = 5): bool
    {
        $service = $this->workerService($service);
        $state = $this->workerState($service);
        if ($state->describe()['running'] && !$state->stop($timeout)) {
            return false;
        }

        return $this->workerStart($service, $daemon, $options, $timeout);
    }

    /**
     * Reload on POSIX and degrade to restart on Windows.
     *
     * @param array<string, mixed>|string $service
     * @return 'reload'|'restart'|false
     */
    public function workerReload(array|string $service, bool $daemon = true, array $options = [], int $timeout = 5): false|string
    {
        $service = $this->workerService($service);
        $state = $this->workerState($service);
        if (!$state->describe()['running']) {
            return false;
        }

        if (static::isWin()) {
            return $this->workerRestart($service, $daemon, $options, $timeout) ? 'restart' : false;
        }

        return $state->reload() ? 'reload' : false;
    }

    /**
     * Spawn a detached worker control command.
     *
     * @param array<string, mixed>|string $service
     */
    public function workerSpawn(string $action, array|string $service, bool $daemon = true, array $options = [], int $usleep = 0): void
    {
        static::thinkExec(self::workerCommand($action, is_array($service) ? (string)$service['name'] : $service, $daemon, $options), $usleep);
    }

    /**
     * Build a public worker control command.
     */
    public static function workerCommand(string $action, string $target, bool $daemon = true, array $options = []): string
    {
        $command = "xadmin:worker {$action} {$target}";
        if ($daemon && in_array($action, ['start', 'serve', 'restart'], true)) {
            $command .= ' -d';
        }
        if (!empty($options['host']) && is_string($options['host'])) {
            $command .= " --host {$options['host']}";
        }
        if (!empty($options['port']) && is_numeric($options['port'])) {
            $command .= ' --port ' . intval($options['port']);
        }
        return $command;
    }

    protected function initialize(): void
    {
        $this->workers = new WorkerConfig($this->app);
    }

    /**
     * Query Windows processes by image name and command substring.
     *
     * @return array<int, array{pid:string,cmd:string}>
     */
    protected static function queryWindows(string $cmd, string $name): array
    {
        $items = [];
        $script = '$needle = ' . static::powershellLiteral($cmd) . ";\n"
            . '$name = ' . static::powershellLiteral($name) . ";\n"
            . "\$items = Get-CimInstance Win32_Process -Filter (\"Name = ''{0}''\" -f (\$name -replace \"'\", \"''\"));\n"
            . 'foreach ($item in $items) { if ($item.CommandLine -and $item.CommandLine.Contains($needle)) { "{0}`t{1}" -f $item.ProcessId, $item.CommandLine } }';

        foreach (static::powershell($script, true) as $line) {
            if ($item = static::parseWindowsProcessLine($line)) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * Execute a PowerShell script and return its normalized output.
     *
     * @return array<int, string>|string
     */
    protected static function powershell(string $script, bool $outarr = false)
    {
        $process = new Process([
            'powershell',
            '-NoProfile',
            '-NonInteractive',
            '-ExecutionPolicy',
            'Bypass',
            '-Command',
            $script,
        ], Library::$sapp->getRootPath());
        $process->run();
        $output = str_replace("\r\n", "\n", CodeToolkit::text2utf8($process->getOutput()));
        return $outarr ? explode("\n", trim($output)) : trim($output);
    }

    /**
     * Build a PowerShell here-string literal.
     */
    protected static function powershellLiteral(string $value): string
    {
        return "@'\n{$value}\n'@";
    }

    /**
     * Parse a PowerShell process output line.
     *
     * @return null|array{pid:string,cmd:string}
     */
    protected static function parseWindowsProcessLine(string $line): ?array
    {
        $line = trim($line);
        if ($line === '') {
            return null;
        }

        [$pid, $cmd] = array_pad(explode("\t", $line, 2), 2, '');
        if (!is_numeric($pid)) {
            return null;
        }

        return ['pid' => trim($pid), 'cmd' => trim($cmd)];
    }
}
