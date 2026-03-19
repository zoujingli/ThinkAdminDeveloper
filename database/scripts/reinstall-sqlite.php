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
$root = dirname(__DIR__, 2);
$database = $root . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'sqlite.db';
$artifacts = [
    $database,
    $database . '-journal',
    $database . '-shm',
    $database . '-wal',
];

chdir($root);

run(buildCommand(PHP_BINARY, $root . DIRECTORY_SEPARATOR . 'think', ['xadmin:worker', 'stop', 'all']), true);

foreach ($artifacts as $file) {
    if (!is_file($file)) {
        continue;
    }

    if (!@unlink($file)) {
        fwrite(STDERR, "Failed to remove {$file}" . PHP_EOL);
        exit(1);
    }

    fwrite(STDOUT, "Removed {$file}" . PHP_EOL);
}

if (!is_dir(dirname($database))) {
    mkdir(dirname($database), 0777, true);
}

if (!is_file($database)) {
    touch($database);
}

run(buildCommand(PHP_BINARY, $root . DIRECTORY_SEPARATOR . 'think', ['xadmin:publish', '--migrate']));

fwrite(STDOUT, "SQLite database rebuilt: {$database}" . PHP_EOL);

function run(string $command, bool $allowFailure = false): void
{
    fwrite(STDOUT, "> {$command}" . PHP_EOL);
    passthru($command, $status);

    if ($status !== 0 && !$allowFailure) {
        fwrite(STDERR, "Command failed with exit code {$status}" . PHP_EOL);
        exit($status);
    }
}

function buildCommand(string $phpBinary, string $thinkFile, array $arguments): string
{
    $segments = [quote($phpBinary), quote($thinkFile)];
    foreach ($arguments as $argument) {
        $segments[] = quote((string)$argument);
    }

    return implode(' ', $segments);
}

function quote(string $value): string
{
    if (DIRECTORY_SEPARATOR === '\\') {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    return escapeshellarg($value);
}
