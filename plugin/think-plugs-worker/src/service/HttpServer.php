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

use think\admin\service\RuntimeService;
use think\Http;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;
use Workerman\Timer;
use Workerman\Worker;

/**
 * Custom Http server for ThinkAdmin.
 */
class HttpServer extends Server
{
    protected ThinkApp $app;

    protected string $root;

    protected string $public;

    /** @var null|callable */
    protected $callable;

    protected array $config = [];

    protected ?WorkerMonitor $monitor;

    public function __construct(
        string $host = '127.0.0.1',
        int $port = 2346,
        array $context = [],
        ?callable $callable = null,
        array $config = [],
    ) {
        $this->port = $port;
        $this->host = $host;
        $this->root = dirname(__DIR__, 4) . \DIRECTORY_SEPARATOR;
        $this->public = $this->root . 'public' . \DIRECTORY_SEPARATOR;
        $this->context = $context;
        $this->protocol = 'http';
        $this->callable = $callable;
        $this->config = $config;
        parent::__construct();
    }

    /**
     * onWorkerStart.
     */
    public function onWorkerStart(Worker $worker): void
    {
        $this->app = new ThinkApp($this->root);
        $this->app->bind([
            'cookie' => ThinkCookie::class,
            'request' => ThinkRequest::class,
            'http' => ThinkHttp::class,
            'think\Cookie' => ThinkCookie::class,
            'think\Request' => ThinkRequest::class,
            Http::class => ThinkHttp::class,
        ]);

        if (!class_exists('think\response\File', false)) {
            class_alias(ThinkResponseFile::class, 'think\response\File');
        }

        RuntimeService::init($this->app)->initialize();
        $this->app->http->warmup();

        // Keep only active database connections alive.
        Timer::add(60, function (): void {
            foreach ($this->app->db->getInstance() as $connection) {
                try {
                    $connection->query('SELECT 1');
                } catch (\Throwable $exception) {
                    Worker::log($exception->getMessage());
                }
            }
        });

        $this->monitor = new WorkerMonitor($this->app, $worker, $this->config);
        $this->monitor->start();
    }

    /**
     * onMessage.
     */
    public function onMessage(TcpConnection $connection, WorkerRequest $request): void
    {
        if (($file = $this->resolvePublicFile($request->path())) !== null) {
            if (!empty($modifiedSince = $request->header('if-modified-since'))) {
                $modifiedTime = gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT';
                if (trim(strtok($modifiedSince, ';')) === $modifiedTime) {
                    $connection->send(new WorkerResponse(304, ['Server' => 'x-server']));
                    return;
                }
            }

            $connection->send((new WorkerResponse())->withFile($file)->header('Server', 'x-server'));
            return;
        }

        if (is_callable($this->callable)) {
            $result = call_user_func($this->callable, $connection, $request);
            if ($result instanceof WorkerResponse) {
                $connection->send($result);
                return;
            }
            if ($result === true) {
                return;
            }
        }

        RuntimeService::sync();
        $this->app->worker($connection, $request);
    }

    /**
     * onWorkerReload.
     */
    public function onWorkerReload(Worker $worker): void
    {
        $this->monitor?->stop();
    }

    /**
     * Set application root path.
     */
    public function setRoot(string $path): void
    {
        $this->root = rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
        $this->public = $this->root . 'public' . \DIRECTORY_SEPARATOR;
    }

    protected function init(): void {}

    private function resolvePublicFile(string $path): ?string
    {
        $path = rawurldecode($path);
        if ($path === '' || $path === '/') {
            return null;
        }

        $publicRoot = rtrim(realpath($this->public) ?: $this->public, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
        $candidate = realpath($this->public . ltrim(str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $path), \DIRECTORY_SEPARATOR));
        if ($candidate === false || !is_file($candidate)) {
            return null;
        }

        return str_starts_with($candidate, $publicRoot) ? $candidate : null;
    }
}
