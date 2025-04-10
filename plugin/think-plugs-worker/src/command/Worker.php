<?php

// +----------------------------------------------------------------------
// | Worker Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// | 参考文件 ( https://github.com/top-think/think-worker/blob/3.0/src/command/Worker.php )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-worker
// | github 代码仓库：https://github.com/zoujingli/think-plugs-worker
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\worker\command;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use plugin\worker\support\HttpServer;
use think\admin\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker as Workerman;

/**
 * Worker Command
 * @class Worker
 * @package think\admin\server\command
 */
class Worker extends Command
{
    protected $config = [];
    protected $classes = [];

    public function configure()
    {
        $this->setName('xadmin:worker')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
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
            if (!$this->winNext($custom, $action, $port)) return;
        } else {
            if (!$this->unixNext($custom, $action, $port)) return;
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
            if (in_array($name, ['daemonize', 'statusFile', 'stdoutFile', 'pidFile', 'logFile'])) {
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
                    $this->classes[] = new $class;
                } else {
                    $this->output->writeln("<error>Worker Server Class Not Exists : {$class}</error>");
                }
            }
            Workerman::runAll();
            return;
        }

        if ($custom === 'default') {
            if ('start' === $action) $output->writeln('Starting Workerman http server...');
            $worker = new HttpServer($host, $port, $this->config['context'] ?? [], $this->config['callable'] ?? null);
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
                if ('start' == $action) {
                    $output->writeln(sprintf("Starting Workerman %s server...", strstr($listen, ':', true) ?: 'unknow'));
                }
            }
            $worker = $this->makeWorker($this->config['type'] ?? '', $listen ?? '', $this->config['context'] ?? []);
        }

        // 设置属性参数
        foreach ($this->config['worker'] ?? [] as $name => $value) {
            $worker->$name = $value;
        }

        // 运行环境提示
        if ($this->process->isWin()) {
            $output->writeln('You can exit with <info>`CTRL-C`</info>');
        }

        // 应用并启动服务
        Workerman::runAll();
    }

    /**
     * 创建 Worker 进程实例
     * @param string $type
     * @param string $listen
     * @param array $context
     * @return BusinessWorker|Register|Gateway|Workerman
     */
    protected function makeWorker(string $type, string $listen, array $context = [])
    {
        switch (strtolower($type)) {
            case 'gateway':
                if (class_exists('GatewayWorker\Gateway')) return new Gateway($listen, $context);
                $this->output->error("请执行 composer require workerman/gateway-worker 安装 GatewayWorker 组件");
                exit(1);
            case 'register':
                if (class_exists('GatewayWorker\Register')) return new Register($listen, $context);
                $this->output->error("请执行 composer require workerman/gateway-worker 安装 GatewayWorker 组件");
                exit(1);
            case 'business':
                if (class_exists('GatewayWorker\BusinessWorker')) return new BusinessWorker($listen, $context);
                $this->output->error("请执行 composer require workerman/gateway-worker 安装 GatewayWorker 组件");
                exit(1);
            default:
                return new Workerman($listen, $context);
        }
    }

    /**
     * 初始化 Windows 环境
     * @param string $custom
     * @param string $action
     * @param integer $port
     * @return boolean
     */
    private function winNext(string $custom, string $action, int $port): bool
    {
        if (!in_array($action, ['start', 'stop', 'status'])) {
            $this->output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|status for Windows .</error>");
            return false;
        }
        $command = "xadmin:worker --custom {$custom} --port {$port}";
        if ($action === 'start' && $this->input->hasOption('daemon')) {
            if (count($query = $this->process->thinkQuery($command)) > 0) {
                $this->output->writeln("<info>Worker daemons [{$custom}:{$port}] already exists for Process {$query[0]['pid']} </info>");
                return false;
            }
            $this->process->thinkExec($command, 500);
            if (count($query = $this->process->thinkQuery($command)) > 0) {
                $this->output->writeln("<info>Worker daemons [{$custom}:{$port}] started successfully for Process {$query[0]['pid']} </info>");
            } else {
                $this->output->writeln("<error>Worker daemons [{$custom}:{$port}] failed to start. </error>");
            }
            return false;
        } elseif ($action === 'stop') {
            foreach ($result = $this->process->thinkQuery($command) as $item) {
                $this->process->close(intval($item['pid']));
                $this->output->writeln("<info>Send stop signal to Worker daemons [{$custom}:{$port}] Process {$item['pid']} </info>");
            }
            if (empty($result)) {
                $this->output->writeln("<error>The Worker daemons [{$custom}:{$port}] is not running. </error>");
            }
            return false;
        } elseif ($action === 'status') {
            foreach ($result = $this->process->thinkQuery('xadmin:worker') as $item) {
                if (preg_match('#--custom\s+(.*?)\s+--port\s+(\d+)#', $item['cmd'], $matches)) {
                    $this->output->writeln("Worker daemons [{$matches[1]}:{$matches[2]}] Process {$item['pid']} running");
                }
            }
            if (empty($result)) {
                $this->output->writeln("<error>The Worker daemons is not running. </error>");
            }
            return false;
        }
        return true;
    }

    /**
     * 初始化 Unix 环境
     * @param string $custom
     * @param string $action
     * @param integer $port
     * @return boolean
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
     * 获取监听主机
     * @return string
     */
    private function withHost(): string
    {
        if ($this->input->hasOption('host')) {
            return $this->input->getOption('host');
        } elseif (empty($this->config['listen'])) {
            return empty($this->config['host']) ? '0.0.0.0' : $this->config['host'];
        } else {
            return parse_url($this->config['listen'], PHP_URL_HOST) ?: '0.0.0.0';
        }
    }

    /**
     * 获取监听端口
     * @return integer
     */
    private function withPort(): int
    {
        if ($this->input->hasOption('port')) {
            return intval($this->input->getOption('port'));
        } elseif (empty($this->config['listen'])) {
            return empty($this->config['port']) ? 80 : intval($this->config['port']);
        } else {
            return intval(parse_url($this->config['listen'], PHP_URL_PORT) ?: 80);
        }
    }

    /**
     * 获取配置参数
     * @return array
     */
    private function withConfig(): array
    {
        if (($custom = $this->input->getOption('custom')) !== 'default') {
            $config = $this->app->config->get("worker.customs.{$custom}", []);
            return [$custom, empty($config) ? false : $config];
        } else {
            return [$custom, $this->app->config->get('worker', [])];
        }
    }
}