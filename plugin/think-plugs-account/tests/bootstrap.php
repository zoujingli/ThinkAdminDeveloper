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
use Phinx\Db\Adapter\AdapterFactory;
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
if (getenv('THINKADMIN_TEST_DB') !== ':memory:') {
    throw new RuntimeException('THINKADMIN_TEST_DB must be set to :memory: for isolated account tests.');
}

require_once $autoload;
require_once dirname($autoload) . '/topthink/framework/src/helper.php';

$app = RuntimeService::init(new App(dirname($autoload, 2)));
foreach (glob($app->getConfigPath() . '*' . $app->getConfigExt()) ?: [] as $file) {
    $app->config->load($file, pathinfo($file, PATHINFO_FILENAME));
}
$testRuntime = sys_get_temp_dir() . '/thinkadmin-account-tests-' . getmypid();
$app->config->set([
    'default' => 'file',
    'stores' => ['file' => ['type' => 'File', 'path' => $testRuntime . '/cache', 'prefix' => '', 'expire' => 0, 'tag_prefix' => 'tag:', 'serialize' => []]],
], 'cache');
$app->config->set([
    'default' => 'file', 'level' => [], 'type_channel' => [],
    'channels' => ['file' => ['type' => 'File', 'path' => $testRuntime . '/log', 'single' => true]],
], 'log');
$app->config->set([
    'default' => 'sqlite', 'auto_timestamp' => true, 'datetime_format' => 'Y-m-d H:i:s',
    'connections' => ['sqlite' => ['type' => 'sqlite', 'database' => ':memory:', 'charset' => 'utf8', 'prefix' => '', 'fields_strict' => false]],
], 'database');
(new ModelService($app))->boot();

$app->db->execute('CREATE TABLE system_data(id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL DEFAULT "", value TEXT, create_time TEXT, update_time TEXT)');
$adapter = AdapterFactory::instance()->getAdapter('sqlite', ['connection' => $app->db->connect()->connect(), 'name' => ':memory:']);
require_once $packageRoot . '/stc/database/20241010000005_install_account20241010.php';
$migration = new InstallAccount20241010('test', 20241010000005);
$migration->setAdapter($adapter);
$migration->change();
