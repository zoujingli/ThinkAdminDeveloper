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
// 加载基础文件
require __DIR__ . '/vendor/autoload.php';

use think\admin\Library;
use think\App;

// 初始化应用
$app = new App(__DIR__);
Library::$sapp = $app;

echo "========================================\n";
echo "路径函数测试\n";
echo "========================================\n\n";

// 测试环境判断
echo "【环境判断测试】\n";
echo 'is_phar(): ' . (is_phar() ? 'true' : 'false') . "\n";
echo 'Phar::running(): ' . (Phar::running() ?: 'empty') . "\n";
echo 'Phar::running(false): ' . (Phar::running(false) ?: 'empty') . "\n\n";

// 测试 syspath
echo "【syspath 测试 - 系统路径（代码/资源）】\n";
echo 'syspath(): ' . syspath() . "\n";
echo "syspath('app'): " . syspath('app') . "\n";
echo "syspath('config'): " . syspath('config') . "\n";
echo "syspath('vendor'): " . syspath('vendor') . "\n";
echo "syspath('runtime'): " . syspath('runtime') . "\n";
echo "syspath('public'): " . syspath('public') . "\n";
echo "syspath('.env'): " . syspath('.env') . "\n\n";

// 测试 runpath
echo "【runpath 测试 - 运行路径（可写数据）】\n";
echo 'runpath(): ' . runpath() . "\n";
echo "runpath('runtime'): " . runpath('runtime') . "\n";
echo "runpath('public'): " . runpath('public') . "\n";
echo "runpath('.env'): " . runpath('.env') . "\n";
echo "runpath('database'): " . runpath('database') . "\n";
echo "runpath('safefile'): " . runpath('safefile') . "\n\n";

// 路径对比分析
echo "【路径对比分析】\n";
$paths = ['app', 'config', 'vendor', 'runtime', 'public', '.env', 'database'];
echo "提示：在普通环境下，syspath 和 runpath 返回相同路径\n";
echo "      在 PHAR 环境下，syspath 返回 phar:// 路径，runpath 返回外部路径\n\n";
foreach ($paths as $path) {
    $sys = syspath($path);
    $run = runpath($path);
    $same = $sys === $run ? '相同' : '不同';
    $sysProto = str_starts_with($sys, 'phar://') ? 'phar' : 'file';
    $runProto = str_starts_with($run, 'phar://') ? 'phar' : 'file';
    echo sprintf(
        "%-12s | syspath(%-4s): %-55s [%s] | runpath: %-60s [%s] | %s\n",
        $path,
        $path,
        basename($sys),
        $sysProto,
        basename($run),
        $runProto,
        $same
    );
}

echo "\n========================================\n";
echo "测试完成\n";
echo "========================================\n";
