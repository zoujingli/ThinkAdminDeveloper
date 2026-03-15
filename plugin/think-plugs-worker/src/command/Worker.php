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

use plugin\worker\model\SystemQueue;
use plugin\worker\service\HttpServer;
use plugin\worker\service\ProcessService;
use plugin\worker\service\QueueServer;
use plugin\worker\service\QueueService as WorkerQueueService;
use plugin\worker\service\WorkerConfig;
use think\admin\Command;
use think\admin\service\QueueService as QueueRuntime;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker as Workerman;

/**
 * Manage ThinkPlugsWorker runtime services.
 */
class Worker extends Command
{
    protected WorkerConfig $workers;

    protected ProcessService $manager;

    public function configure(): void
    {
        $this->setName('xadmin:worker')
            ->addArgument('action', Argument::OPTIONAL, 'start|serve|stop|restart|reload|status|query|check', 'start')
            ->addArgument('target', Argument::OPTIONAL, 'http|queue|all|<service>', 'http')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'Override the service host.')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'Override the service port.')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the service in background mode.')
            ->setDescription('Manage ThinkPlugsWorker HTTP and queue runtimes');
    }

    public function execute(Input $input, Output $output): int
    {
        $this->workers = new WorkerConfig($this->app);
        $this->manager = ProcessService::instance();
        $action = strtolower(trim((string)$input->getArgument('action')));
        $target = strtolower(trim((string)$input->getArgument('target')));

        try {
            $targets = $this->workers->targets($target);
        } catch (\InvalidArgumentException $exception) {
            $output->error($exception->getMessage());
            return 1;
        }

        if ($targets === []) {
            $output->error('No enabled worker services are configured.');
            return 1;
        }

        if (count($targets) > 1 && ($this->input->getOption('host') || $this->input->getOption('port'))) {
            $output->error('Host or port overrides can only be used with a single service target.');
            return 1;
        }

        if (count($targets) > 1 && in_array($action, ['start', 'restart'], true) && !$this->input->getOption('daemon')) {
            $output->error('Managing multiple services requires --daemon.');
            return 1;
        }

        return $this->handleAction($action, $targets);
    }

    /**
     * Dispatch command action.
     *
     * @param string[] $targets
     */
    protected function handleAction(string $action, array $targets): int
    {
        return match ($action) {
            'start' => $this->startTargets($targets),
            'serve' => $this->serveTargets($targets),
            'stop' => $this->stopTargets($targets),
            'status' => $this->statusTargets($targets),
            'query' => $this->queryTargets($targets),
            'check' => $this->checkTargets($targets),
            'reload' => $this->reloadTargets($targets),
            'restart' => $this->restartTargets($targets),
            default => $this->invalidAction(),
        };
    }

    /**
     * Start services.
     *
     * @param string[] $targets
     */
    protected function startTargets(array $targets): int
    {
        $status = 0;
        foreach ($targets as $name) {
            $service = $this->withOverrides($this->workers->service($name));
            $status = max($status, $this->input->getOption('daemon')
                ? $this->startDaemon($service)
                : $this->serveService($service));
        }

        return $status;
    }

    /**
     * Run a service in the current process.
     *
     * @param string[] $targets
     */
    protected function serveTargets(array $targets): int
    {
        if (count($targets) !== 1) {
            $this->output->error('The serve action only accepts a single service target.');
            return 1;
        }

        return $this->serveService($this->withOverrides($this->workers->service($targets[0])));
    }

    /**
     * Stop services.
     *
     * @param string[] $targets
     */
    protected function stopTargets(array $targets): int
    {
        $status = 0;
        foreach ($targets as $name) {
            $info = $this->manager->workerDescribe($name);
            if (!$info['running']) {
                $this->output->writeln("Worker [{$name}] is not running");
                continue;
            }

            if ($this->manager->workerStop($name)) {
                $this->output->writeln("Worker [{$name}] stop signal sent to pid {$info['pid']}");
            } else {
                $this->output->writeln("Worker [{$name}] failed to stop");
                $status = 1;
            }
        }

        return $status;
    }

    /**
     * Inspect service status.
     *
     * @param string[] $targets
     */
    protected function statusTargets(array $targets): int
    {
        foreach ($targets as $name) {
            $info = $this->manager->workerDescribe($name);
            if ($info['running']) {
                $this->output->writeln("Worker [{$name}] process {$info['pid']} running");
            } else {
                $this->output->writeln("Worker [{$name}] is not running");
            }
        }

        return 0;
    }

    /**
     * Query service process details.
     *
     * @param string[] $targets
     */
    protected function queryTargets(array $targets): int
    {
        foreach ($targets as $name) {
            $info = $this->manager->workerDescribe($name);
            if (!$info['running']) {
                $this->output->writeln("># [{$name}] no related worker process found");
                continue;
            }

            foreach ($info['processes'] as $process) {
                $this->output->writeln("># [{$name}] {$process['pid']}\t{$process['cmd']}");
            }
        }

        return 0;
    }

    /**
     * Perform runtime smoke checks for the selected services.
     *
     * @param string[] $targets
     */
    protected function checkTargets(array $targets): int
    {
        $status = 0;
        foreach ($targets as $name) {
            $status = max($status, $this->checkService($this->withOverrides($this->workers->service($name))));
        }

        return $status;
    }

    /**
     * Perform a smoke check for a single service.
     */
    protected function checkService(array $service): int
    {
        $name = (string)$service['name'];
        $info = $this->manager->workerDescribe($name);
        if (!$info['running']) {
            $this->output->writeln("Worker [{$name}] is not running");
            return 1;
        }

        return match ($service['driver']) {
            'http' => $this->checkHttpService($service),
            'queue' => $this->checkQueueService(),
            default => 1,
        };
    }

    /**
     * Probe the HTTP runtime with a real local request.
     */
    protected function checkHttpService(array $service): int
    {
        $host = $this->probeHost((string)($service['server']['host'] ?? '127.0.0.1'));
        $port = intval($service['server']['port'] ?? 0);
        [$ok, $statusLine, $error] = $this->probeHttp($host, $port);

        if ($ok) {
            $this->output->writeln("># Worker [http] smoke check passed: {$statusLine}");
            return 0;
        }

        $this->output->writeln("># Worker [http] smoke check failed: {$error}");
        return 1;
    }

    /**
     * Probe the queue runtime by creating and waiting for a simple smoke task.
     */
    protected function checkQueueService(): int
    {
        $title = 'Smoke Queue ' . date('YmdHis') . mt_rand(100, 999);

        try {
            $queue = QueueRuntime::register($title, 'version');
            $code = $queue->getCode();
        } catch (\Throwable $exception) {
            $this->output->writeln("># Worker [queue] failed to create smoke task: {$exception->getMessage()}");
            return 1;
        }

        $this->output->writeln("># Worker [queue] smoke task created: {$code}");

        $lastStatus = 0;
        $lastMessage = '';
        for ($i = 0; $i < 20; ++$i) {
            usleep(500000);
            $record = SystemQueue::mk()->where(['code' => $code])->findOrEmpty();
            if ($record->isEmpty()) {
                continue;
            }

            $lastStatus = intval($record->getAttr('status') ?: 0);
            $lastMessage = trim((string)$record->getAttr('exec_desc'));
            if ($lastStatus === WorkerQueueService::STATE_DONE) {
                $this->cleanupQueueSmokeTask($code);
                $this->output->writeln("># Worker [queue] smoke check passed: {$code} {$lastMessage}");
                return 0;
            }

            if ($lastStatus === WorkerQueueService::STATE_ERROR) {
                $this->output->writeln("># Worker [queue] smoke check failed: {$code} {$lastMessage}");
                return 1;
            }
        }

        $message = $lastMessage === '' ? 'timeout waiting for queue completion' : $lastMessage;
        $this->output->writeln("># Worker [queue] smoke check failed: {$code} status {$lastStatus}, {$message}");
        return 1;
    }

    /**
     * Reload services.
     * Uses a Workerman reload signal on POSIX and a controlled restart on Windows.
     *
     * @param string[] $targets
     */
    protected function reloadTargets(array $targets): int
    {
        $status = 0;
        foreach ($targets as $name) {
            $info = $this->manager->workerDescribe($name);
            if (!$info['running']) {
                $this->output->writeln("Worker [{$name}] is not running");
                continue;
            }

            $mode = $this->manager->workerReload($name, true, $this->overrideOptions());
            if ($mode === 'reload') {
                $this->output->writeln("Worker [{$name}] reload signal sent to pid {$info['pid']}");
            } elseif ($mode === 'restart') {
                $this->output->writeln("Worker [{$name}] restarted to apply reload on Windows");
            } else {
                $this->output->writeln("Worker [{$name}] failed to reload");
                $status = 1;
            }
        }

        return $status;
    }

    /**
     * Restart services.
     *
     * @param string[] $targets
     */
    protected function restartTargets(array $targets): int
    {
        if (!$this->input->getOption('daemon')) {
            $status = $this->stopTargets($targets);
            foreach ($targets as $name) {
                $status = max($status, $this->serveService($this->withOverrides($this->workers->service($name))));
            }
            return $status;
        }

        $status = 0;
        foreach ($targets as $name) {
            if ($this->manager->workerRestart($name, true, $this->overrideOptions())) {
                $info = $this->manager->workerDescribe($name);
                $this->output->writeln("># Worker [{$name}] restarted successfully for pid {$info['pid']}");
            } else {
                $this->output->writeln("># Worker [{$name}] failed to restart");
                $status = 1;
            }
        }

        return $status;
    }

    /**
     * Start a detached background process.
     */
    protected function startDaemon(array $service): int
    {
        $name = $service['name'];
        $info = $this->manager->workerDescribe($name);

        $command = $this->manager->workerCommand('serve', $name, true, $this->overrideOptions());
        $this->output->comment(">$ {$this->process->think($command)}");

        if ($info['running']) {
            $this->output->writeln("># Worker [{$name}] already running for pid {$info['pid']}");
            return 0;
        }

        if ($this->manager->workerStart($name, true, $this->overrideOptions())) {
            $info = $this->manager->workerDescribe($name);
            $this->output->writeln("># Worker [{$name}] started successfully for pid {$info['pid']}");
            return 0;
        }

        $this->output->writeln("># Worker [{$name}] failed to start");
        return 1;
    }

    /**
     * Build service objects and run Workerman.
     */
    protected function serveService(array $service): int
    {
        $this->prepareRuntime($service);
        $this->prepareWorkermanArgs();

        $label = $service['label'] ?: strtoupper($service['name']);
        $this->output->writeln("Starting worker service [{$service['name']}] ({$label})...");
        if ($this->process->isWin()) {
            $this->output->writeln('You can exit with <info>`CTRL-C`</info>');
        }

        foreach ($this->buildServiceWorkers($service) as $worker) {
            unset($worker);
        }

        Workerman::runAll();
        return 0;
    }

    /**
     * Prepare static Workerman runtime options.
     */
    protected function prepareRuntime(array $service): void
    {
        $runtime = (array)$service['runtime'];
        foreach (['pidFile', 'logFile', 'statusFile', 'stdoutFile'] as $name) {
            if (!empty($runtime[$name])) {
                $dir = dirname((string)$runtime[$name]);
                is_dir($dir) or mkdir($dir, 0777, true);
            }
        }

        Workerman::$daemonize = !$this->process->isWin() && (bool)$this->input->getOption('daemon');
        foreach (['pidFile', 'logFile', 'statusFile', 'stdoutFile', 'logFileMaxSize', 'stopTimeout', 'eventLoopClass', 'onMasterReload', 'onMasterStop', 'onWorkerExit'] as $name) {
            if (array_key_exists($name, $runtime) && $runtime[$name] !== null && $runtime[$name] !== '') {
                Workerman::${$name} = $runtime[$name];
            }
        }
    }

    /**
     * Instantiate runtime workers for a service.
     *
     * @return array<int, mixed>
     */
    protected function buildServiceWorkers(array $service): array
    {
        $workers = [];
        if ($service['classes'] !== []) {
            foreach ($service['classes'] as $class) {
                if (!class_exists($class)) {
                    throw new \InvalidArgumentException("Worker service class [{$class}] is not defined.");
                }
                $workers[] = new $class();
            }
            return $workers;
        }

        if ($service['driver'] === 'http') {
            $server = (array)$service['server'];
            $worker = new HttpServer(
                (string)$server['host'],
                intval($server['port']),
                (array)$server['context'],
                is_callable($server['callable']) ? $server['callable'] : null,
                $service,
            );
            $worker->setRoot($this->app->getRootPath());
            $this->applyWorkerOptions($worker, $service);
            return [$worker];
        }

        if ($service['driver'] === 'queue') {
            return [new QueueServer($service, $this->app->getRootPath())];
        }

        throw new \InvalidArgumentException("Unsupported worker driver [{$service['driver']}], only http and queue are supported.");
    }

    /**
     * Apply CLI host/port overrides.
     */
    protected function withOverrides(array $service): array
    {
        $host = $this->input->getOption('host');
        $port = $this->input->getOption('port');

        if (is_string($host) && $host !== '') {
            $service['server']['host'] = $host;
            $service['host'] = $host;
        }
        if (is_numeric($port) && intval($port) > 0) {
            $service['server']['port'] = intval($port);
            $service['port'] = intval($port);
        }

        return $service;
    }

    /**
     * Apply worker-level options.
     */
    protected function applyWorkerOptions(object $worker, array $service): void
    {
        foreach ((array)$service['process'] as $name => $value) {
            if (in_array($name, ['daemonize', 'statusFile', 'stdoutFile', 'pidFile', 'logFile', 'logFileMaxSize', 'stopTimeout', 'eventLoopClass', 'onMasterReload', 'onMasterStop', 'onWorkerExit'], true)) {
                continue;
            }
            $worker->{$name} = $value;
        }
    }

    /**
     * Build the detached start command.
     */
    protected function overrideOptions(): array
    {
        $options = [];
        if (($host = $this->input->getOption('host')) && is_string($host) && $host !== '') {
            $options['host'] = $host;
        }
        if (($port = $this->input->getOption('port')) && is_numeric($port) && intval($port) > 0) {
            $options['port'] = intval($port);
        }
        return $options;
    }

    /**
     * Prepare Workerman CLI arguments for in-process startup.
     */
    protected function prepareWorkermanArgs(): void
    {
        global $argv;

        $argv = [
            $argv[0] ?? 'think',
            'start',
        ];
        if (!$this->process->isWin() && $this->input->getOption('daemon')) {
            $argv[] = '-d';
        }
    }

    /**
     * Normalize a probe host for local runtime checks.
     */
    protected function probeHost(string $host): string
    {
        return in_array($host, ['', '0.0.0.0', '::', '[::]'], true) ? '127.0.0.1' : $host;
    }

    /**
     * Probe an HTTP endpoint and return the raw status line.
     *
     * @return array{0:bool,1:string,2:string}
     */
    protected function probeHttp(string $host, int $port, string $path = '/'): array
    {
        if ($port < 1) {
            return [false, '', 'invalid HTTP port'];
        }

        $socket = @fsockopen($host, $port, $errno, $error, 3.0);
        if (!is_resource($socket)) {
            return [false, '', trim("connect {$host}:{$port} failed ({$errno}) {$error}")];
        }

        stream_set_timeout($socket, 3);
        fwrite($socket, "GET {$path} HTTP/1.1\r\nHost: {$host}\r\nConnection: close\r\n\r\n");
        $statusLine = trim(strval(fgets($socket)));
        fclose($socket);

        if ($statusLine === '') {
            return [false, '', 'empty HTTP response'];
        }

        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $statusLine, $match)) {
            $status = intval($match[1]);
            if (($status >= 200 && $status < 400) || in_array($status, [401, 403], true)) {
                return [true, $statusLine, ''];
            }
        }

        return [false, $statusLine, "unexpected HTTP response: {$statusLine}"];
    }

    /**
     * Remove transient queue artifacts after a successful smoke check.
     */
    protected function cleanupQueueSmokeTask(string $code): void
    {
        $this->app->cache->delete("queue_{$code}_progress");
        SystemQueue::mk()->where(['code' => $code])->delete();
    }

    /**
     * Handle unsupported action.
     */
    protected function invalidAction(): int
    {
        $this->output->error('Wrong operation, allow start|serve|stop|restart|reload|status|query|check');
        return 1;
    }
}
