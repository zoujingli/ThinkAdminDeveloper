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
// 本地与 CI 共用入口；每个套件独立进程，避免应用容器和静态模型状态互相污染。
$root = dirname(__DIR__);
$phpunit = getenv('PHPUNIT_BIN') ?: $root . '/vendor/bin/phpunit';
if (!is_file($phpunit)) {
    fwrite(STDERR, "未找到 PHPUnit，请安装开发依赖或通过 PHPUNIT_BIN 指定 PHPUnit 脚本路径。\n");
    exit(1);
}
chdir($root);
putenv('THINKADMIN_TEST_DB=:memory:');

$commands = [];
foreach (['think-library', 'think-plugs-admin', 'think-plugs-account', 'think-plugs-payment', 'think-plugs-wemall'] as $package) {
    $commands[] = [PHP_BINARY, $phpunit, '--do-not-cache-result', '-c', "plugin/{$package}/phpunit.xml.dist"];
}
$commands[] = [PHP_BINARY, $phpunit, '--do-not-cache-result', '--no-configuration', '--bootstrap', 'vendor/autoload.php', 'plugin/think-plugs-wuma/tests/CodeTest.php'];
$commands[] = [PHP_BINARY, 'tests/check-rbac.php'];
$commands[] = [PHP_BINARY, 'tests/check-coder-token.php'];
$commands[] = [PHP_BINARY, 'tests/check-form-token.php'];
$commands[] = [PHP_BINARY, 'tests/check-upgrade.php', 'sqlite'];
if (getenv('PHINX_TEST_MYSQL_PORT')) {
    $commands[] = [PHP_BINARY, 'tests/check-upgrade.php', 'mysql'];
}

// 任一检查失败立即返回原退出码，使 Composer 和发布流水线能够阻止后续步骤。
foreach ($commands as $arguments) {
    $command = implode(' ', array_map('escapeshellarg', $arguments));
    passthru($command, $status);
    if ($status !== 0) {
        exit($status);
    }
}
