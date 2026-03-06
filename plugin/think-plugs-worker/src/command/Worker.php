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

namespace plugin\worker\command;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use plugin\worker\support\HttpServer;
use think\admin\Command;
use think\admin\service\ProcessService;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker as Workerman;

/**
 * Worker Command.
 * @class Worker
 */
class Worker extends Command
{
    protected $config = [];

    protected $classes = [];

    public function configure()
    {
        $this->setName('xadmin:worker')
            ->addArgument('action', Argument::OPTIONAL, 'start|stop|restart|reload|status|connections', 'start')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'the host of workerman server.')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'the port of workerman server.')
            ->addOption('custom', 'c', Option::VALUE_OPTIONAL, 'the custom workerman server.', 'default')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the workerman server in daemon mode.')
            ->setDescription('Workerman Http Server for ThinkAdmin');
    }

    public function execute(Input $input, Output $output)
    {
        // 读取配置参数
        [$custom, $this->config] = $this->withConfig();
        if (empty($this->config)) {
            $output->writeln("<error>Configuration Custom {$custom} Undefined.</error> ");
            return;
        }

        // 获取基本运行参数
        $host = $this->withHost();
        $port = $this->withPort();
        $action = $input->getArgument('action');

        // 初始化运行环境参数
        if ($this->process->iswin()) {
            if (!$this->winNext($custom, $action, $port)) {
                return;
            }
        } else {
            if (!$this->unixNext($custom, $action, $port)) {
                return;
            }
        }

        // 设置环境运行文件
        if (empty($this->config['worker']['logFile'])) {
            $this->config['worker']['logFile'] = syspath("safefile/worker/worker_{$port}.log");
        }
        if (empty($this->config['worker']['pidFile'])) {
            $this->config['worker']['pidFile'] = syspath("safefile/worker/worker_{$port}.pid");
        }
        if (empty($this->config['worker']['statusFile'])) {
            $this->config['worker']['statusFile'] = syspath("safefile/worker/worker_{$port}.status");
        }
        is_dir($dir = dirname($this->config['worker']['pidFile'])) or mkdir($dir, 0777, true);
        is_dir($dir = dirname($this->config['worker']['logFile'])) or mkdir($dir, 0777, true);
        is_dir($dir = dirname($this->config['worker']['statusFile'])) or mkdir($dir, 0777, true);

        // 静态属性设置
        foreach ($this->config['worker'] ?? [] as $name => $value) {
            if (in_array($name, [
                'daemonize',
                'statusFile',
                'stdoutFile',
                'pidFile',
                'logFile',
                'logFileMaxSize',
                'stopTimeout',
                'eventLoopClass',
                'onMasterReload',
                'onMasterStop',
                'onWorkerExit',
            ], true)) {
                Workerman::${$name} = $value;
                unset($this->config['worker'][$name]);
            }
        }

        // 守护进程模式
        if ($this->input->hasOption('daemon')) {
            Workerman::$daemonize = true;
        }

        // 执行自定义服务
        if (!empty($this->config['classes'])) {
            foreach ((array)$this->config['classes'] as $class) {
                if (class_exists($class)) {
                    $this->classes[] = new $class();
                } else {
                    $this->output->writeln("<error>Worker Server Class Not Exists : {$class}</error>");
                }
            }
            Workerman::runAll();
            return;
        }

        if ($custom === 'default') {
            if ($action === 'start') {
                $output->writeln('Starting Workerman http server...');
            }
            $worker = new HttpServer($host, $port, $this->config['context'] ?? [], $this->config['callable'] ?? null, $this->config);
            $worker->setRoot($this->app->getRootPath());
        } else {
            if (strtolower($this->config['type']) !== 'business') {
                if (empty($this->config['listen'])) {
                    $listen = "websocket://{$host}:{$port}";
                } elseif (is_array($attr = parse_url($this->config['listen']))) {
                    $attr = ['port' => $port, 'host' => $host] + $attr + ['scheme' => 'websocket'];
                    $listen = "{$attr['scheme']}://{$attr['host']}:{$attr['port']}";
                } else {
                    $listen = $this->config['listen'];
                }
                if ($action == 'start') {
                    $output->writeln(sprintf('Starting Workerman %s server...', strstr($listen, ':', true) ?: 'unknow'));
                }
            }
            $worker = $this->makeWorker($this->config['type'] ?? '', $listen ?? '', $this->config['context'] ?? []);
        }

        // 设置属性参数
        foreach ($this->config['worker'] ?? [] as $name => $value) {
            $worker->{$name} = $value;
        }

        // 运行环境提示
        if ($this->process->isWin()) {
            $output->writeln('You can exit with <info>`CTRL-C`</info>');
        }

        // 应用并启动服务
        Workerman::runAll();
    }

    /**
     * 创建 Worker 进程实例.
     * @return BusinessWorker|Gateway|Register|Workerman
     */
    protected function makeWorker(string $type, string $listen, array $context = [])
    {
        switch (strtolower($type)) {
            case 'gateway':
                if (class_exists('GatewayWorker\Gateway')) {
                    return new Gateway($listen, $context);
                }
                $this->output->error('请执行 composer require workerman/gateway-worker 安装 GatewayWorker 组件');
                exit(1);
            case 'register':
                if (class_exists('GatewayWorker\Register')) {
                    return new Register($listen, $context);
                }
                $this->output->error('请执行 composer require workerman/gateway-worker 安装 GatewayWorker 组件');
                exit(1);
            case 'business':
                if (class_exists('GatewayWorker\BusinessWorker')) {
                    return new BusinessWorker($listen, $context);
                }
                $this->output->error('请执行 composer require workerman/gateway-worker 安装 GatewayWorker 组件');
                exit(1);
            default:
                return new Workerman($listen, $context);
        }
    }

    /**
     * 初始化 Windows 环境.
     */
    private function winNext(string $custom, string $action, int $port): bool
    {
        if (!in_array($action, ['start', 'stop', 'status'])) {
            $this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|status for Windows .</error>");
            return false;
        }
        $command = "xadmin:worker --custom {$custom} --port {$port}";
        if ($action === 'start' && $this->input->hasOption('daemon')) {
            if (count($query = $this->findWindowsWorkers($custom, $port)) > 0) {
                $this->output->writeln("<info>Worker daemons [{$custom}:{$port}] already exists for Process {$query[0]['pid']} </info>");
                return false;
            }
            $this->process->thinkExec($command, 500);
            if (count($query = $this->findWindowsWorkers($custom, $port)) > 0) {
                $this->output->writeln("<info>Worker daemons [{$custom}:{$port}] started successfully for Process {$query[0]['pid']} </info>");
            } else {
                $this->output->writeln("<error>Worker daemons [{$custom}:{$port}] failed to start. </error>");
            }
            return false;
        }
        if ($action === 'stop') {
            foreach ($result = $this->findWindowsWorkers($custom, $port) as $item) {
                $this->process->close(intval($item['pid']));
                $this->output->writeln("<info>Send stop signal to Worker daemons [{$custom}:{$port}] Process {$item['pid']} </info>");
            }
            if (empty($result)) {
                $this->output->writeln("<error>The Worker daemons [{$custom}:{$port}] is not running. </error>");
            }
            return false;
        }
        if ($action === 'status') {
            foreach ($result = $this->findWindowsWorkers($custom, $port) as $item) {
                $this->output->writeln("Worker daemons [{$custom}:{$port}] Process {$item['pid']} running");
            }
            if (empty($result)) {
                $this->output->writeln("<error>The Worker daemons [{$custom}:{$port}] is not running. </error>");
            }
            return false;
        }
        return true;
    }

    /**
     * 初始化 Unix 环境.
     */
    private function unixNext(string $custom, string $action, int $port): bool
    {
        if (!in_array($action, ['start', 'stop', 'reload', 'restart', 'status', 'connections'])) {
            $this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload|status|connections .</error>");
            return false;
        }
        global $argv;
        array_shift($argv) && array_shift($argv);
        array_unshift($argv, 'xadmin:worker', $action, "--custom {$custom} --port {$port}");
        return true;
    }

    /**
     * 获取监听主机.
     */
    private function withHost(): string
    {
        if ($this->input->hasOption('host')) {
            return $this->input->getOption('host');
        }
        if (empty($this->config['listen'])) {
            return empty($this->config['host']) ? '0.0.0.0' : $this->config['host'];
        }
        return parse_url($this->config['listen'], PHP_URL_HOST) ?: '0.0.0.0';
    }

    /**
     * 获取监听端口.
     */
    private function withPort(): int
    {
        if ($this->input->hasOption('port')) {
            return intval($this->input->getOption('port'));
        }
        if (empty($this->config['listen'])) {
            return empty($this->config['port']) ? 80 : intval($this->config['port']);
        }
        return intval(parse_url($this->config['listen'], PHP_URL_PORT) ?: 80);
    }

    /**
     * 获取配置参数.
     */
    private function withConfig(): array
    {
        if (($custom = $this->input->getOption('custom')) !== 'default') {
            $config = $this->app->config->get("worker.customs.{$custom}", []);
            return [$custom, empty($config) ? false : $config];
        }
        return [$custom, $this->app->config->get('worker', [])];
    }

    private function findWindowsWorkers(string $custom, int $port): array
    {
        $command = "xadmin:worker --custom {$custom} --port {$port}";
        if ($result = $this->process->thinkQuery($command)) {
            return $result;
        }

        $workers = [];
        foreach ($this->queryWindowsListenerPids($port) as $pid) {
            $cmd = $this->queryWindowsCommandLine($pid);
            if ($cmd === '' || stripos($cmd, 'xadmin:worker') === false) {
                continue;
            }

            $workers[] = ['pid' => (string)$pid, 'cmd' => $cmd];
        }

        return $workers;
    }

    private function queryWindowsListenerPids(int $port): array
    {
        $pids = [];
        $lines = ProcessService::exec("netstat -ano -p tcp | findstr LISTENING | findstr :{$port}", true);
        foreach ($lines as $line) {
            $line = trim(preg_replace('#\s+#', ' ', trim($line)));
            if ($line === '') {
                continue;
            }

            $parts = explode(' ', $line);
            $address = $parts[1] ?? '';
            $pid = $parts[count($parts) - 1] ?? '';
            if ($pid !== '' && is_numeric($pid) && str_ends_with($address, ':' . $port)) {
                $pids[] = (int)$pid;
            }
        }

        return array_values(array_unique($pids));
    }

    private function queryWindowsCommandLine(int $pid): string
    {
        $lines = ProcessService::exec("wmic process where processid=\"{$pid}\" get CommandLine /value", true);
        foreach ($lines as $line) {
            $line = trim($line);
            if (str_starts_with($line, 'CommandLine=')) {
                return trim(substr($line, 12));
            }
        }

        return '';
    }
}
