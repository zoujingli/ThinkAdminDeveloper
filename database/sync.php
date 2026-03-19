#!/usr/bin/env php
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
use Symfony\Component\Process\Process;

// 以项目根目录作为命令执行基准。
$root = dirname(__DIR__);
$autoload = $root . '/vendor/autoload.php';

if (!is_file($autoload)) {
    fwrite(STDERR, "Unable to find vendor/autoload.php\n");
    exit(1);
}

require $autoload;

// 统一编排索引、备份、迁移和模型同步。
$options = [
    'plugins' => '',
    'tables' => '',
    'backup' => true,
    'index' => true,
    'model' => true,
];

foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
    if ($arg === '--skip-backup') {
        $options['backup'] = false;
        continue;
    }
    if ($arg === '--skip-index') {
        $options['index'] = false;
        continue;
    }
    if ($arg === '--skip-model') {
        $options['model'] = false;
        continue;
    }
    if (str_starts_with($arg, '--plugin=')) {
        $options['plugins'] = substr($arg, 9);
        continue;
    }
    if (str_starts_with($arg, '--table=')) {
        $options['tables'] = substr($arg, 8);
    }
}

$commands = [];
if ($options['index']) {
    $commands[] = ['xadmin:helper:index'];
}
if ($options['backup']) {
    $commands[] = ['xadmin:helper:backup', '--all'];
}

$migrate = ['xadmin:helper:migrate'];
if ($options['plugins'] !== '') {
    $migrate[] = '--plugin=' . $options['plugins'];
}
if ($options['tables'] !== '') {
    $migrate[] = '--table=' . $options['tables'];
}
if ($options['model']) {
    $migrate[] = '--model';
}
$commands[] = $migrate;

foreach ($commands as $command) {
    $process = new Process([PHP_BINARY, 'think', ...$command], $root);
    $process->setTimeout(null);
    $process->run(static function (string $type, string $output): void {
        echo $output;
    });

    if (!$process->isSuccessful()) {
        exit($process->getExitCode() ?? 1);
    }
}
