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

namespace plugin\worker\service;

use plugin\worker\model\SystemQueue;
use Psr\Log\NullLogger;
use think\admin\runtime\RequestContext;
use think\admin\service\RuntimeService;
use think\App;
use Workerman\Timer;
use Workerman\Worker;

/**
 * Workerman-based queue server.
 * Replaces the legacy while-true queue listener with timer-driven polling.
 */
class QueueServer
{
    protected Worker $worker;

    protected ?App $app = null;

    protected ?WorkerMonitor $monitor = null;

    /** @var array<int, int> */
    protected array $timers = [];

    protected string $root;

    protected array $config = [];

    protected bool $running = false;

    public function __construct(array $config = [], ?string $root = null)
    {
        $this->root = $root ? rtrim($root, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR : dirname(__DIR__, 4) . \DIRECTORY_SEPARATOR;
        $this->config = $config;
        $this->worker = new Worker();
        $this->worker->onWorkerStart = [$this, 'onWorkerStart'];
        $this->worker->onWorkerStop = [$this, 'onWorkerStop'];
        $this->worker->onWorkerReload = [$this, 'onWorkerReload'];
        $this->applyWorkerOptions();
    }

    /**
     * Run the queue server in the current console process.
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
     * Boot the queue server application and timers.
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
        $interval = max(1, intval($this->config['queue']['scan_interval'] ?? 1));
        $this->timers[] = Timer::add($interval, function (): void {
            $this->dispatchQueues();
        });
    }

    /**
     * Claim and execute due queue records inside Worker processes.
     */
    protected function dispatchQueues(): void
    {
        if ($this->app === null || $this->running) {
            return;
        }

        @file_put_contents(syspath('runtime/cache/time.queue'), strval(time()));

        $limit = max(1, intval($this->config['queue']['batch_limit'] ?? 20));
        if (!$queue = $this->claimQueue($limit)) {
            function_exists('sysvar') && sysvar('', '');
            return;
        }

        $this->running = true;

        try {
            Worker::log("># Worker {$this->worker->id} claimed queue -> [{$queue['code']}] {$queue['title']}");
            $result = QueueExecutor::instance()->run($queue['code'], true);
            $extra = $result['message'] === '' ? '' : " {$result['message']}";
            $state = match ($result['status']) {
                QueueService::STATE_DONE => 'Completed',
                QueueService::STATE_ERROR => 'Failed',
                default => 'Skipped',
            };
            Worker::log("># {$state} queue -> [{$queue['code']}] {$queue['title']}{$extra}");
        } catch (\Throwable $exception) {
            SystemQueue::mk()->where(['code' => $queue['code']])->update([
                'status' => QueueService::STATE_ERROR,
                'outer_time' => microtime(true),
                'exec_desc' => $exception->getMessage(),
            ]);
            Worker::log("># Execution failed -> [{$queue['code']}] {$queue['title']}，{$exception->getMessage()}");
        } finally {
            $this->running = false;
            RequestContext::clear();
            function_exists('sysvar') && sysvar('', '');
        }
    }

    /**
     * Atomically claim the next due queue task.
     *
     * @return null|array{code:string,title:string}
     */
    protected function claimQueue(int $limit): ?array
    {
        $items = SystemQueue::mk()->where([
            ['status', '=', QueueService::STATE_WAIT],
            ['exec_time', '<=', time()],
        ])->order('exec_time asc')->limit($limit)->select();

        foreach ($items as $queue) {
            $code = (string)$queue['code'];
            $claim = SystemQueue::mk()->strict(false)->where([
                ['code', '=', $code],
                ['status', '=', QueueService::STATE_WAIT],
                ['exec_time', '<=', time()],
            ])->inc('attempts')->update([
                'enter_time' => microtime(true),
                'outer_time' => 0,
                'exec_pid' => getmypid(),
                'exec_desc' => '',
                'status' => QueueService::STATE_LOCK,
            ]);

            if ($claim > 0) {
                return ['code' => $code, 'title' => (string)$queue['title']];
            }
        }

        return null;
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
