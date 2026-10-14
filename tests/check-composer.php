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
require __DIR__ . '/acceptance.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Table;
use think\admin\extend\PhinxBackup;

/**
 * 构建离线仓库，发布包走 zip 解压，开发包保留真实 path 链接。
 * @param string $root 聚合源码目录
 * @param string $directory 本次验收目录
 * @return array 两代包元数据，旧代仅移除目标版本之后的迁移
 */
function composerAcceptancePackages(string $root, string $directory): array
{
    $lock = json_decode(file_get_contents($root . '/composer.lock'), true);
    $repositories = ['old' => [], 'current' => []];
    foreach ($lock['packages'] as $package) {
        $name = $package['name'];
        $local = strpos($name, 'zoujingli/think-') === 0 && is_dir($root . '/plugin/' . basename($name));
        $source = $local ? $root . '/plugin/' . basename($name) : $root . '/vendor/' . $name;
        acceptanceCheck(is_dir($source), '依赖源码不完整：' . $name);
        unset($package['source'], $package['dist'], $package['transport-options'], $package['notification-url']);
        foreach ($local ? ['old', 'current'] : ['current'] as $generation) {
            $copy = $directory . '/packages/' . $generation . '/' . $name;
            mkdir($copy, 0777, true);
            [$status, $output] = acceptanceRun($root, ['cp', '-a', $source . '/.', $copy]);
            acceptanceCheck($status === 0, '准备包失败：' . $output);
            if ($local && $generation === 'old') {
                foreach (glob($copy . '/stc/database/*_*.php') as $file) {
                    if ((int)basename($file) > 20241010000009) {
                        unlink($file);
                    }
                }
            }
            $metadata = $package;
            if ($local) {
                $metadata['version'] = 'dev-master';
            }
            $reference = sha1($name . '/' . $generation);
            $archive = $directory . '/' . str_replace('/', '-', $name) . '-' . $generation . '.zip';
            [$status, $output] = acceptanceRun($copy, ['zip', '-qr', $archive, '.', '-x', '.git/*']);
            acceptanceCheck($status === 0, '生成发布包失败：' . $output);
            $metadata['dist'] = ['type' => 'zip', 'url' => 'file://' . $archive, 'reference' => $reference, 'shasum' => sha1_file($archive)];
            $repositories[$generation][$name] = [$metadata, $copy, $local];
            if (!$local) {
                $repositories['old'][$name] = $repositories['current'][$name];
            }
        }
    }
    return $repositories;
}

/**
 * 切换包代次，Composer 自行解析并执行安装、更新事件。
 * @param string $site 隔离站点
 * @param array $root 根项目元数据
 * @param array $packages 当前代包元数据
 * @param string $mode path 或 dist
 * @param bool $core 是否仅安装后台基础组合
 */
function composerAcceptanceManifest(string $site, array $root, array $packages, string $mode, bool $core): void
{
    $repository = [];
    foreach ($packages as [$package, $source, $local]) {
        if ($mode === 'path' && $local) {
            $package['dist'] = ['type' => 'path', 'url' => $source, 'reference' => $package['dist']['reference']];
            $package['transport-options'] = ['symlink' => true, 'relative' => false];
        }
        $repository[] = $package;
    }
    $config = [
        'name' => 'thinkadmin/acceptance', 'type' => 'project',
        'autoload' => $root['autoload'], 'require' => $core ? ['zoujingli/think-plugs-admin' => '*'] : $root['require'],
        'repositories' => [['type' => 'package', 'package' => $repository], ['packagist.org' => false]],
        'minimum-stability' => 'dev',
        'config' => ['allow-plugins' => ['zoujingli/think-install' => true]],
    ];
    acceptanceWrite($site . '/composer.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
}

/**
 * 核对真实站点的服务注册，避免复制成功但插件不可用的假阳性。
 * @param string $site 隔离站点
 */
function composerAcceptanceServices(string $site): void
{
    acceptanceWrite(
        $site . '/verify-services.php',
        <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = \think\admin\service\RuntimeService::init(new \think\App(__DIR__));
$app->initialize();
foreach (require __DIR__ . '/vendor/services.php' as $service) {
    if (!class_exists($service) || !$app->getService($service)) {
        fwrite(STDERR, 'MISSING_SERVICE:' . $service . "\n");
        exit(1);
    }
}
echo "SERVICES_READY\n";
PHP
    );
    [$status, $output] = acceptanceRun($site, [PHP_BINARY, 'verify-services.php']);
    acceptanceCheck($status === 0 && strpos($output, 'SERVICES_READY') !== false, '已安装插件未完整注册：' . $output);
}

acceptanceCheck(getenv('THINKADMIN_TEST_DB') === ':memory:', '必须显式启用隔离验收');
$driver = $argv[1] ?? 'sqlite';
acceptanceCheck(in_array($driver, ['sqlite', 'mysql'], true), '仅支持 sqlite/mysql');
$port = (int)getenv('PHINX_TEST_MYSQL_PORT');
acceptanceCheck($driver !== 'mysql' || $port > 0, '必须指定隔离 MySQL 端口');
$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/thinkadmin-composer-' . bin2hex(random_bytes(8));
mkdir($directory);
$composer = getenv('COMPOSER_BINARY') ?: '/usr/local/bin/composer';
acceptanceCheck(is_file($composer), '请使用 Docker 验收入口，或指定 COMPOSER_BINARY');
$manifest = json_decode(file_get_contents($root . '/composer.json'), true);
$server = $driver === 'mysql' ? new PDO('mysql:host=127.0.0.1;port=' . $port . ';dbname=phinx_fixture', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]) : null;
$databases = $failures = [];
try {
    $packages = composerAcceptancePackages($root, $directory);
    putenv('COMPOSER_ALLOW_SUPERUSER=1');
    putenv('COMPOSER_DISABLE_NETWORK=1');
    putenv('COMPOSER_CACHE_DIR=' . $directory . '/cache');
    foreach (['dist-core', 'path-install', 'path-upgrade', 'dist-install', 'dist-upgrade'] as $scenario) {
        $site = $directory . '/' . $scenario;
        $core = $scenario === 'dist-core';
        $mode = strpos($scenario, 'path') === 0 ? 'path' : 'dist';
        $upgrade = strpos($scenario, 'upgrade') !== false;
        $prefix = $mode === 'dist' ? 'tenant_' : '';
        $database = 'thinkadmin_composer_' . bin2hex(random_bytes(6));
        if ($server) {
            $server->exec("CREATE DATABASE {$database} CHARACTER SET utf8mb4");
            $databases[] = $database;
        }
        try {
            // 预置文件禁止安装器上报主机标识，其他安装事件保持原样。
            acceptanceWrite($site . '/vendor/binarys.php', '<?php return [];');
            acceptanceWrite($site . '/.env', "DB_TYPE={$driver}\nCACHE_TYPE=file\nDB_MYSQL_HOST=127.0.0.1\nDB_MYSQL_PORT={$port}\nDB_MYSQL_DATABASE={$database}\nDB_MYSQL_USERNAME=root\nDB_MYSQL_PREFIX={$prefix}\n");
            acceptanceWrite($site . '/runtime/.env', "[RUNTIME]\nmode=product\n");
            if ($driver === 'sqlite') {
                $connection = ['type' => 'sqlite', 'database' => $site . '/fixture.db', 'prefix' => $prefix, 'deploy' => 0, 'charset' => 'utf8',
                    'hostname' => '', 'hostport' => '', 'username' => '', 'password' => ''];
                acceptanceWrite($site . '/config/database.php', '<?php return ' . var_export(['default' => 'sqlite', 'connections' => ['sqlite' => $connection]], true) . ';');
            }
            composerAcceptanceManifest($site, $manifest, $packages[$upgrade ? 'old' : 'current'], $mode, $core);
            [$status, $output] = acceptanceRun($site, [PHP_BINARY, $composer, 'update', '--no-dev', '--no-interaction', '--prefer-dist', '--no-progress'], 300);
            acceptanceCheck($status === 0 && strpos($output, 'xadmin:publish --migrate') !== false, '真实 Composer 安装失败或未执行钩子：' . $output);
            $databaseFile = $site . '/config/database.php';
            acceptanceCheck(is_file($databaseFile) && is_file($site . '/think'), '未生成入口和配置');
            composerAcceptanceServices($site);
            $pdo = new PDO($driver === 'sqlite' ? 'sqlite:' . $site . '/fixture.db' : 'mysql:host=127.0.0.1;port=' . $port . ';dbname=' . $database, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
            if ($core) {
                acceptanceCheck((int)$pdo->query("SELECT COUNT(*) FROM {$prefix}migrations")->fetchColumn() > 0, '基础插件组合未完成迁移');
                echo "PASS: {$driver} / {$scenario} / 安装钩子及服务注册\n";
                continue;
            }
            $balance = $prefix . 'plugin_payment_balance';
            $refund = $prefix . 'plugin_payment_refund';
            $pdo->exec("INSERT INTO {$balance} (amount) VALUES (-12.34)");
            if ($upgrade) {
                $pdo->exec("INSERT INTO {$refund} (code) VALUES ('composer-retry'), ('composer-retry')");
            }
            file_put_contents($databaseFile, "\n// 用户自定义配置保留标记\n", FILE_APPEND);
            $configHash = hash_file('sha256', $databaseFile);
            $envHash = hash_file('sha256', $site . '/.env');
            acceptanceWrite($site . '/public/static/extra/script.js', '// 用户脚本保留');
            acceptanceWrite($site . '/public/static/theme/ignore', '保留用户主题');
            acceptanceWrite($site . '/public/static/theme/custom.txt', 'custom-theme');
            file_put_contents($site . '/think', "\n// 入口更新前标记\n", FILE_APPEND);
            composerAcceptanceManifest($site, $manifest, $packages['current'], $mode, false);
            // reinstall 触发复制；update 通过引用变化触发真实更新。
            $arguments = $upgrade ? ['update', '--no-dev'] : ['reinstall', 'zoujingli/think-plugs-static'];
            [$status, $output] = acceptanceRun($site, array_merge([PHP_BINARY, $composer], $arguments, ['--no-interaction', '--prefer-dist', '--no-progress']), 300);
            if ($upgrade) {
                acceptanceCheck($status !== 0, '迁移失败未使 Composer 更新失败：' . $output);
                acceptanceCheck(stripos($output, 'unique') !== false || stripos($output, 'duplicate entry') !== false, '更新未复现预期的唯一约束失败：' . $output);
                acceptanceCheck((int)$pdo->query("SELECT COUNT(*) FROM {$refund}")->fetchColumn() === 2, '失败更新丢失数据');
                acceptanceCheck((int)$pdo->query("SELECT COUNT(*) FROM {$prefix}migrations WHERE version=20241010000011")->fetchColumn() === 0, '失败迁移被登记为完成');
                $id = (int)$pdo->query("SELECT MAX(id) FROM {$refund}")->fetchColumn();
                $pdo->exec("DELETE FROM {$refund} WHERE id={$id}");
            } else {
                acceptanceCheck($status === 0, '重新安装静态包失败：' . $output);
            }
            for ($attempt = 0; $attempt < 2; ++$attempt) {
                [$status, $output] = acceptanceRun($site, [PHP_BINARY, $composer, 'install', '--no-dev', '--optimize-autoloader', '--no-interaction', '--prefer-dist', '--no-progress'], 300);
                acceptanceCheck($status === 0, '按锁文件恢复或重复执行失败：' . $output);
                acceptanceCheck((float)$pdo->query("SELECT amount FROM {$balance} LIMIT 1")->fetchColumn() === -12.34, '更新丢失余额数据');
                acceptanceCheck($configHash === hash_file('sha256', $databaseFile) && $envHash === hash_file('sha256', $site . '/.env'), '更新覆盖用户配置');
            }
            $migrationCount = count(glob($site . '/database/migrations/*_*.php'));
            acceptanceCheck((int)$pdo->query("SELECT COUNT(*) FROM {$prefix}migrations")->fetchColumn() === $migrationCount, '存在未完成或重复登记的迁移');
            acceptanceCheck(hash_file('sha256', $site . '/think') === hash_file('sha256', $root . '/plugin/think-plugs-static/stc/think'), '强制覆盖入口规则未生效');
            acceptanceCheck(file_get_contents($site . '/public/static/extra/script.js') === '// 用户脚本保留', 'init 覆盖用户脚本');
            acceptanceCheck(file_get_contents($site . '/public/static/theme/custom.txt') === 'custom-theme', 'ignore 未保留用户主题');
            acceptanceCheck(($mode === 'path') === is_dir($site . '/vendor/zoujingli/think-plugs-static'), 'path/dist 的 clear 行为不符合约定');
            composerAcceptanceServices($site);

            // 独立数据库恢复升级后的余额样本，源数据库只读。
            $recovery = $database . '_recovery';
            if ($server) {
                $server->exec("CREATE DATABASE {$recovery} CHARACTER SET utf8mb4");
                $databases[] = $recovery;
                $adapter = new MysqlAdapter(['host' => '127.0.0.1', 'port' => $port, 'name' => $recovery, 'user' => 'root', 'pass' => '', 'charset' => 'utf8mb4']);
            } else {
                $adapter = new SQLiteAdapter(['adapter' => 'sqlite', 'name' => $site . '/recovery.db']);
            }
            $adapter->execute('CREATE TABLE recovery_balance (id INTEGER PRIMARY KEY, amount DECIMAL(20,2))');
            $rows = $pdo->query("SELECT id, amount FROM {$balance} ORDER BY id")->fetchAll();
            $backup = $site . '/balance.backup';
            PhinxBackup::write($rows, $backup);
            acceptanceCheck(PhinxBackup::restore(new Table('recovery_balance', [], $adapter), $backup) === count($rows), '独立库恢复记录数不一致');
            acceptanceCheck((float)$adapter->fetchRow('SELECT amount FROM recovery_balance')['amount'] === -12.34, '独立库恢复余额错误');
            echo "PASS: {$driver} / {$scenario} / 配置、数据、重复执行、恢复及安装器规则\n";
        } catch (Throwable $error) {
            $failures[] = "{$driver}/{$scenario}: " . substr($error->getMessage(), -5000);
            echo "FAIL: {$driver} / {$scenario}\n";
        } finally {
            $pdo = $adapter = null;
        }
    }
} finally {
    foreach ($databases as $database) {
        $server->exec("DROP DATABASE IF EXISTS {$database}");
    }
    putenv('COMPOSER_DISABLE_NETWORK');
    putenv('COMPOSER_CACHE_DIR');
    acceptanceRemove($directory);
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
