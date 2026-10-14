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

/**
 * 分配回环临时端口，避免依赖宿主固定端口。
 * @return int 端口号
 */
function lifecyclePort(): int
{
    $socket = stream_socket_server('tcp://127.0.0.1:0', $code, $message);
    acceptanceCheck(is_resource($socket), '无法分配验收端口：' . $message);
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    return (int)substr(strrchr($address, ':'), 1);
}

/**
 * 发起一次真实请求，保留 HTTP 状态和响应体。
 * @param int $port 服务端口
 * @param string $path 请求路径
 * @param string $session 验收会话编号
 * @return array{0:int,1:array,2:string}
 */
function lifecycleRequest(int $port, string $path, string $session): array
{
    $context = stream_context_create(['http' => [
        'timeout' => 5, 'ignore_errors' => true,
        'header' => "Cookie: ssid={$session}\r\nConnection: close\r\nAccept: application/json\r\n",
    ]]);
    $body = file_get_contents("http://127.0.0.1:{$port}/index/probe/{$path}", false, $context);
    preg_match('#HTTP/\S+ (\d+)#', $http_response_header[0] ?? '', $match);
    return [(int)($match[1] ?? 0), json_decode($body ?: '', true) ?: [], $body ?: ''];
}

acceptanceCheck(getenv('THINKADMIN_TEST_DB') === ':memory:', '必须显式启用隔离验收');
acceptanceCheck(extension_loaded('pcntl') && extension_loaded('posix'), '生命周期验收需要 pcntl/posix，请使用 Docker 入口');
$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/thinkadmin-lifecycle-' . bin2hex(random_bytes(8));
$processes = [];
$workerPort = lifecyclePort();
$failures = [];
try {
    acceptanceWrite($directory . '/vendor/autoload.php', '<?php $loader = require ' . var_export($root . '/vendor/autoload.php', true) . '; $loader->addPsr4("app\\\", dirname(__DIR__) . "/app/", true); return $loader;');
    $services = ['think\migration\Service', 'think\admin\Library', 'plugin\worker\Service'];
    acceptanceWrite($directory . '/vendor/services.php', '<?php return ' . var_export($services, true) . ';');
    acceptanceWrite($directory . '/vendor/composer/installed.json', json_encode(['packages' => [['name' => 'thinkadmin/lifecycle', 'extra' => ['think' => ['services' => $services]]]]]));
    acceptanceWrite($directory . '/vendor/binarys.php', '<?php return ' . var_export(['php' => PHP_BINARY], true) . ';');
    ToolsExtend::copy($root . '/plugin/think-plugs-static/stc/config', $directory . '/config', [], true, false);
    $databaseTemplate = file_get_contents($directory . '/config/database.php');
    acceptanceWrite($directory . '/config/database.php', str_replace('return [', "return ['hostname' => 'explicit-host',", $databaseTemplate));
    ToolsExtend::copy($root . '/plugin/think-plugs-admin/stc/database', $directory . '/database/migrations', [], true, false);
    acceptanceWrite($directory . '/.env', "DB_TYPE=sqlite\nCACHE_TYPE=file\n");
    acceptanceWrite($directory . '/runtime/.env', "[RUNTIME]\nmode=product\n");
    acceptanceWrite($directory . '/config/worker.php', '<?php return ' . var_export(['host' => '127.0.0.1', 'port' => $workerPort, 'worker' => ['name' => 'Acceptance', 'count' => 1]], true) . ';');
    $entry = str_replace('RuntimeService::doConsoleInit()', 'RuntimeService::doConsoleInit(new \think\App(__DIR__))', file_get_contents($root . '/think'));
    acceptanceWrite($directory . '/think', $entry);
    $entry = str_replace('RuntimeService::doWebsiteInit()', 'RuntimeService::doWebsiteInit(new \think\App(dirname(__DIR__)))', file_get_contents($root . '/public/index.php'));
    acceptanceWrite($directory . '/public/index.php', $entry);
    acceptanceWrite($directory . '/public/router.php', '<?php require __DIR__ . "/index.php";');
    acceptanceWrite(
        $directory . '/app/index/controller/Probe.php',
        <<<'PHP'
<?php
namespace app\index\controller;
class Probe
{
    public function state(\think\App $app)
    {
        $before = $app->session->get('acceptance');
        $cached = sysvar('acceptance.request');
        if ($app->request->get('value')) {
            $app->session->set('acceptance', $app->request->get('value'));
        }
        sysvar('acceptance.request', 'request-local');
        $expected = require syspath('config/database.php');
        return json(['before' => $before, 'cached' => $cached, 'pid' => getmypid(),
            'config_ok' => $app->config->get('database.connections') === $expected['connections']
                && $app->config->get('database.hostname') === 'explicit-host',
            'database' => $app->config->get('database.connections'),
            'default' => $app->config->get('database.default')]);
    }
    public function failure()
    {
        sysvar('acceptance.request', 'failed-request');
        throw new \TypeError('LIFECYCLE_FAILURE');
    }
}
PHP
    );
    acceptanceWrite(
        $directory . '/app/common.php',
        <<<'PHP'
<?php
class AcceptanceQueueSuccess extends \think\admin\Queue
{
    public function execute(array $data = [])
    {
        return 'QUEUE_DONE';
    }
}
class AcceptanceQueueFailure extends \think\admin\Queue
{
    public function execute(array $data = [])
    {
        throw new TypeError('QUEUE_FAILURE');
    }
}
PHP
    );
    [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:publish', '--migrate']);
    acceptanceCheck($status === 0 && strpos($output, 'Succeed!') !== false, '生命周期站点初始化失败：' . $output);
    $pdo = new PDO('sqlite:' . $directory . '/database/sqlite.db', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    foreach (['http', 'worker'] as $mode) {
        $port = $mode === 'worker' ? $workerPort : lifecyclePort();
        $arguments = $mode === 'worker' ? [PHP_BINARY, 'think', 'xadmin:worker', 'start'] : [PHP_BINARY, '-S', '127.0.0.1:' . $port, '-t', 'public', 'public/router.php'];
        $command = 'exec ' . implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1';
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $directory . '/' . $mode . '.log', 'w']], $pipes, $directory);
        acceptanceCheck(is_resource($process), '无法启动 ' . $mode);
        fclose($pipes[0]);
        $processes[] = $process;
        try {
            $ready = false;
            for ($attempt = 0; $attempt < 100; ++$attempt) {
                $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.1);
                if ($socket) {
                    fclose($socket);
                    $ready = true;
                    break;
                }
                if (!proc_get_status($process)['running']) {
                    break;
                }
                usleep(100000);
            }
            acceptanceCheck($ready, '服务未启动：' . file_get_contents($directory . '/' . $mode . '.log'));
            $session = bin2hex(random_bytes(16));
            [$status, $first, $body] = lifecycleRequest($port, 'state?value=owner', $session);
            acceptanceCheck($status === 200 && isset($first['pid']) && empty($first['before']) && $first['cached'] === null, '首个请求失败：' . $body);
            [$status, , $body] = lifecycleRequest($port, 'failure', $session);
            acceptanceCheck($status === 500, '异常请求未返回 500：' . $body);
            [$status, $next, $body] = lifecycleRequest($port, 'state', $session);
            acceptanceCheck($status === 200 && $next['before'] === 'owner' && $next['cached'] === null, '异常后的会话或请求状态错误：' . $body);
            acceptanceCheck($first['config_ok'] && $next['config_ok'] && $next['database'] === $first['database'] && $next['default'] === 'sqlite', '共享初始化修改连接配置或覆盖显式值');
            if ($mode === 'worker') {
                acceptanceCheck($first['pid'] === $next['pid'], '异常后工作进程重启，未证明常驻进程恢复');
            }
            [$status, $other, $body] = lifecycleRequest($port, 'state', bin2hex(random_bytes(16)));
            acceptanceCheck($status === 200 && empty($other['before']) && $other['cached'] === null, '请求或会话串入另一用户：' . $body);
            echo "PASS: {$mode} / 正常、TypeError、正常 / 会话隔离及配置稳定\n";
        } catch (Throwable $error) {
            $failures[] = $mode . ': ' . $error->getMessage();
            echo "FAIL: {$mode}\n";
        }
    }

    // 真实监听器派生任务子进程，失败任务应落库且不阻止后续任务执行。
    try {
        $insert = $pdo->prepare('INSERT INTO system_queue (code,title,command,status,exec_time,exec_data) VALUES (?,?,?,1,0,?)');
        $insert->execute(['ACCEPTANCE_FAIL', '验收失败任务', 'AcceptanceQueueFailure', '{}']);
        [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:queue', 'start']);
        acceptanceCheck($status === 0, '队列启动命令失败：' . $output);
        $states = [];
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $states = $pdo->query('SELECT code,status FROM system_queue')->fetchAll(PDO::FETCH_KEY_PAIR);
            if (($states['ACCEPTANCE_FAIL'] ?? 0) == 4) {
                break;
            }
            usleep(100000);
        }
        acceptanceCheck(($states['ACCEPTANCE_FAIL'] ?? 0) == 4, '失败任务未记录为失败：' . $output);
        $insert->execute(['ACCEPTANCE_OK', '验收成功任务', 'AcceptanceQueueSuccess', '{}']);
        for ($attempt = 0; $attempt < 100; ++$attempt) {
            $states = $pdo->query('SELECT code,status FROM system_queue')->fetchAll(PDO::FETCH_KEY_PAIR);
            if (($states['ACCEPTANCE_OK'] ?? 0) == 3) {
                break;
            }
            usleep(100000);
        }
        acceptanceCheck(($states['ACCEPTANCE_OK'] ?? 0) == 3, '失败后监听器未继续执行成功任务');
        [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:queue', 'stop']);
        acceptanceCheck($status === 0, '队列停止命令失败：' . $output);
        [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:queue', 'status']);
        acceptanceCheck($status === 0 && strpos($output, 'not running') !== false, '停止后仍存在监听器：' . $output);
        echo "PASS: queue / 启动、任务失败、后续任务成功、停止\n";
    } catch (Throwable $error) {
        $failures[] = 'queue: ' . $error->getMessage();
        echo "FAIL: queue\n";
    }
} finally {
    if (is_file($directory . '/think')) {
        acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:worker', 'stop'], 20);
        acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:queue', 'stop'], 20);
    }
    foreach ($processes as $process) {
        if (proc_get_status($process)['running']) {
            proc_terminate($process);
        }
        proc_close($process);
    }
    $pdo = null;
    acceptanceRemove($directory);
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
