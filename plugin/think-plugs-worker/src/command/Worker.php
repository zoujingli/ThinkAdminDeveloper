<?php

declare(strict_types=1);

namespace plugin\worker\command;

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use InvalidArgumentException;
use plugin\worker\support\HttpServer;
use plugin\worker\support\QueueWorker;
use plugin\worker\support\WorkerConfig;
use plugin\worker\support\WorkerState;
use think\admin\Command;
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

    public function configure(): void
    {
        $this->setName('xadmin:worker')
            ->addArgument('action', Argument::OPTIONAL, 'start|serve|stop|restart|reload|status|query', 'start')
            ->addArgument('target', Argument::OPTIONAL, 'http|queue|all|<service>', 'http')
            ->addOption('host', 'H', Option::VALUE_OPTIONAL, 'Override the service host.')
            ->addOption('port', 'p', Option::VALUE_OPTIONAL, 'Override the service port.')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the service in background mode.')
            ->setDescription('Manage ThinkPlugsWorker HTTP and queue runtimes');
    }

    public function execute(Input $input, Output $output): int
    {
        $this->workers = new WorkerConfig($this->app);
        $action = strtolower(trim((string)$input->getArgument('action')));
        $target = strtolower(trim((string)$input->getArgument('target')));

        try {
            $targets = $this->workers->targets($target);
        } catch (InvalidArgumentException $exception) {
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
            $service = $this->workers->service($name);
            $state = new WorkerState($service);
            $info = $state->describe();
            if (!$info['running']) {
                $this->output->writeln("Worker [{$name}] is not running");
                continue;
            }

            if ($state->stop()) {
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
            $info = (new WorkerState($this->workers->service($name)))->describe();
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
            $info = (new WorkerState($this->workers->service($name)))->describe();
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
     * Reload POSIX services.
     *
     * @param string[] $targets
     */
    protected function reloadTargets(array $targets): int
    {
        if ($this->process->iswin()) {
            $this->output->error('Reload is only available on Linux or macOS.');
            return 1;
        }

        $status = 0;
        foreach ($targets as $name) {
            $state = new WorkerState($this->workers->service($name));
            $info = $state->describe();
            if (!$info['running']) {
                $this->output->writeln("Worker [{$name}] is not running");
                continue;
            }

            if ($state->reload()) {
                $this->output->writeln("Worker [{$name}] reload signal sent to pid {$info['pid']}");
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
        $status = $this->stopTargets($targets);
        foreach ($targets as $name) {
            $service = $this->withOverrides($this->workers->service($name));
            $status = max($status, $this->input->getOption('daemon')
                ? $this->startDaemon($service)
                : $this->serveService($service));
        }

        return $status;
    }

    /**
     * Start a detached background process.
     */
    protected function startDaemon(array $service): int
    {
        $name = $service['name'];
        $state = new WorkerState($service);
        $info = $state->describe();

        $command = $this->daemonCommand($name);
        $this->output->comment(">$ {$this->process->think($command)}");

        if ($info['running']) {
            $this->output->writeln("># Worker [{$name}] already running for pid {$info['pid']}");
            return 0;
        }

        $this->process->thinkExec($command);
        if ($state->waitStarted()) {
            $info = $state->describe();
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
        if ($this->process->iswin()) {
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

        Workerman::$daemonize = !$this->process->iswin() && (bool)$this->input->getOption('daemon');
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
                    throw new InvalidArgumentException("Worker service class [{$class}] is not defined.");
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
            return [new QueueWorker($service, $this->app->getRootPath())];
        }

        $worker = $this->makeSocketWorker($service);
        $this->applyWorkerOptions($worker, $service);
        return [$worker];
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
     * Create a generic socket runtime worker.
     */
    protected function makeSocketWorker(array $service): BusinessWorker|Gateway|Register|Workerman
    {
        $type = strtolower((string)($service['socket']['type'] ?? 'workerman'));
        $listen = $this->resolveListen($service);
        $context = (array)($service['server']['context'] ?? []);

        return match ($type) {
            'gateway' => class_exists(Gateway::class)
                ? new Gateway($listen, $context)
                : throw new InvalidArgumentException('Please install workerman/gateway-worker first.'),
            'register' => class_exists(Register::class)
                ? new Register($listen, $context)
                : throw new InvalidArgumentException('Please install workerman/gateway-worker first.'),
            'business' => class_exists(BusinessWorker::class)
                ? new BusinessWorker($listen, $context)
                : throw new InvalidArgumentException('Please install workerman/gateway-worker first.'),
            default => new Workerman($listen, $context),
        };
    }

    /**
     * Resolve the socket listen address.
     */
    protected function resolveListen(array $service): string
    {
        $server = (array)($service['server'] ?? []);
        $listen = trim((string)($server['listen'] ?? ''));
        if ($listen === '') {
            $scheme = strtolower((string)($server['scheme'] ?? 'websocket'));
            $host = trim((string)($server['host'] ?? '')) ?: '0.0.0.0';
            $port = max(1, intval($server['port'] ?? 80));
            return "{$scheme}://{$host}:{$port}";
        }

        if (!is_array($attr = parse_url($listen))) {
            return $listen;
        }

        $scheme = strtolower((string)($server['scheme'] ?? ($attr['scheme'] ?? 'websocket')));
        $host = trim((string)($server['host'] ?? ''));
        $port = intval($server['port'] ?? 0);
        $host = $host !== '' ? $host : (string)($attr['host'] ?? '0.0.0.0');
        $port = max(1, $port > 0 ? $port : intval($attr['port'] ?? 80));
        return "{$scheme}://{$host}:{$port}";
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
     * Build the detached start command.
     */
    protected function daemonCommand(string $name): string
    {
        $command = "xadmin:worker serve {$name} -d";
        if (($host = $this->input->getOption('host')) && is_string($host) && $host !== '') {
            $command .= " --host {$host}";
        }
        if (($port = $this->input->getOption('port')) && is_numeric($port) && intval($port) > 0) {
            $command .= " --port " . intval($port);
        }

        return $command;
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
        if (!$this->process->iswin() && $this->input->getOption('daemon')) {
            $argv[] = '-d';
        }
    }

    /**
     * Handle unsupported action.
     */
    protected function invalidAction(): int
    {
        $this->output->error('Wrong operation, allow start|serve|stop|restart|reload|status|query');
        return 1;
    }
}
