<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | Copyright 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 */

namespace plugin\worker\support;

use think\Request;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Worker;

use const DIRECTORY_SEPARATOR;

/**
 * ThinkPHP request bridge for Workerman.
 */
class ThinkRequest extends Request
{
    /**
     * Reset request state before reusing the same instance.
     */
    public function reset(): void
    {
        static $props = null;
        if ($props === null) {
            $props = (new \ReflectionClass(Request::class))->getDefaultProperties();
        }

        foreach ($props as $key => $value) {
            $this->{$key} = $value;
        }
    }

    /**
     * Convert a Workerman request into a ThinkPHP request.
     */
    public function withWorkerRequest(TcpConnection $connection, WorkerRequest $request): ThinkRequest
    {
        $this->reset();

        $headers = $request->header();
        $scheme = $this->resolveScheme($headers, $connection);
        $host = $this->resolveHost($request, $headers);
        $port = $this->resolvePort($host, $headers, $connection, $scheme);

        $this->get = $request->get();
        $this->post = $request->post();
        $this->file = $request->file() ?? [];
        $this->cookie = $request->cookie();
        $this->header = $headers;
        $this->method = strtoupper($request->method());
        $this->request = $this->post + $this->get;
        $this->pathinfo = ltrim($request->path(), '/\\');
        $this->realIP = $this->resolveRealIp($headers, $connection);
        $this->host = $this->normalizeHost($host, $port, $scheme);

        $server = $this->buildServer($request, $connection, $scheme, $port);

        $_GET = $this->get;
        $_POST = $this->post;
        $_COOKIE = $this->cookie;
        $_FILES = $this->file;
        $_REQUEST = $this->post + $this->get + $this->cookie;
        $_SERVER = $server;
        $GLOBALS['HTTP_RAW_POST_DATA'] = $request->rawBody();

        return $this
            ->withInput($request->rawBody())
            ->withHeader($headers)
            ->withGet($this->get)
            ->withPost($this->post)
            ->withCookie($this->cookie)
            ->withFiles($this->file)
            ->withServer($server);
    }

    private function resolveRealIp(array $headers, TcpConnection $connection): string
    {
        if (!empty($headers['x-real-ip'])) {
            return trim((string)$headers['x-real-ip']);
        }
        if (!empty($headers['x-forwarded-for'])) {
            return trim((string)strtok((string)$headers['x-forwarded-for'], ','));
        }

        return $connection->getRemoteIp();
    }

    private function resolveHost(WorkerRequest $request, array $headers): string
    {
        return (string)($headers['x-host']
            ?? $headers['x-requested-host']
            ?? $headers['x-forwarded-host']
            ?? $headers['remote-host']
            ?? $headers['host']
            ?? $request->host());
    }

    private function resolveScheme(array $headers, TcpConnection $connection): string
    {
        $scheme = strtolower((string)($headers['x-forwarded-proto'] ?? $headers['x-scheme'] ?? ''));
        if (in_array($scheme, ['http', 'https'], true)) {
            return $scheme;
        }

        return $connection->getLocalPort() === 443 ? 'https' : 'http';
    }

    private function resolvePort(string $host, array $headers, TcpConnection $connection, string $scheme): int
    {
        $port = $headers['x-forwarded-port'] ?? $headers['x-requested-port'] ?? $headers['x-port'] ?? null;
        if (is_numeric($port)) {
            return (int)$port;
        }

        if (str_contains($host, ':')) {
            return (int)substr((string)strrchr($host, ':'), 1);
        }

        if ($connection->getLocalPort() > 0) {
            return $connection->getLocalPort();
        }

        return $scheme === 'https' ? 443 : 80;
    }

    private function normalizeHost(string $host, int $port, string $scheme): string
    {
        $defaultPort = $scheme === 'https' ? 443 : 80;
        if ($port === $defaultPort && str_contains($host, ':')) {
            return (string)strstr($host, ':', true);
        }

        return $host;
    }

    private function buildServer(WorkerRequest $request, TcpConnection $connection, string $scheme, int $port): array
    {
        $root = dirname(__DIR__, 4);
        $pathInfo = '/' . ltrim($this->pathinfo, '/');
        $server = [
            'DOCUMENT_ROOT' => $root . DIRECTORY_SEPARATOR . 'public',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'index.php',
            'PHP_SELF' => '/index.php',
            'PATH_INFO' => $pathInfo === '/' ? '/' : $pathInfo,
            'REQUEST_URI' => $request->uri(),
            'QUERY_STRING' => $request->queryString(),
            'REQUEST_METHOD' => $this->method,
            'REQUEST_SCHEME' => $scheme,
            'SERVER_PROTOCOL' => 'HTTP/' . $request->protocolVersion(),
            'SERVER_SOFTWARE' => 'Workerman/' . Worker::VERSION,
            'SERVER_NAME' => $request->host(true) ?: $this->host,
            'SERVER_ADDR' => $connection->getLocalIp(),
            'SERVER_PORT' => $port,
            'REMOTE_ADDR' => $this->realIP,
            'REMOTE_PORT' => $connection->getRemotePort(),
            'HTTP_HOST' => $this->host,
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ];

        if ($scheme === 'https') {
            $server['HTTPS'] = 'on';
        }

        foreach ($this->header as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            if ($key === 'CONTENT_TYPE') {
                $server['CONTENT_TYPE'] = $value;
                continue;
            }
            if ($key === 'CONTENT_LENGTH') {
                $server['CONTENT_LENGTH'] = $value;
                continue;
            }
            $server["HTTP_{$key}"] = $value;
        }

        return array_filter($server, static fn ($value) => $value !== null && $value !== '');
    }
}
