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
 * @param bool $initFailure 是否在服务注册阶段注入异常
 * @return array{0:int,1:array,2:string,3:array}
 */
function lifecycleRequest(int $port, string $path, string $session, bool $initFailure = false): array
{
    $context = stream_context_create(['http' => [
        'timeout' => 5, 'ignore_errors' => true,
        'header' => "Cookie: ssid={$session}\r\nConnection: close\r\nAccept: application/json\r\n" . ($initFailure ? "X-Acceptance-Init-Failure: 1\r\n" : ''),
    ]]);
    $body = file_get_contents("http://127.0.0.1:{$port}/index/probe/{$path}", false, $context);
    preg_match('#HTTP/\S+ (\d+)#', $http_response_header[0] ?? '', $match);
    return [(int)($match[1] ?? 0), json_decode($body ?: '', true) ?: [], $body ?: '', $http_response_header ?? []];
}

/**
 * 经真实 HTTP 服务核验中间件层次、短路及异常后恢复，重复请求不得累积执行次数。
 * @param int $port 服务端口
 * @param string $session 验收会话编号
 * @param int $pid 同一服务进程编号
 */
function lifecycleCheckMiddleware(int $port, string $session, int $pid): void
{
    $normal = 'global:before,app:before,route:before,controller,route:after,app:after,global:after';
    $cases = [
        ['', 200, $normal],
        ['&stop=global', 202, 'global:before,global:after'],
        ['&stop=app', 202, 'global:before,app:before,app:after,global:after'],
        ['&stop=route', 202, 'global:before,app:before,route:before,route:after,app:after,global:after'],
        ['', 200, $normal],
        ['&failure=1', 500, $normal],
        ['', 200, $normal],
    ];
    foreach ($cases as [$query, $expectedStatus, $trace]) {
        [$status, , $body, $headers] = lifecycleRequest($port, 'middleware?_middleware_probe=1' . $query, $session);
        $headers = array_map(static function (string $header): string {
            return preg_replace('/:\s*/', ': ', strtolower($header), 1);
        }, $headers);
        acceptanceCheck($status === $expectedStatus, '中间件请求状态错误：' . $query . ' / ' . $body);
        acceptanceCheck(in_array('x-acceptance-middleware: ' . $trace, $headers, true), '中间件次数或顺序错误：' . $query . ' / ' . json_encode($headers));
        acceptanceCheck(in_array('x-acceptance-pid: ' . $pid, $headers, true), '中间件异常或短路后工作进程发生变化');
    }
}

/**
 * 在独立进程组中启动真实服务，便于回收 think run 派生的 HTTP 子进程。
 * @param string $directory 隔离站点
 * @param string $name 日志名称
 * @param string[] $arguments 服务命令
 * @return resource 服务进程
 */
function lifecycleStart(string $directory, string $name, array $arguments)
{
    $command = 'exec setsid ' . implode(' ', array_map('escapeshellarg', $arguments)) . ' 2>&1';
    $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $directory . '/' . $name . '.log', 'w']], $pipes, $directory);
    acceptanceCheck(is_resource($process), '无法启动 ' . $name);
    fclose($pipes[0]);
    return $process;
}

/**
 * 限时等待服务监听，启动失败时保留服务日志。
 * @param resource $process 服务进程
 * @param int $port 回环端口
 * @param string $log 日志路径
 */
function lifecycleReady($process, int $port, string $log): void
{
    for ($attempt = 0; $attempt < 100; ++$attempt) {
        if (!proc_get_status($process)['running']) {
            break;
        }
        $socket = @stream_socket_client('tcp://127.0.0.1:' . $port, $code, $message, 0.1);
        if ($socket) {
            fclose($socket);
            return;
        }
        usleep(100000);
    }
    throw new RuntimeException('服务未启动：' . file_get_contents($log));
}

/**
 * 停止本次服务及其子进程，避免开发服务器在验收结束后残留。
 * @param resource $process 服务进程
 * @return int 进程退出码；主动终止的开发服务器可能返回信号状态
 */
function lifecycleStop($process): int
{
    $status = proc_get_status($process);
    if ($status['running']) {
        posix_kill(-$status['pid'], SIGTERM);
        for ($attempt = 0; $attempt < 50; ++$attempt) {
            usleep(100000);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
        }
        if ($status['running']) {
            posix_kill(-$status['pid'], SIGKILL);
        }
    }
    proc_close($process);
    return $status['exitcode'];
}

acceptanceCheck(getenv('THINKADMIN_TEST_DB') === ':memory:', '必须显式启用隔离验收');
acceptanceCheck(extension_loaded('pcntl') && extension_loaded('posix'), '生命周期验收需要 pcntl/posix，请使用 Docker 入口');
$driver = $argv[1] ?? 'sqlite';
$scope = $argv[2] ?? 'all';
acceptanceCheck(in_array($driver, ['sqlite', 'mysql'], true) && in_array($scope, ['all', 'web', 'worker'], true), '用法：check-lifecycle.php sqlite|mysql all|web|worker');
$mysqlPort = (int)getenv('PHINX_TEST_MYSQL_PORT');
acceptanceCheck($driver !== 'mysql' || $mysqlPort > 0, 'MySQL 必须使用隔离实例 PHINX_TEST_MYSQL_PORT');
$root = dirname(__DIR__);
$id = bin2hex(random_bytes(8));
$directory = sys_get_temp_dir() . '/thinkadmin-lifecycle-' . $id;
$databaseName = 'thinkadmin_lifecycle_' . $id;
$server = $pdo = null;
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
    // 旧迁移读取顶层 hostname；MySQL 必须保留可连接地址，同时核验显式配置未被覆盖。
    $hostname = $driver === 'mysql' ? '127.0.0.1' : 'explicit-host';
    acceptanceWrite($directory . '/config/database.php', str_replace('return [', "return ['hostname' => '{$hostname}',", $databaseTemplate));
    ToolsExtend::copy($root . '/plugin/think-plugs-admin/stc/database', $directory . '/database/migrations', [], true, false);
    if ($driver === 'mysql') {
        $server = new PDO('mysql:host=127.0.0.1;port=' . $mysqlPort . ';dbname=phinx_fixture', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $server->exec("CREATE DATABASE `{$databaseName}` CHARACTER SET utf8mb4");
    }
    acceptanceWrite($directory . '/.env', "DB_TYPE={$driver}\nDB_MYSQL_HOST=127.0.0.1\nDB_MYSQL_PORT={$mysqlPort}\nDB_MYSQL_DATABASE={$databaseName}\nCACHE_TYPE=file\n");
    acceptanceWrite($directory . '/runtime/.env', "[RUNTIME]\nmode=product\n");
    acceptanceWrite($directory . '/config/worker.php', '<?php return ' . var_export(['host' => '127.0.0.1', 'port' => $workerPort, 'worker' => ['name' => 'Acceptance', 'count' => 1]], true) . ';');
    $entry = str_replace('RuntimeService::doConsoleInit()', 'RuntimeService::doConsoleInit(new \think\App(__DIR__))', file_get_contents($root . '/think'), $replacements);
    acceptanceCheck($replacements === 1, 'CLI 入口变化，需要重新确认隔离应用根目录');
    acceptanceWrite($directory . '/think', $entry);
    $entry = str_replace('RuntimeService::doWebsiteInit()', 'RuntimeService::doWebsiteInit(new \think\App(dirname(__DIR__)))', file_get_contents($root . '/public/index.php'), $replacements);
    acceptanceCheck($replacements === 1, 'HTTP 入口变化，需要重新确认隔离应用根目录');
    acceptanceWrite($directory . '/public/index.php', $entry);
    acceptanceWrite($directory . '/public/router.php', file_get_contents($root . '/plugin/think-plugs-static/stc/public/router.php'));
    acceptanceWrite(
        $directory . '/app/index/controller/Probe.php',
        <<<'PHP'
<?php
namespace app\index\controller;
class Probe
{
    public function middleware(\think\Request $request)
    {
        $trace = $request->middleware('acceptance_trace', []);
        $trace[] = 'controller';
        $request->withMiddleware(['acceptance_trace' => $trace]);
        if ($request->get('failure')) {
            throw new \TypeError('MIDDLEWARE_FAILURE');
        }
        return json(['controller' => true]);
    }
    public function state(\think\App $app)
    {
        $before = $app->session->get('acceptance');
        $cached = sysvar('acceptance.request');
        if ($app->request->get('value')) {
            $app->session->set('acceptance', $app->request->get('value'));
            $app->db->name('system_data')->insert(['name' => $app->request->get('key'), 'value' => $app->request->get('value')]);
        }
        sysvar('acceptance.request', 'request-local');
        $expected = require syspath('config/database.php');
        return json(['before' => $before, 'cached' => $cached, 'pid' => getmypid(), 'sapi' => PHP_SAPI,
            'stored' => $app->db->name('system_data')->where('name', $app->request->get('key'))->value('value'),
            'rolled_back' => $app->db->name('system_data')->where('name', 'acceptance-failed')->count() === 0,
            'request_config' => $app->config->get('app.acceptance'),
            'config_ok' => $app->config->get('database.connections') === $expected['connections']
                && $app->config->get('database.hostname') === $expected['hostname'],
            'database' => $app->config->get('database.connections'),
            'default' => $app->config->get('database.default')]);
    }
    public function failure(\think\App $app)
    {
        sysvar('acceptance.request', 'failed-request');
        if (!$app->request->get('kind')) {
            throw new \TypeError('LIFECYCLE_FAILURE');
        }
        $app->config->set(['acceptance' => 'failed-request'], 'app');
        $app->db->transaction(function () use ($app) {
            $app->db->name('system_data')->insert(['name' => 'acceptance-failed', 'value' => 'rollback']);
            // 仅验收控制器发送标记，证明失败前已执行实际写入。
            header('X-Acceptance-Failure: ' . $app->request->get('kind'));
            switch ($app->request->get('kind')) {
                case 'exception': throw new \RuntimeException('LIFECYCLE_EXCEPTION');
                case 'error': throw new \Error('LIFECYCLE_ERROR');
                // 无条件 find 不会执行 SQL，显式查询才能覆盖真实数据库异常。
                case 'database': return $app->db->query('SELECT * FROM acceptance_missing_table');
                default: throw new \TypeError('LIFECYCLE_TYPE_ERROR');
            }
        });
    }
}
PHP
    );
    acceptanceWrite(
        $directory . '/app/common.php',
        <<<'PHP'
<?php
if (isset($_SERVER['HTTP_X_ACCEPTANCE_INIT_FAILURE'])) {
    throw new RuntimeException('LIFECYCLE_INIT_FAILURE');
}
class AcceptanceMiddleware
{
    protected $stage = 'global';
    public function handle(\think\Request $request, \Closure $next): \think\Response
    {
        if (!$request->get('_middleware_probe')) {
            return $next($request);
        }
        $trace = $request->middleware('acceptance_trace', []);
        $trace[] = $this->stage . ':before';
        $request->withMiddleware(['acceptance_trace' => $trace]);
        $response = $request->get('stop') === $this->stage ? json(['stop' => $this->stage], 202) : $next($request);
        $trace = $request->middleware('acceptance_trace', []);
        $trace[] = $this->stage . ':after';
        $request->withMiddleware(['acceptance_trace' => $trace]);
        return $response->header(['X-Acceptance-Middleware' => implode(',', $trace), 'X-Acceptance-Pid' => (string)getmypid()]);
    }
}
class AcceptanceAppMiddleware extends AcceptanceMiddleware
{
    protected $stage = 'app';
}
class AcceptanceRouteMiddleware extends AcceptanceMiddleware
{
    protected $stage = 'route';
}
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
    acceptanceWrite($directory . '/app/middleware.php', '<?php return [AcceptanceMiddleware::class];');
    acceptanceWrite($directory . '/app/index/middleware.php', '<?php return [AcceptanceAppMiddleware::class];');
    acceptanceWrite($directory . '/app/index/route/app.php', '<?php \think\facade\Route::get("probe/middleware", "Probe/middleware")->middleware(AcceptanceRouteMiddleware::class);');
    [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:publish', '--migrate']);
    acceptanceCheck($status === 0 && strpos($output, 'Succeed!') !== false, '生命周期站点初始化失败：' . $output);
    $pdo = new PDO($driver === 'sqlite' ? 'sqlite:' . $directory . '/database/sqlite.db' : 'mysql:host=127.0.0.1;port=' . $mysqlPort . ';dbname=' . $databaseName, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    acceptanceCheck((int)$pdo->query('SELECT COUNT(*) FROM migrations')->fetchColumn() === count(glob($directory . '/database/migrations/*_*.php')), 'HTTP 站点迁移不完整');

    $modes = $scope === 'web' ? ['think-run', 'fpm'] : ($scope === 'worker' ? ['worker'] : ['think-run', 'fpm', 'worker']);
    foreach ($modes as $mode) {
        $port = $mode === 'worker' ? $workerPort : lifecyclePort();
        try {
            // 各服务模式独立验证回滚，前一模式失败留下的数据不能污染下一模式。
            $pdo->exec("DELETE FROM system_data WHERE name = 'acceptance-failed'");
            if ($mode === 'fpm') {
                acceptanceCheck(getenv('THINKADMIN_ACCEPTANCE_CONTAINER') === '1', 'FPM 验收仅在隔离 Docker 容器内运行');
                $fpmPort = lifecyclePort();
                acceptanceWrite($directory . '/fpm.conf', "[global]\nerror_log = {$directory}/fpm-error.log\n[www]\nuser = www-data\ngroup = www-data\nlisten = 127.0.0.1:{$fpmPort}\npm = static\npm.max_children = 1\npm.max_requests = 0\ncatch_workers_output = yes\nclear_env = no\nchdir = {$directory}\nphp_admin_flag[log_errors] = on\n");
                acceptanceWrite($directory . '/nginx.conf', "pid {$directory}/nginx.pid;\nerror_log {$directory}/nginx-error.log;\nevents {}\nhttp {\naccess_log {$directory}/nginx-access.log;\nserver {\nlisten 127.0.0.1:{$port};\nroot {$directory}/public;\nlocation / { try_files \$uri \$uri/ /index.php?s=\$uri&\$args; }\nlocation ~ \\.php$ {\ninclude /etc/nginx/fastcgi_params;\nfastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\nfastcgi_param HTTP_PROXY \"\";\nfastcgi_pass 127.0.0.1:{$fpmPort};\n}\n}\n}\n");
                // FPM 以非 root 用户处理请求，只修改本次临时站点的所有权。
                [$status, $output] = acceptanceRun($directory, ['chown', '-R', 'www-data:www-data', $directory]);
                acceptanceCheck($status === 0, '无法准备 FPM 写入权限：' . $output);
                $fpm = lifecycleStart($directory, 'fpm', ['php-fpm', '--nodaemonize', '--fpm-config', $directory . '/fpm.conf']);
                $processes['fpm'] = $fpm;
                lifecycleReady($fpm, $fpmPort, $directory . '/fpm.log');
                $arguments = ['nginx', '-c', $directory . '/nginx.conf', '-g', 'daemon off;'];
            } else {
                $arguments = $mode === 'worker' ? [PHP_BINARY, 'think', 'xadmin:worker', 'start'] : [PHP_BINARY, 'think', 'run', '--host', '127.0.0.1', '--port', (string)$port];
            }
            $name = $mode === 'fpm' ? 'nginx' : $mode;
            $process = lifecycleStart($directory, $name, $arguments);
            $processes[$name] = $process;
            lifecycleReady($process, $port, $directory . '/' . $name . '.log');
            $session = bin2hex(random_bytes(16));
            $key = bin2hex(random_bytes(16));
            [$status, $first, $body] = lifecycleRequest($port, 'state?value=owner&key=' . $key, $session);
            acceptanceCheck($status === 200 && isset($first['pid']) && empty($first['before']) && $first['cached'] === null && $first['stored'] === 'owner', '首个请求或数据库写入失败：' . $body);
            $expectedSapi = ['think-run' => 'cli-server', 'fpm' => 'fpm-fcgi', 'worker' => 'cli'][$mode];
            acceptanceCheck($first['sapi'] === $expectedSapi, '请求未经过预期的 PHP SAPI：' . $body);
            [$status, , $body] = lifecycleRequest($port, 'failure', $session);
            acceptanceCheck($status === 500, '异常请求未返回 500：' . $body);
            [$status, $next, $body] = lifecycleRequest($port, 'state?key=' . $key, $session);
            acceptanceCheck($status === 200 && $next['before'] === 'owner' && $next['cached'] === null && $next['stored'] === 'owner', '异常后的会话、请求状态或数据库连接错误：' . $body);
            acceptanceCheck($first['config_ok'] && $next['config_ok'] && $next['database'] === $first['database'] && $next['default'] === $driver, '共享初始化修改连接配置或覆盖显式值');
            acceptanceCheck($first['pid'] === $next['pid'], '异常后工作进程重启，未证明同一进程恢复');
            if ($mode !== 'worker') {
                foreach (['exception', 'error', 'type-error', 'database'] as $failure) {
                    [$status, , $body, $headers] = lifecycleRequest($port, 'failure?kind=' . $failure, $session);
                    acceptanceCheck($status === 500 && in_array('x-acceptance-failure: ' . $failure, array_map('strtolower', $headers), true), $failure . ' 未在数据库写入后返回 500：' . $body);
                    [$status, $next, $body] = lifecycleRequest($port, 'state?key=' . $key, $session);
                    acceptanceCheck($status === 200 && $next['pid'] === $first['pid'] && $next['before'] === 'owner' && $next['cached'] === null && $next['request_config'] === null && $next['config_ok'] && $next['stored'] === 'owner' && $next['rolled_back'], $failure . ' 后请求状态或事务恢复失败：' . $body);
                }
                [$status, , $body] = lifecycleRequest($port, 'state?key=' . $key, $session, true);
                acceptanceCheck($status === 500, '初始化异常未返回 500：' . $body);
                [$status, $next, $body] = lifecycleRequest($port, 'state?key=' . $key, $session);
                acceptanceCheck($status === 200 && $next['pid'] === $first['pid'] && $next['before'] === 'owner' && $next['cached'] === null && $next['config_ok'] && $next['stored'] === 'owner', '初始化异常后的请求未恢复：' . $body);
            }
            [$status, $other, $body] = lifecycleRequest($port, 'state?key=' . $key, bin2hex(random_bytes(16)));
            acceptanceCheck($status === 200 && empty($other['before']) && $other['cached'] === null, '请求或会话串入另一用户：' . $body);
            lifecycleCheckMiddleware($port, $session, $first['pid']);
            echo "PASS: {$driver} / {$mode} / 全局、应用、路由中间件各执行一次，顺序、短路及异常后恢复\n";
            echo "PASS: {$driver} / {$mode} / sapi={$first['sapi']} / pid={$first['pid']} / 正常、异常、正常，同一进程、会话隔离、配置及数据库稳定\n";
            if ($mode !== 'worker') {
                echo "PASS: {$driver} / {$mode} / Exception、Error、TypeError、数据库异常的事务回滚及初始化异常恢复\n";
            }
        } catch (Throwable $error) {
            $failures[] = $mode . ': ' . $error->getMessage();
            echo "FAIL: {$mode}\n";
        } finally {
            // 服务停止与退出码也是验收结果；即使请求失败仍须回收所有已启动进程。
            foreach (array_reverse(array_keys($processes)) as $name) {
                $status = lifecycleStop($processes[$name]);
                unset($processes[$name]);
                if (in_array($name, ['fpm', 'nginx'], true)) {
                    echo "STOP: {$name} / exit={$status}\n";
                    if ($status !== 0) {
                        $failures[] = $name . ' 未正常停止：' . $status;
                    }
                }
            }
        }
    }

    // 真实监听器派生任务子进程，失败任务应落库且不阻止后续任务执行。
    if ($scope !== 'web') {
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
    }
} finally {
    if ($scope !== 'web' && is_file($directory . '/think')) {
        acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:worker', 'stop'], 20);
        acceptanceRun($directory, [PHP_BINARY, 'think', 'xadmin:queue', 'stop'], 20);
    }
    foreach ($processes as $process) {
        lifecycleStop($process);
    }
    $pdo = null;
    if ($server !== null) {
        $server->exec("DROP DATABASE IF EXISTS `{$databaseName}`");
    }
    if (getenv('THINKADMIN_ACCEPTANCE_CONTAINER') === '1' && is_dir('/evidence')) {
        foreach (glob($directory . '/*.log') as $log) {
            copy($log, '/evidence/' . $driver . '-' . basename($log));
        }
    }
    acceptanceRemove($directory);
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}
