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
/**
 * 验证公开行为，失败时中止当前场景。
 * @param bool $condition 预期条件
 * @param string $message 失败证据
 * @throws RuntimeException
 */
function acceptanceCheck(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * 执行隔离进程，限制运行时间并收集退出状态和输出。
 * @param string $directory 工作目录
 * @param string[] $arguments 完整命令参数，每个参数独立转义以避免 shell 展开
 * @param int $timeout 最长执行秒数
 * @return array{0:int,1:string} [退出码, 合并输出]
 */
function acceptanceRun(string $directory, array $arguments, int $timeout = 120): array
{
    $log = tempnam(sys_get_temp_dir(), 'thinkadmin-command-');
    $command = 'exec ' . implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1';
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $log, 'w']], $pipes, $directory);
    if (!is_resource($process)) {
        unlink($log);
        throw new RuntimeException('无法启动验收进程');
    }
    fclose($pipes[0]);
    $deadline = microtime(true) + $timeout;
    while (true) {
        $status = proc_get_status($process);
        if (!$status['running']) {
            break;
        }
        if (microtime(true) > $deadline) {
            proc_terminate($process, 9);
            proc_close($process);
            $output = file_get_contents($log);
            unlink($log);
            throw new RuntimeException('验收进程超时：' . $output);
        }
        usleep(100000);
    }
    proc_close($process);
    $output = file_get_contents($log);
    unlink($log);
    return [$status['exitcode'], $output];
}

/**
 * 在本次隔离目录内生成验收文件，自动创建父目录。
 * @param string $path 目标文件
 * @param string $content 文件内容
 */
function acceptanceWrite(string $path, string $content): void
{
    is_dir(dirname($path)) || mkdir(dirname($path), 0777, true);
    acceptanceCheck(file_put_contents($path, $content) !== false, '无法写入验收文件：' . $path);
}

/**
 * 清理本次临时站点；符号链接只删除链接，绝不遍历链接目标。
 * @param string $directory 由验收脚本生成的临时根目录
 */
function acceptanceRemove(string $directory): void
{
    acceptanceCheck(strpos($directory, sys_get_temp_dir() . '/thinkadmin-') === 0, '拒绝清理非验收目录');
    if (!is_dir($directory)) {
        return;
    }
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isLink() || !$file->isDir() ? unlink($file->getPathname()) : rmdir($file->getPathname());
    }
    rmdir($directory);
}
