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

namespace think\admin\service;

use think\admin\Service;
use think\Container;

/**
 * Standard process facade.
 * The concrete runtime implementation is provided by ThinkPlugsWorker.
 */
final class ProcessService extends Service
{
    public const BIND_NAME = 'think.admin.runtime.process';

    /**
     * 生成 PHP 指令.
     */
    public static function php(string $args = ''): string
    {
        $provider = self::providerClass();
        return $provider::php($args);
    }

    /**
     * 生成 Think 指令.
     */
    public static function think(string $args = '', bool $simple = false): string
    {
        $provider = self::providerClass();
        return $provider::think($args, $simple);
    }

    /**
     * 生成 Composer 指令.
     */
    public static function composer(string $args = ''): string
    {
        $provider = self::providerClass();
        return $provider::composer($args);
    }

    /**
     * 创建 Think 进程.
     */
    public static function thinkExec(string $args, int $usleep = 0, bool $doQuery = false): array
    {
        $provider = self::providerClass();
        return $provider::thinkExec($args, $usleep, $doQuery);
    }

    /**
     * 检查 Think 进程.
     */
    public static function thinkQuery(string $args): array
    {
        $provider = self::providerClass();
        return $provider::thinkQuery($args);
    }

    /**
     * 创建异步进程.
     */
    public static function create(string $command, int $usleep = 0): void
    {
        $provider = self::providerClass();
        $provider::create($command, $usleep);
    }

    /**
     * 查询进程列表.
     *
     * @return array<int, array{pid:string,cmd:string}>
     */
    public static function query(string $cmd, string $name = 'php.exe'): array
    {
        $provider = self::providerClass();
        return $provider::query($cmd, $name);
    }

    /**
     * 关闭指定进程.
     */
    public static function close(int $pid): bool
    {
        $provider = self::providerClass();
        return $provider::close($pid);
    }

    /**
     * 立即执行指令.
     *
     * @return array<int, string>|string
     */
    public static function exec(string $command, bool $outarr = false, ?callable $callable = null)
    {
        $provider = self::providerClass();
        return $provider::exec($command, $outarr, $callable);
    }

    /**
     * 输出命令行消息.
     */
    public static function message(string $message, int $backline = 0): void
    {
        $container = Container::getInstance();
        if (!$container->bound(self::BIND_NAME)) {
            while ($backline-- > 0) {
                $message = "\033[1A\r\033[K{$message}";
            }
            print_r($message . PHP_EOL);
            return;
        }

        $provider = self::providerClass();
        $provider::message($message, $backline);
    }

    /**
     * 判断系统类型 WINDOWS.
     */
    public static function isWin(): bool
    {
        $provider = self::providerClass();
        return $provider::isWin();
    }

    /**
     * 判断系统类型 UNIX.
     */
    public static function isUnix(): bool
    {
        $provider = self::providerClass();
        return $provider::isUnix();
    }

    /**
     * 检查文件是否存在.
     */
    public static function isFile(string $file): bool
    {
        $provider = self::providerClass();
        return $provider::isFile($file);
    }

    /**
     * 构建 Worker 控制命令。
     */
    public static function workerCommand(string $action, string $target, bool $daemon = true, array $options = []): string
    {
        $provider = self::providerClass();
        return $provider::workerCommand($action, $target, $daemon, $options);
    }

    /**
     * Resolve the concrete process provider class.
     */
    protected static function providerClass(): string
    {
        $container = Container::getInstance();
        if (!$container->bound(self::BIND_NAME)) {
            throw new \RuntimeException('ThinkPlugsWorker is required for process runtime operations.');
        }

        return $container->getAlias(self::BIND_NAME);
    }
}
