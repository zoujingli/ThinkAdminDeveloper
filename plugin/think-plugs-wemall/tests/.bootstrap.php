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
use plugin\wemall\tests\support\TestDatabase;
use think\admin\service\RuntimeService;
use think\App;
use think\service\ModelService;

$packageRoot = dirname(__DIR__);
$autoload = null;
foreach ([$packageRoot . '/vendor/autoload.php', dirname($packageRoot, 2) . '/vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if ($autoload === null) {
    throw new RuntimeException('Composer autoload was not found. Run Composer install for the package or aggregate project.');
}

require_once $autoload;
require_once dirname($autoload) . '/topthink/framework/src/helper.php';

if (getenv('THINKADMIN_TEST_DB') !== ':memory:') {
    throw new RuntimeException('THINKADMIN_TEST_DB must be set to :memory: for isolated Wemall tests.');
}

$projectRoot = dirname($autoload, 2);
$app = RuntimeService::init(new App($projectRoot));
$app->loadConfig();

$testRuntime = sys_get_temp_dir() . '/thinkadmin-wemall-tests-' . getmypid();
$app->config->set([
    'default' => 'file',
    'stores' => [
        'file' => [
            'type' => 'File',
            'path' => $testRuntime . '/cache',
            'prefix' => '',
            'expire' => 0,
            'tag_prefix' => 'tag:',
            'serialize' => [],
        ],
    ],
], 'cache');
$app->config->set([
    'default' => 'file',
    'level' => [],
    'type_channel' => [],
    'channels' => [
        'file' => [
            'type' => 'File',
            'path' => $testRuntime . '/log',
            'single' => true,
        ],
    ],
], 'log');

$app->config->set([
    'default' => 'sqlite',
    'auto_timestamp' => true,
    'datetime_format' => 'Y-m-d H:i:s',
    'connections' => [
        'sqlite' => [
            'type' => 'sqlite',
            'database' => ':memory:',
            'charset' => 'utf8',
            'prefix' => '',
            'fields_strict' => false,
            'params' => [PDO::ATTR_STRINGIFY_FETCHES => true],
        ],
    ],
], 'database');
(new ModelService($app))->boot();

$findMigration = static function (string $package, string $file) use ($packageRoot, $projectRoot): string {
    $sourcePackage = basename($packageRoot) === "think-plugs-{$package}"
        ? $packageRoot
        : dirname($packageRoot) . "/think-plugs-{$package}";
    foreach ([$sourcePackage . "/stc/database/{$file}", $projectRoot . "/vendor/zoujingli/think-plugs-{$package}/stc/database/{$file}"] as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }
    throw new RuntimeException("Test migration not found for package {$package}: {$file}");
};

require_once __DIR__ . '/support/TestDatabase.php';

TestDatabase::createSchema([
    [$findMigration('account', '20241010000005_install_account20241010.php'), 'InstallAccount20241010', 20241010000005],
    [$findMigration('payment', '20241010000006_install_payment20241010.php'), 'InstallPayment20241010', 20241010000006],
    [$findMigration('wemall', '20241010000007_install_wemall20241010.php'), 'InstallWemall20241010', 20241010000007],
]);
