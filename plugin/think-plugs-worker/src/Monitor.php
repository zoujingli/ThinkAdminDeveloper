<?php

// +----------------------------------------------------------------------
// | Worker Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// | 参考资料 ( https://github.com/walkor/webman/blob/master/process/Monitor.php )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-worker
// | github 代码仓库：https://github.com/zoujingli/think-plugs-worker
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\worker;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use think\admin\service\ProcessService;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 资源监控管理器
 * @class Monitor
 * @package plugin\worker
 */
abstract class Monitor
{
    /**
     * 监控目录
     * @var array
     */
    private static $paths = [];

    /**
     * 内存限制
     * @var string
     */
    private const defaultMaxMemory = '1G';

    /**
     * 暂停锁定标记
     * @var string
     */
    private static $lockFile;
    private static $changeTimerId = -1;
    private static $memoryTimerId = -1;

    /**
     * 监听锁定标记
     * @return string
     */
    private static function _tag(): string
    {
        return self::$lockFile ?: (self::$lockFile = syspath('runtime/monitor.lock'));
    }

    /**
     * Pause Monitor
     * @return void
     */
    public static function pause()
    {
        file_put_contents(self::_tag(), time());
    }

    /**
     * Resume monitor
     * @return void
     */
    public static function resume(): void
    {
        clearstatcache();
        if (is_file(self::_tag())) {
            unlink(self::$lockFile);
        }
    }

    /**
     * Whether monitor is paused
     * @return bool
     */
    public static function isPaused(): bool
    {
        clearstatcache();
        return file_exists(self::_tag());
    }

    /**
     * Enable Files Monitor
     * @param array $dirs 监听目录
     * @param array $exts 文件后缀
     * @param integer $interval 定时器时间
     * @return boolean
     */
    public static function enableChangeMonitor(array $dirs = [], array $exts = ['php'], int $interval = 60): bool
    {
        if ($interval <= 0) return false;
        if (!Worker::getAllWorkers()) return false;
        if (in_array('exec', explode(',', ini_get('disable_functions')), true)) {
            echo "\nMonitor file change turned off because exec() has been disabled by disable_functions setting in " . PHP_CONFIG_FILE_PATH . "/php.ini\n";
            return false;
        } else {
            foreach ($dirs as $dir) self::$paths[$dir] = $exts;
            if (self::$changeTimerId > -1) Timer::del(self::$changeTimerId);
            self::$changeTimerId = Timer::add($interval, static function () {
                if (self::isPaused()) return false;
                foreach (self::$paths as $path => $exts) {
                    if (self::_checkFilesChange($path, $exts)) {
                        return true;
                    }
                }
                return false;
            });
            return true;
        }
    }

    /**
     * Check Files Change
     * @param string $path
     * @param array $exts
     * @return boolean
     */
    private static function _checkFilesChange(string $path, array $exts): bool
    {
        static $lastMtime, $tooManyFilesCheck;
        if (!$lastMtime) $lastMtime = time();

        clearstatcache();
        if (!is_dir($path)) {
            if (!is_file($path)) return false;
            $iterator = [new SplFileInfo($path)];
        } else {
            // recursive traversal directory
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS));
        }
        $count = 0;
        foreach ($iterator as $file) {
            $count++;
            /** var SplFileInfo $file */
            if (is_dir($file->getRealPath())) continue;
            // check mtime
            if ($lastMtime < $file->getMTime() && (in_array('*', $exts) || in_array($file->getExtension(), $exts, true))) {
                if ($file->getExtension() === 'php') {
                    exec(ProcessService::php("-l {$file}"), $out, $var);
                    if ($var) continue;
                }
                $lastMtime = $file->getMTime();
                echo "{$file} update and reload\n";
                // send SIGUSR1 signal to master process for reload
                if (DIRECTORY_SEPARATOR === '/') {
                    posix_kill(posix_getppid(), SIGUSR1);
                    break;
                } else {
                    return true;
                }
            }
        }
        if (!$tooManyFilesCheck && $count > 10000) {
            echo "Monitor: There are too many files ($count files) in $path which makes file monitoring very slow\n";
            $tooManyFilesCheck = 1;
        }
        return false;
    }

    /**
     * Enable Member Monitor，only windows
     * @param ?string $limit
     * @param integer $interval
     * @return boolean
     */
    public static function enableMemoryMonitor(?string $limit = null, int $interval = 60): bool
    {
        if ($interval <= 0) return false;
        if (!Worker::getAllWorkers()) return false;
        if (!ProcessService::isUnix()) return false;
        if ($memoryLimit = self::_getMemoryLimit($limit ?: self::defaultMaxMemory)) {
            self::$memoryTimerId > -1 && Timer::del(self::$memoryTimerId);
            self::$memoryTimerId = Timer::add($interval, [self::class, 'checkMemory'], [$memoryLimit]);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check Memory Limit
     * @param $memoryLimit
     * @return void
     */
    public static function checkMemory($memoryLimit)
    {
        if (static::isPaused() || $memoryLimit <= 0) return;
        $ppid = posix_getppid();
        $childrenFile = "/proc/$ppid/task/$ppid/children";
        if (!is_file($childrenFile) || !($children = file_get_contents($childrenFile))) {
            return;
        }
        foreach (explode(' ', $children) as $pid) {
            $pid = (int)$pid;
            $statusFile = "/proc/$pid/status";
            if (!is_file($statusFile) || !($status = file_get_contents($statusFile))) {
                continue;
            }
            $mem = 0;
            if (preg_match('/VmRSS\s*?:\s*?(\d+?)\s*?kB/', $status, $match)) {
                $mem = $match[1];
            }
            $mem = (int)($mem / 1024);
            $mem >= $memoryLimit && posix_kill($pid, SIGINT);
        }
    }

    /**
     * Get memory limit
     * @param mixed $memoryLimit
     * @return float
     */
    private static function _getMemoryLimit($memoryLimit): float
    {
        if ($memoryLimit === 0) {
            return floatval(0);
        }
        $usePhpIni = false;
        if (!$memoryLimit) {
            $usePhpIni = true;
            $memoryLimit = ini_get('memory_limit');
        }
        if ($memoryLimit == -1) return floatval(0);
        $unit = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        if ($unit === 'g') {
            $memoryLimit = 1024 * intval($memoryLimit);
        } else if ($unit === 'm') {
            $memoryLimit = intval($memoryLimit);
        } else if ($unit === 'k') {
            $memoryLimit = intval($memoryLimit / 1024);
        } else {
            $memoryLimit = intval($memoryLimit / 1024 / 1024);
        }
        if ($memoryLimit < 30) $memoryLimit = 30;
        if ($usePhpIni) $memoryLimit = (int)(0.8 * $memoryLimit);
        return floatval($memoryLimit);
    }
}