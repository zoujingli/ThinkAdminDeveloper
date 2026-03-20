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

use think\App;
use think\exception\Handle;
use think\Response;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;

/**
 * Worker-aware application wrapper.
 *
 * @property ThinkCookie $cookie
 * @property ThinkRequest $request
 * @property ThinkHttp $http
 */
class ThinkApp extends App
{
    protected int $requests = 0;

    /**
     * Handle a Workerman request.
     */
    public function worker(TcpConnection $connection, WorkerRequest $request): void
    {
        try {
            $this->delete('view');
            $this->db->clearQueryTimes();
            $this->beginTime = microtime(true);
            $this->beginMem = memory_get_usage();
            while (ob_get_level() > 1) {
                ob_end_clean();
            }

            $this->request->withWorkerRequest($connection, $request);
            $response = $this->cookie->withWorkerResponse();

            ob_start();
            $thinkResponse = $this->http->run($this->request);
            $response = $this->marshalResponse($response, $thinkResponse, ob_get_clean());
            $this->cookie->save();

            if (worker_auth_should_debug($request->path(), $this->request->cookie(), $this->request->header())) {
                worker_auth_debug('worker.response.out', [
                    'method' => strtoupper($request->method()),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                    'location' => $response->getHeader('Location'),
                    'set_cookie' => $response->getHeader('Set-Cookie'),
                ]);
            }

            if ($this->shouldKeepAlive($request)) {
                $connection->send($response);
            } else {
                $connection->close($response);
            }

            $this->http->end($thinkResponse);
            $this->collectGarbage();
        } catch (\Throwable $exception) {
            $this->showException($connection, $exception);
        }
    }

    /**
     * Worker must behave as a web runtime instead of console.
     */
    public function runningInConsole(): bool
    {
        return false;
    }

    private function marshalResponse(WorkerResponse $response, Response $thinkResponse, string $buffer): WorkerResponse
    {
        $response->withStatus($thinkResponse->getCode());
        $response->withHeaders($thinkResponse->getHeader() + ['Server' => 'x-server']);

        if ($thinkResponse instanceof ThinkResponseFile) {
            $thinkResponse->prepareDownload();
            $response->withHeaders($thinkResponse->getHeader() + ['Server' => 'x-server']);
            if ($thinkResponse->isFileResponse()) {
                return $response->withFile($thinkResponse->getFilePath());
            }
        }

        return $response->withBody($buffer . $thinkResponse->getContent());
    }

    private function shouldKeepAlive(WorkerRequest $request): bool
    {
        $connection = strtolower((string)$request->header('connection', ''));
        if ($request->protocolVersion() === '1.0') {
            return $connection === 'keep-alive';
        }

        return $connection !== 'close';
    }

    private function showException(TcpConnection $connection, \Throwable $exception): void
    {
        while (ob_get_level() > 1) {
            ob_end_clean();
        }

        $handler = $this->make(Handle::class);
        $handler->report($exception);
        try {
            $response = $handler->render($this->request, $exception);
            $connection->close($this->marshalResponse(new WorkerResponse(), $response, ''));
        } catch (\Throwable $renderException) {
            $handler->report($renderException);
            $connection->close($this->fallbackExceptionResponse($exception, $renderException));
        }
    }

    private function collectGarbage(): void
    {
        if (++$this->requests % 100 !== 0) {
            return;
        }

        gc_collect_cycles();
        if (function_exists('gc_mem_caches')) {
            gc_mem_caches();
        }
    }

    /**
     * Build a plain-text fallback response when exception rendering itself fails.
     */
    private function fallbackExceptionResponse(\Throwable $exception, ?\Throwable $renderException = null): WorkerResponse
    {
        $response = new WorkerResponse();
        $response->withStatus(500);
        $response->withHeaders([
            'Content-Type' => 'text/plain; charset=utf-8',
            'Server' => 'x-server',
        ]);

        $lines = [
            sprintf('%s: %s', $exception::class, $exception->getMessage()),
        ];

        if ($this->isDebug()) {
            $lines[] = sprintf('at %s:%d', $exception->getFile(), $exception->getLine());
            $lines[] = $exception->getTraceAsString();
            if ($renderException !== null && $renderException !== $exception) {
                $lines[] = '';
                $lines[] = sprintf(
                    'While rendering exception: %s: %s',
                    $renderException::class,
                    $renderException->getMessage()
                );
                $lines[] = sprintf('at %s:%d', $renderException->getFile(), $renderException->getLine());
                $lines[] = $renderException->getTraceAsString();
            }
        }

        return $response->withBody(implode(PHP_EOL, $lines));
    }
}
