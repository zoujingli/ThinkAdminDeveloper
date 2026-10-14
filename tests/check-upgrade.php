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
use think\admin\extend\ToolsExtend;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * 执行隔离站点的实际命令行入口，保留输出和进程退出码.
 * @param string $directory 隔离站点根目录
 * @param string[] $arguments ThinkPHP 命令及参数
 * @param string $entry 隔离站点中的入口文件名
 * @return array{0:int,1:string} [退出码, 合并后的标准输出与错误输出]
 */
function runUpgradeCommand(string $directory, array $arguments, string $entry = 'think'): array
{
    $command = implode(' ', array_map('escapeshellarg', array_merge([PHP_BINARY, $directory . '/' . $entry], $arguments)));
    $process = proc_open($command . ' 2>&1', [0 => ['pipe', 'r'], 1 => ['pipe', 'w']], $pipes, $directory);
    if (!is_resource($process)) {
        throw new RuntimeException('无法启动验收命令');
    }
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    return [proc_close($process), $output];
}

/**
 * 检查可观察的安装与升级结果，失败时让验收流程返回非零状态.
 * @param bool $condition 预期条件
 * @param string $message 失败时的诊断信息
 */
function verifyUpgrade(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$root = dirname(__DIR__);
$driver = $argv[1] ?? 'sqlite';
verifyUpgrade(in_array($driver, ['sqlite', 'mysql'], true), '验收仅支持 sqlite 或 mysql');
verifyUpgrade(getenv('THINKADMIN_TEST_DB') === ':memory:', '必须通过 THINKADMIN_TEST_DB=:memory: 明确启用隔离验收');
$port = (int)getenv('PHINX_TEST_MYSQL_PORT');
verifyUpgrade($driver !== 'mysql' || $port > 0, 'MySQL 验收必须指定隔离实例 PHINX_TEST_MYSQL_PORT');

foreach (['', 'tenant_'] as $prefix) {
    foreach (['install', 'upgrade'] as $scenario) {
        $id = bin2hex(random_bytes(8));
        $directory = sys_get_temp_dir() . '/thinkadmin-upgrade-' . $id;
        $databaseName = 'thinkadmin_upgrade_' . $id;
        $server = $connection = null;
        try {
            mkdir($directory . '/vendor/composer', 0777, true);
            mkdir($directory . '/runtime', 0777, true);
            mkdir($directory . '/app', 0777, true);
            ToolsExtend::copy($root . '/plugin/think-plugs-static/stc/config', $directory . '/config', [], true, false);
            foreach (glob($root . '/plugin/*/stc/database') as $source) {
                ToolsExtend::copy($source, $directory . '/database/migrations', [], true, false);
            }
            $migrationCount = count(glob($directory . '/database/migrations/*_*.php'));

            // 仅替换应用根目录参数，保留发布入口的退出码处理，覆盖真实 CLI 行为。
            $entry = str_replace('RuntimeService::doConsoleInit()', 'RuntimeService::doConsoleInit(new \think\App(__DIR__))', file_get_contents($root . '/think'), $replacements);
            verifyUpgrade($replacements === 1, '入口变化后需重新确认隔离站点根目录');
            file_put_contents($directory . '/think', $entry);
            file_put_contents($directory . '/vendor/autoload.php', '<?php require ' . var_export($root . '/vendor/autoload.php', true) . ';');
            $services = ['think\migration\Service', 'think\admin\Library'];
            file_put_contents($directory . '/vendor/services.php', '<?php return ' . var_export($services, true) . ';');
            file_put_contents($directory . '/vendor/composer/installed.json', json_encode(['packages' => [[
                'name' => 'thinkadmin/acceptance', 'extra' => ['think' => ['services' => $services]],
            ]]]));
            file_put_contents($directory . '/.env', "DB_TYPE={$driver}\nCACHE_TYPE=file\n");
            file_put_contents($directory . '/runtime/.env', "[RUNTIME]\nmode=product\n");

            // 连接始终由本次验收创建，不读取宿主站点的环境配置或业务数据库。
            $config = ['type' => $driver, 'database' => $directory . '/fixture.db', 'prefix' => $prefix, 'deploy' => 0,
                'hostname' => '127.0.0.1', 'hostport' => $port, 'username' => 'root', 'password' => '', 'charset' => 'utf8mb4'];
            if ($driver === 'mysql') {
                $server = new PDO('mysql:host=127.0.0.1;port=' . $port . ';dbname=phinx_fixture', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4");
                $config['database'] = $databaseName;
            }
            file_put_contents($directory . '/config/database.php', '<?php return ' . var_export([
                'default' => $driver, 'auto_timestamp' => true, 'datetime_format' => 'Y-m-d H:i:s', 'connections' => [$driver => $config],
            ], true) . ';');
            $configHash = hash_file('sha256', $directory . '/config/database.php');
            $environmentHash = hash_file('sha256', $directory . '/.env');

            if ($driver === 'sqlite' && $prefix === '' && $scenario === 'install') {
                // 模拟调用方关闭自动退出后复用入口，确保入口不额外终止当前进程。
                file_put_contents(
                    $directory . '/console-include.php',
                    <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = \think\admin\service\RuntimeService::init(new \think\App(__DIR__));
$app->console->setAutoExit(false);
require __DIR__ . '/think-embedded';
echo "ENTRY_RETURNED\n";
PHP
                );
                foreach (['think', 'plugin/think-plugs-static/stc/think'] as $source) {
                    $embedded = str_replace('RuntimeService::doConsoleInit()', 'RuntimeService::doConsoleInit($app)', file_get_contents($root . '/' . $source), $replacements);
                    verifyUpgrade($replacements === 1, '入口变化后需重新确认嵌入调用方式');
                    // PHP 7 将被 include 文件的 shebang 当作输出，仅移除该启动器行。
                    $embedded = preg_replace('/^#![^\r\n]*\R/', '', $embedded);
                    file_put_contents($directory . '/think-embedded', $embedded);
                    [$status, $output] = runUpgradeCommand($directory, ['list', '--raw'], 'console-include.php');
                    verifyUpgrade($status === 0 && strpos($output, 'ENTRY_RETURNED') !== false, $source . ' 在禁用自动退出后必须允许调用方继续执行：' . $output);
                }
                file_put_contents(
                    $directory . '/console-exception.php',
                    <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = \think\admin\service\RuntimeService::init(new \think\App(__DIR__));
$app->console->setAutoExit(false);
$app->console->setCatchExceptions(false);
try {
    \think\admin\service\RuntimeService::doConsoleInit($app);
} catch (\InvalidArgumentException $exception) {
    echo "EXCEPTION_FORWARDED\n";
}
PHP
                );
                [$status, $output] = runUpgradeCommand($directory, ['acceptance:missing-command'], 'console-exception.php');
                verifyUpgrade($status === 0 && strpos($output, 'EXCEPTION_FORWARDED') !== false, '调用方关闭异常捕获后必须能够接收原异常：' . $output);
                echo "PASS: CLI 入口返回及异常向上传递\n";
            }

            $arguments = $scenario === 'install' ? ['xadmin:publish', '--migrate'] : ['migrate:run', '--target', '20241010000009'];
            [$status, $output] = runUpgradeCommand($directory, $arguments);
            verifyUpgrade($status === 0, "{$driver}/{$prefix}/{$scenario} 初始化失败：\n{$output}");
            $connection = new PDO($driver === 'sqlite' ? 'sqlite:' . $config['database'] : 'mysql:host=127.0.0.1;port=' . $port . ';dbname=' . $databaseName, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $refunds = $prefix . 'plugin_payment_refund';
            $journal = $prefix . 'migrations';
            $balance = $prefix . 'plugin_payment_balance';
            $connection->exec("INSERT INTO `{$balance}` (amount) VALUES (-12.34)");

            if ($scenario === 'upgrade') {
                $connection->exec("INSERT INTO `{$refunds}` (code) VALUES ('retry-fixture'), ('retry-fixture')");
                [$status, $output] = runUpgradeCommand($directory, ['xadmin:publish', '--migrate']);
                verifyUpgrade($status !== 0, '重复退款编号导致迁移失败时，发布入口必须返回非零退出码：' . $output);
                verifyUpgrade((int)$connection->query("SELECT COUNT(*) FROM `{$refunds}`")->fetchColumn() === 2, '失败迁移不应删除退款记录');
                verifyUpgrade((int)$connection->query("SELECT COUNT(*) FROM `{$journal}` WHERE version = 20241010000011")->fetchColumn() === 0, '失败迁移不应登记为完成');
                // 模拟维护人员修复重复数据后重试，限定删除本次构造的一条重复记录。
                $duplicate = $connection->query("SELECT MAX(id) FROM `{$refunds}` WHERE code = 'retry-fixture'")->fetchColumn();
                $connection->exec("DELETE FROM `{$refunds}` WHERE id = " . (int)$duplicate);
            }

            for ($attempt = 0; $attempt < 2; ++$attempt) {
                [$status, $output] = runUpgradeCommand($directory, ['xadmin:publish', '--migrate']);
                verifyUpgrade($status === 0, "升级或重复执行失败：\n{$output}");
                verifyUpgrade((int)$connection->query("SELECT COUNT(*) FROM `{$journal}`")->fetchColumn() === $migrationCount, '必须完成全部已发布迁移');
                verifyUpgrade((float)$connection->query("SELECT amount FROM `{$balance}` LIMIT 1")->fetchColumn() === -12.34, '升级必须保留余额业务数据');
                verifyUpgrade($configHash === hash_file('sha256', $directory . '/config/database.php'), '升级不应覆盖已有数据库配置');
                verifyUpgrade($environmentHash === hash_file('sha256', $directory . '/.env'), '升级不应覆盖已有环境配置');
            }
            if ($scenario === 'upgrade') {
                verifyUpgrade((int)$connection->query("SELECT COUNT(*) FROM `{$refunds}` WHERE code = 'retry-fixture'")->fetchColumn() === 1, '重试后必须保留原退款记录');
            }
            echo "PASS: {$driver} / " . ($prefix ?: '无前缀') . " / {$scenario} / 配置保留、数据保留、重复执行\n";
        } finally {
            $connection = null;
            if ($server !== null) {
                $server->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
            }
            if (is_dir($directory)) {
                ToolsExtend::remove($directory);
            }
        }
    }
}
