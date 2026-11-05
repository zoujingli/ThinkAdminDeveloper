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

use think\admin\extend\ToolsExtend;

if (PHP_VERSION_ID < 80000) {
    echo "SKIP: worker-daemon / PHP 7.x 使用既有 lifecycle 验收覆盖 Worker 启停\n";
    exit(0);
}

/**
 * 分配本次验收使用的回环端口。
 */
function workerDiagnosticPort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    acceptanceCheck(is_resource($socket), '无法分配 Worker 验收端口：' . $message);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    return (int)substr(strrchr($address, ':'), 1);
}

/**
 * 等待 daemon 监听端口。
 */
function workerDiagnosticReady(int $port): bool
{
    for ($attempt = 0; $attempt < 80; ++$attempt) {
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.1);
        if (is_resource($socket)) {
            fclose($socket);
            return true;
        }
        usleep(25000);
    }
    return false;
}

/**
 * 写入 Worker 配置，切换到指定的入口分支。
 */
function workerDiagnosticConfig(string $directory, int $port, string $mode): void
{
    if ($mode === 'custom') {
        $config = [
            'host' => '127.0.0.1',
            'port' => $port,
            'worker' => ['name' => 'DiagnosticCustom', 'count' => 1],
            'customs' => [
                'text' => [
                    'type' => 'Workerman',
                    'listen' => "text://127.0.0.1:{$port}",
                    'worker' => ['name' => 'DiagnosticCustom', 'count' => 1],
                ],
            ],
        ];
    } elseif ($mode === 'classes') {
        $config = [
            'host' => '127.0.0.1',
            'port' => $port,
            'classes' => 'app\DiagnosticWorker',
            'worker' => ['name' => 'DiagnosticClasses', 'count' => 1],
        ];
        acceptanceWrite(
            $directory . '/app/DiagnosticWorker.php',
            <<<PHP
<?php
namespace app;
class DiagnosticWorker extends \\Workerman\\Worker
{
    public function __construct()
    {
        parent::__construct('tcp://127.0.0.1:{$port}');
    }
}
PHP
        );
    } else {
        $config = [
            'host' => '127.0.0.1',
            'port' => $port,
            'worker' => ['name' => 'DiagnosticDefault', 'count' => 1],
        ];
    }
    acceptanceWrite($directory . '/config/worker.php', '<?php return ' . var_export($config, true) . ';');
}

/**
 * 验证端口占用时三种 Worker 入口均返回失败并提示日志位置。
 */
function workerDiagnosticConflict(string $directory, string $mode, bool $daemon): void
{
    $port = workerDiagnosticPort();
    workerDiagnosticConfig($directory, $port, $mode);
    $log = $directory . '/safefile/worker/worker_' . $port . '.log';
    $pid = $directory . '/safefile/worker/worker_' . $port . '.pid';
    $listener = stream_socket_server('tcp://127.0.0.1:' . $port, $code, $message);
    acceptanceCheck(is_resource($listener), '无法创建端口占用夹具：' . $message);
    try {
        $arguments = [PHP_BINARY, 'think', 'xadmin:worker'];
        if ($daemon) {
            $arguments[] = '-d';
        }
        if ($mode === 'custom') {
            $arguments[] = '--custom';
            $arguments[] = 'text';
        } elseif ($mode === 'classes') {
            $arguments[] = '--port';
            $arguments[] = (string)$port;
        }
        if ($mode !== 'default' || !$daemon) {
            $arguments[] = 'start';
        }
        [$status, $output] = acceptanceRun($directory, $arguments, 8);
        acceptanceCheck($status === 1, "{$mode} 启动失败时退出码错误：{$status} / {$output}");
        acceptanceCheck(strpos($output, 'Worker server failed to start.') !== false, "{$mode} 未提示启动失败：{$output}");
        acceptanceCheck(strpos($output, 'worker_' . $port . '.log') !== false, "{$mode} 未提示日志路径：{$output}");
        acceptanceCheck(is_file($log) && strpos(file_get_contents($log), 'Address already in use') !== false, "{$mode} 未记录端口占用：{$log}");
        acceptanceCheck(!is_file($pid), "{$mode} 启动失败后残留 PID 文件：{$pid}");
        echo "PASS: conflict / {$mode} / daemon=" . ($daemon ? 'yes' : 'no') . " / exit=1 / log-path\n";
    } finally {
        fclose($listener);
    }
}

$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/thinkadmin-worker-daemon-' . bin2hex(random_bytes(8));
$runningPort = 0;
try {
    acceptanceWrite($directory . '/vendor/autoload.php', '<?php $loader = require ' . var_export($root . '/vendor/autoload.php', true) . '; $loader->addPsr4("app\\\", dirname(__DIR__) . "/app/", true); return $loader;');
    $services = ['think\migration\Service', 'think\admin\Library', 'plugin\worker\Service'];
    acceptanceWrite($directory . '/vendor/services.php', '<?php return ' . var_export($services, true) . ';');
    acceptanceWrite($directory . '/vendor/composer/installed.json', json_encode(['packages' => [['name' => 'thinkadmin/worker-daemon', 'extra' => ['think' => ['services' => $services]]]]]));
    acceptanceWrite($directory . '/vendor/binarys.php', '<?php return ' . var_export(['php' => PHP_BINARY], true) . ';');
    ToolsExtend::copy($root . '/plugin/think-plugs-static/stc/config', $directory . '/config', [], true, false);
    $databaseTemplate = file_get_contents($directory . '/config/database.php');
    acceptanceWrite($directory . '/config/database.php', str_replace('return [', "return ['hostname' => 'explicit-host',", $databaseTemplate));
    ToolsExtend::copy($root . '/plugin/think-plugs-admin/stc/database', $directory . '/database/migrations', [], true, false);
    acceptanceWrite($directory . '/.env', "DB_TYPE=sqlite\nCACHE_TYPE=file\n");
    acceptanceWrite($directory . '/runtime/.env', "[RUNTIME]\nmode=product\n");
    acceptanceWrite($directory . '/app/common.php', '<?php');
    acceptanceWrite($directory . '/app/index/controller/Index.php', '<?php namespace app\index\controller; class Index { public function index() { return "ok"; } }');
    acceptanceWrite($directory . '/app/index/route/app.php', '<?php');
    acceptanceWrite($directory . '/app/admin/route/app.php', '<?php');
    $entry = str_replace('RuntimeService::doConsoleInit()', 'RuntimeService::doConsoleInit(new \think\App(__DIR__))', file_get_contents($root . '/think'), $replacements);
    acceptanceCheck($replacements === 1, 'CLI 入口变化，需要重新确认 Worker 验收脚本');
    acceptanceWrite($directory . '/think', $entry);

    // 正常 daemon 启停，验证停止命令不会因 -d 隐藏结果。
    $runningPort = workerDiagnosticPort();
    // classes 分支不依赖 ThinkPHP 常驻初始化，适合验证 PHP 7.2/Workerman 4.x 的 daemon 生命周期。
    workerDiagnosticConfig($directory, $runningPort, 'classes');
    [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:worker', '-d', 'start', '--port', (string)$runningPort], 8);
    $log = $directory . '/safefile/worker/worker_' . $runningPort . '.log';
    $logOutput = is_file($log) ? file_get_contents($log) : '';
    $ready = workerDiagnosticReady($runningPort);
    acceptanceCheck($ready, 'daemon 正常启动失败：' . $status . ' / ' . $output . ' / ' . $logOutput);
    [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:worker', 'stop', '--port', (string)$runningPort, '-d'], 8);
    acceptanceCheck($status === 0 && strpos($output, 'stop success') !== false, 'stop -d 未显示停止结果：' . $output);
    acceptanceCheck(!workerDiagnosticReady($runningPort), 'daemon 停止后端口仍可连接');
    echo "PASS: lifecycle / daemon start + stop -d / visible result / port-free\n";

    workerDiagnosticConflict($directory, 'default', PHP_VERSION_ID >= 80000);
    workerDiagnosticConflict($directory, 'custom', false);
    workerDiagnosticConflict($directory, 'classes', true);
} finally {
    if ($runningPort > 0) {
        acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:worker', 'stop', '--port', (string)$runningPort], 8);
    }
    acceptanceRemove($directory);
}
