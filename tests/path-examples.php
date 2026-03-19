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
// ============================================
// 场景 1：加载 PHP 类文件（使用 syspath）
// ============================================
require syspath('vendor/autoload.php');
require syspath('app/controller/User.php');

// ============================================
// 场景 2：读取配置文件（使用 syspath）
// ============================================
$configFile = syspath('config/database.php');
if (is_file($configFile)) {
    $config = include $configFile;
}

// ============================================
// 场景 3：写入日志文件（使用 runpath）
// ============================================
$logFile = runpath('runtime/log/' . date('Ymd') . '.log');
file_put_contents($logFile, "日志内容\n", FILE_APPEND);

// ============================================
// 场景 4：保存上传文件（使用 runpath）
// ============================================
$uploadDir = runpath('public/upload');
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}
$targetFile = $uploadDir . '/' . basename($_FILES['file']['name']);
move_uploaded_file($_FILES['file']['tmp_name'], $targetFile);

// ============================================
// 场景 5：数据库文件存储（使用 runpath）
// ============================================
$dbFile = runpath('database/sqlite.db');
// PHAR 环境：/path/to/install/database/sqlite.db
// 普通环境：/project/database/sqlite.db

// ============================================
// 场景 6：缓存文件（使用 runpath）
// ============================================
$cacheFile = runpath('runtime/cache/' . md5($key) . '.php');
file_put_contents($cacheFile, "<?php\nreturn " . var_export($data, true) . ';');

// ============================================
// 场景 7：会话文件（使用 runpath）
// ============================================
$sessionPath = runpath('runtime/session');
session_save_path($sessionPath);

// ============================================
// 场景 8：读取框架资源（使用 syspath）
// ============================================
$frameworkPath = syspath('vendor/topthink/framework');
$routeFile = syspath('vendor/topthink/framework/src/think/Route.php');

// ============================================
// 场景 9：环境文件操作（使用 runpath）
// ============================================
$envFile = runpath('.env');
if (!is_file($envFile)) {
    copy(syspath('.env.example'), $envFile);
}

// ============================================
// 场景 10：检查文件是否存在（注意路径选择）
// ============================================
// ❌ 错误：在 PHAR 中，syspath('runtime') 指向 phar:// 内部，文件不存在
if (is_file(syspath('runtime/cache/test.php'))) {
    // 这段代码在 PHAR 中永远不会执行
}

// ✅ 正确：使用 runpath 访问可写路径
if (is_file(runpath('runtime/cache/test.php'))) {
    // 可以正确访问到文件
    $content = file_get_contents(runpath('runtime/cache/test.php'));
}

// ============================================
// 场景 11：路径调试
// ============================================
if (is_phar()) {
    echo "当前运行在 PHAR 环境\n";
    echo '系统根目录：' . syspath() . "\n";      // phar:///path/to/admin.phar
    echo '运行根目录：' . runpath() . "\n";      // /path/to/install
} else {
    echo "当前运行在普通环境\n";
    echo '系统根目录：' . syspath() . "\n";      // /project
    echo '运行根目录：' . runpath() . "\n";      // /project
}

// ============================================
// 场景 12：动态路径选择
// ============================================
function getConfigPath(): string
{
    // 配置文件在代码目录中，使用 syspath
    return syspath('config/app.php');
}

function getRuntimePath(): string
{
    // 运行时数据在可写目录中，使用 runpath
    return runpath('runtime');
}

// ============================================
// 快速参考表
// ============================================
/*
| 用途           | 使用函数  | PHAR 环境示例                    | 普通环境示例           |
|----------------|-----------|----------------------------------|------------------------|
| 加载类文件     | syspath   | phar:///admin.phar/app/User.php  | /app/User.php          |
| 读取配置       | syspath   | phar:///admin.phar/config/db.php | /config/db.php         |
| 框架代码       | syspath   | phar:///admin.phar/vendor/...    | /vendor/...            |
| 写入日志       | runpath   | /install/runtime/log/...         | /project/runtime/log/  |
| 上传文件       | runpath   | /install/public/upload/...       | /project/public/upload |
| 缓存文件       | runpath   | /install/runtime/cache/...       | /project/runtime/cache |
| 数据库文件     | runpath   | /install/database/...            | /project/database      |
| 会话文件       | runpath   | /install/runtime/session/...     | /project/runtime/sess  |
| 环境文件       | runpath   | /install/.env                    | /project/.env          |
*/
