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
require_once __DIR__ . '/support/TestDatabase.php';
require_once dirname($autoload) . '/topthink/framework/src/helper.php';

$projectRoot = dirname($autoload, 2);
$app = \think\admin\service\RuntimeService::init(new \think\App($projectRoot));
$app->loadConfig();
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
        ],
    ],
], 'database');
(new \think\service\ModelService($app))->boot();

$migrationFile = dirname(__DIR__) . '/stc/database/20241010000001_install_admin20241010.php';
require_once $migrationFile;
$adapter = \Phinx\Db\Adapter\AdapterFactory::instance()->getAdapter('sqlite', [
    'connection' => \think\facade\Db::connect()->connect(),
    'name' => ':memory:',
]);
$migration = new \InstallAdmin20241010('test', 20241010000001);
$migration->setAdapter($adapter);
$migration->change();
\think\facade\Db::table('system_config')->insertAll([
    ['type' => 'storage', 'name' => 'allow_exts', 'value' => 'gif,jpg,png'],
    ['type' => 'storage', 'name' => 'type', 'value' => 'local'],
]);
