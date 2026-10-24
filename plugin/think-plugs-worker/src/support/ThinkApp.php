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

namespace plugin\worker\support;

use think\App;
use think\exception\Handle;
use think\Middleware;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;

/**
 * 自定义基础类.
 * @class ThinkApp
 * @property ThinkCookie $cookie
 * @property ThinkRequest $request
 */
class ThinkApp extends App
{
    /** @var null|array Worker 启动时的中间件队列快照 */
    protected $workerMiddlewareQueue;

    /**
     * 保存常驻 Worker 的中间件启动快照.
     */
    public function captureWorkerState(): void
    {
        // Worker 启动时创建多应用中间件，确保其快照来自请求之前的基础状态。
        $middleware = 'think\\admin\\support\\middleware\\MultAccess';
        if (class_exists($middleware)) {
            $this->make($middleware);
        }
        $property = $this->middlewareQueueProperty();
        $queue = $property->getValue($this->middleware);
        $this->workerMiddlewareQueue = [];
        foreach (['global', 'app', 'route', 'controller'] as $type) {
            $this->workerMiddlewareQueue[$type] = $queue[$type] ?? [];
        }
    }

    /**
     * 恢复常驻 Worker 的中间件状态并清理路由实例.
     */
    public function resetWorkerState(): void
    {
        function_exists('sysvar') && sysvar('CurrentPluginCode', '');
        if (is_array($this->workerMiddlewareQueue)) {
            $property = $this->middlewareQueueProperty();
            $queue = $property->getValue($this->middleware);
            foreach ($this->workerMiddlewareQueue as $type => $middlewares) {
                $queue[$type] = $middlewares;
            }
            $property->setValue($this->middleware, $queue);
        }
        // 多应用中间件会在 HttpRun 事件后才执行；先清掉上次应用的事件和容器状态。
        $middleware = 'think\\admin\\support\\middleware\\MultAccess';
        if (class_exists($middleware) && $this->exists($middleware)) {
            $instance = $this->make($middleware);
            if (method_exists($instance, 'resetApplicationState')) {
                $instance->resetApplicationState();
            }
        }
        // 基线可能持有启动时的 route 实例，必须在恢复应用状态后删除，确保本次请求重新创建。
        $this->delete('route');
    }

    /**
     * 访问请求处理.
     */
    public function worker(TcpConnection $connection, WorkerRequest $request)
    {
        try {
            // 初始化请求
            $this->resetWorkerState();
            $this->delete('view');
            $this->db->clearQueryTimes();
            $this->beginTime = microtime(true);
            $this->beginMem = memory_get_usage();
            while (ob_get_level() > 1) {
                ob_end_clean();
            }

            // 切换进程数据
            $this->session->clear();
            $this->session->setId($request->sessionId());
            $this->request->withWorkerRequest($connection, $request);
            $response = $this->cookie->withWorkerResponse();

            ob_start();
            // 执行处理请求
            $thinkres = $this->http->run($this->request);
            $response->withBody(ob_get_clean() . $thinkres->getContent());
            $response->withStatus($thinkres->getCode()) && $this->cookie->save();
            $response->withHeaders($thinkres->getHeader() + ['Server' => 'x-server']);
            if (strtolower($request->header('connection', '')) === 'keep-alive') {
                $connection->send($response);
            } else {
                $connection->close($response);
            }

            // 结束当前请求
            $this->http->end($thinkres);
        } catch (\Error|\Exception|\RuntimeException|\Throwable $exception) {
            // 其他异常处理
            $this->showException($connection, $exception);
        } finally {
            // HttpEnd 可能因异常未触发，清理请求变量和应用状态，避免事件循环间隙可见脏数据。
            try {
                while (ob_get_level() > 1) {
                    ob_end_clean();
                }
                if ($this->exists('request')) {
                    $request = $this->make('request');
                    method_exists($request, 'clearWorkerGlobals') && $request->clearWorkerGlobals();
                }
                $this->resetWorkerState();
            } catch (\Throwable $cleanup) {
                // 清理失败不能覆盖原始响应，下一次请求入口仍会再次执行基线恢复。
            }
            function_exists('sysvar') && sysvar('', '');
        }
    }

    /**
     * 是否运行在命令行下.
     */
    public function runningInConsole(): bool
    {
        return false;
    }

    /**
     * 获取中间件队列属性.
     *
     * PHP 8.1 起 ReflectionProperty 可以直接访问非公开属性，避免调用
     * PHP 8.5 已弃用的 setAccessible().
     */
    protected function middlewareQueueProperty(): \ReflectionProperty
    {
        $property = new \ReflectionProperty(Middleware::class, 'queue');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        return $property;
    }

    /**
     * 输出异常信息.
     * @param \Exception|\RuntimeException|\Throwable $exception
     */
    private function showException(TcpConnection $connection, $exception)
    {
        if ($exception instanceof \Exception) {
            ($handler = $this->make(Handle::class))->report($exception);
            $resp = $handler->render($this->request, $exception);
            $connection->send(new WorkerResponse($resp->getCode(), ['Server' => 'x-server'], $resp->getContent()));
        } else {
            $connection->send(new WorkerResponse(500, ['Server' => 'x-server'], $exception->getMessage()));
        }
    }
}
