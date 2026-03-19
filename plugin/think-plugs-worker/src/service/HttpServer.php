<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
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
 * ThinkAdmin 的 Workerman HTTP 服务实现。
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
        $this->public = $this->resolvePublicRoot();
        $this->context = $context;
        $this->protocol = 'http';
        $this->callable = $callable;
        $this->config = $config;
        parent::__construct();
    }

    /**
     * Worker 启动时初始化应用容器。
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
     * 处理 HTTP 请求与静态资源响应。
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

        $cookies = $request->cookie();
        $headers = $request->header();
        $shouldDebug = worker_auth_should_debug($request->path(), is_array($cookies) ? $cookies : [], is_array($headers) ? $headers : []);
        if ($shouldDebug) {
            worker_auth_trace_id(substr(md5(microtime(true) . $request->path() . getmypid()), 0, 12));
            worker_auth_debug('worker.request.in', [
                'method' => strtoupper($request->method()),
                'path' => $request->path(),
                'uri' => $request->uri(),
                'host' => $request->host(),
                'remote_ip' => $connection->getRemoteIp(),
                'cookies' => array_keys(is_array($cookies) ? $cookies : []),
                'system_cookie' => worker_auth_token_snapshot(strval($cookies['system_access_token'] ?? '')),
                'authorization' => worker_auth_token_snapshot(strval($headers['authorization'] ?? $headers['Authorization'] ?? '')),
            ]);
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
     * Worker 重载时释放监控资源。
     */
    public function onWorkerReload(Worker $worker): void
    {
        $this->monitor?->stop();
    }

    /**
     * 设置应用根目录。
     */
    public function setRoot(string $path): void
    {
        $this->root = rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
        $this->public = $this->resolvePublicRoot();
    }

    protected function init(): void {}

    private function resolvePublicRoot(): string
    {
        // 统一使用 runpath 计算可写 public 根目录（phar/普通环境一致）
        if (function_exists('runpath')) {
            return rtrim(runpath('public'), \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR;
        }
        return $this->root . 'public' . \DIRECTORY_SEPARATOR;
    }

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
