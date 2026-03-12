<?php

declare(strict_types=1);

namespace plugin\worker\support;

use Psr\Log\NullLogger;
use think\App;
use think\admin\model\SystemQueue;
use think\admin\process\ProcessService;
use think\admin\runtime\RuntimeService;
use think\admin\queue\command\Queue as QueueCommand;
use Workerman\Timer;
use Workerman\Worker;

use const DIRECTORY_SEPARATOR;

/**
 * Workerman-based queue dispatcher.
 * Replaces the legacy while-true queue listener with timer-driven polling.
 */
class QueueWorker
{
    protected Worker $worker;

    protected ?App $app = null;

    protected ?WorkerMonitor $monitor = null;

    /** @var array<int, int> */
    protected array $timers = [];

    protected string $root;

    protected array $config = [];

    public function __construct(array $config = [], ?string $root = null)
    {
        $this->root = $root ? rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : dirname(__DIR__, 4) . DIRECTORY_SEPARATOR;
        $this->config = $config;
        $this->worker = new Worker();
        $this->worker->onWorkerStart = [$this, 'onWorkerStart'];
        $this->worker->onWorkerStop = [$this, 'onWorkerStop'];
        $this->worker->onWorkerReload = [$this, 'onWorkerReload'];
        $this->applyWorkerOptions();
    }

    /**
     * Run the queue worker in the current console process.
     */
    public static function runConsole(array $config = [], ?string $root = null): void
    {
        global $argv;

        $argv = [
            $argv[0] ?? 'think',
            'start',
        ];

        new self($config, $root);
        Worker::runAll();
    }

    /**
     * Boot the queue worker application and timers.
     */
    public function onWorkerStart(Worker $worker): void
    {
        $this->app = new App($this->root);
        RuntimeService::init($this->app)->initialize();
        $this->app->db->setLog(new NullLogger());

        $this->bootDispatchTimer();

        $this->monitor = new WorkerMonitor($this->app, $worker, $this->config);
        $this->monitor->start();
    }

    /**
     * Release timers when the worker is stopping.
     */
    public function onWorkerStop(): void
    {
        $this->stopTimers();
        $this->monitor?->stop();
    }

    /**
     * Release timers before the worker reloads.
     */
    public function onWorkerReload(): void
    {
        $this->onWorkerStop();
    }

    /**
     * Apply per-worker options from config/worker.php.
     */
    protected function applyWorkerOptions(): void
    {
        $options = (array)($this->config['process'] ?? []);
        foreach ($options as $name => $value) {
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
                'onWorkerStart',
                'onWorkerStop',
                'onWorkerReload',
                'onMessage',
            ], true)) {
                continue;
            }
            $this->worker->{$name} = $value;
        }

        $this->worker->name = $this->worker->name ?: 'ThinkAdminQueue';
        $this->worker->count = max(1, intval($this->worker->count ?: 1));
    }

    /**
     * Start timer-based queue dispatching.
     */
    protected function bootDispatchTimer(): void
    {
        if ($this->worker->id !== 0) {
            return;
        }

        $interval = max(1, intval($this->config['queue']['scan_interval'] ?? 1));
        $this->timers[] = Timer::add($interval, function (): void {
            $this->dispatchQueues();
        });
    }

    /**
     * Dispatch due queue records to isolated CLI workers.
     */
    protected function dispatchQueues(): void
    {
        if ($this->app === null) {
            return;
        }

        @file_put_contents(syspath('runtime/cache/time.queue'), strval(time()));

        $limit = max(1, intval($this->config['queue']['batch_limit'] ?? 20));
        $items = SystemQueue::mk()
            ->where([
                ['status', '=', QueueCommand::STATE_WAIT],
                ['exec_time', '<=', time()],
            ])
            ->order('exec_time asc')
            ->limit($limit)
            ->select();

        foreach ($items as $queue) {
            $this->dispatchQueue((string)$queue['code'], (string)$queue['title']);
        }

        function_exists('sysvar') && sysvar('', '');
    }

    /**
     * Spawn a single queue record in an isolated CLI process.
     */
    protected function dispatchQueue(string $code, string $title): void
    {
        $args = "xadmin:queue dorun {$code} -";

        try {
            if (count(ProcessService::thinkQuery($args)) > 0) {
                return;
            }

            ProcessService::thinkExec($args);
            Worker::log("># Created new process -> [{$code}] {$title}");
        } catch (\Throwable $exception) {
            SystemQueue::mk()->where(['code' => $code])->update([
                'status' => QueueCommand::STATE_ERROR,
                'outer_time' => microtime(true),
                'exec_desc' => $exception->getMessage(),
            ]);
            Worker::log("># Execution failed -> [{$code}] {$title}，{$exception->getMessage()}");
        }
    }

    /**
     * Stop all timer registrations.
     */
    protected function stopTimers(): void
    {
        foreach ($this->timers as $timerId) {
            Timer::del($timerId);
        }

        $this->timers = [];
    }
}
