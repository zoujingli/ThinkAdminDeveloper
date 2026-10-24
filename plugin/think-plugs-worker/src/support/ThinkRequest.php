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

use think\Request;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Worker;

/**
 * 自定义 Request.
 * @class ThinkRequest
 */
class ThinkRequest extends Request
{
    /** @var null|array Worker 进程启动时的超全局变量基线 */
    protected static $workerGlobalsBaseline;

    /**
     * 初始化属性.
     */
    public function reset()
    {
        static $props = [];
        if (empty($props)) {
            $props = (new \ReflectionClass(Request::class))->getDefaultProperties();
        }
        foreach ($props as $k => $v) {
            isset($this->{$k}) && $this->{$k} = $v;
        }
    }

    /**
     * 清理当前请求的 PHP 超全局变量，供 Worker 请求结束或异常时调用.
     */
    public function clearWorkerGlobals(): void
    {
        if (is_null(static::$workerGlobalsBaseline)) {
            return;
        }

        $this->reset();
        $_GET = static::$workerGlobalsBaseline['get'];
        $_POST = static::$workerGlobalsBaseline['post'];
        $_COOKIE = static::$workerGlobalsBaseline['cookie'];
        $_FILES = static::$workerGlobalsBaseline['files'];
        $_REQUEST = static::$workerGlobalsBaseline['request'];
        $_SERVER = static::$workerGlobalsBaseline['server'];
        if (static::$workerGlobalsBaseline['rawExists']) {
            $GLOBALS['HTTP_RAW_POST_DATA'] = static::$workerGlobalsBaseline['raw'];
        } else {
            unset($GLOBALS['HTTP_RAW_POST_DATA']);
        }
    }

    /**
     * WorkermanRequest 转换为 ThinkRequest 对象
     */
    public function withWorkerRequest(TcpConnection $connection, WorkerRequest $request): ThinkRequest
    {
        $headers = $request->header();
        $get = $request->get();
        $post = $request->post();
        $cookie = $request->cookie();
        $files = $request->file() ?? [];
        $raw = $request->rawBody();
        $method = $request->method();

        // 初始化变量
        $this->reset();

        // 赋值新的变量
        $this->get = $get;
        $this->file = $files;
        $this->post = $post;
        $this->cookie = $cookie;
        $this->header = $headers;
        $this->method = $method;
        $this->request = $this->post + $this->get;
        $this->pathinfo = ltrim($request->path(), '\/');

        // 请求真实IP
        $this->realIP = $headers['x-real-ip'] ?? ($headers['x-forwarded-for'] ?? $connection->getRemoteIp());

        // 请求服务器的域名处理
        $this->host = $headers['x-host'] ?? ($headers['x-requested-host'] ?? ($headers['remote-host'] ?? ($headers['host'] ?? $request->host())));

        // 请求服务器的端口处理
        if (!is_numeric($port = $headers['x-port'] ?? ($headers['x-requested-port'] ?? ($headers['port'] ?? null)))) {
            $port = strpos($this->host, ':') !== false ? explode(':', $this->host)[1] : $connection->getRemotePort();
        }
        $port = (int)$port;

        // 如果是正常端口，不需要在 host 表示端口
        if (strpos($this->host, ':') !== false && in_array($port, [80, 443])) {
            $this->host = strstr($this->host, ':', true);
        }

        $headerServer = [];
        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $headerServer[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : 'HTTP_' . $key] = $value;
        }
        $scheme = $headers['x-scheme'] ?? ($port === 443 ? 'https' : 'http');
        $protocol = method_exists($request, 'protocolVersion') ? $request->protocolVersion() : '1.1';
        if (stripos($protocol, 'HTTP/') === 0) {
            $protocol = substr($protocol, 5);
        }
        $server = array_filter(array_merge($headerServer, [
            'HTTP_HOST' => $this->host,
            'PATH_INFO' => $this->pathinfo,
            'REQUEST_URI' => $request->uri(),
            'SERVER_NAME' => $request->host(true),
            'SERVER_ADDR' => $connection->getLocalIp(),
            'SERVER_PORT' => $port,
            'REMOTE_ADDR' => $this->realIP,
            'REMOTE_PORT' => $connection->getRemotePort(),
            'QUERY_STRING' => $request->queryString(),
            'REQUEST_METHOD' => $method,
            'REQUEST_SCHEME' => $scheme,
            'SERVER_PROTOCOL' => 'HTTP/' . $protocol,
            'SERVER_SOFTWARE' => 'Server/' . Worker::VERSION,
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ]), static function ($value) {
            // 保留合法的 "0" 请求头和端口值，行为与 FPM 超全局一致。
            return $value !== null && $value !== '';
        });

        // 替换全局变量，保持常驻模式与 FPM 的请求边界一致
        $this->withWorkerGlobals($get, $post, $cookie, $files, $raw, $server);

        // 服务变量替换
        return $this->withInput($raw)->withServer($server);
    }

    /**
     * 同步当前请求的 PHP 超全局变量.
     * @param array $get GET 参数
     * @param array $post POST 参数
     * @param array $cookie Cookie 参数
     * @param array $files 上传文件
     * @param string $raw 原始请求体
     * @param array $server ThinkPHP 服务变量
     */
    protected function withWorkerGlobals(array $get, array $post, array $cookie, array $files, string $raw, array $server): void
    {
        if (is_null(static::$workerGlobalsBaseline)) {
            static::$workerGlobalsBaseline = [
                'get' => $_GET,
                'post' => $_POST,
                'cookie' => $_COOKIE,
                'files' => $_FILES,
                'request' => $_REQUEST,
                'server' => $_SERVER,
                'rawExists' => array_key_exists('HTTP_RAW_POST_DATA', $GLOBALS),
                'raw' => $GLOBALS['HTTP_RAW_POST_DATA'] ?? null,
            ];
        }

        $_GET = $get;
        $_POST = $post;
        $_COOKIE = $cookie;
        $_FILES = $files;
        $_REQUEST = $post + $get + $cookie;
        $_SERVER = static::$workerGlobalsBaseline['server'];
        $_SERVER = array_merge($_SERVER, $server);
        if (isset($server['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = strtoupper($server['REQUEST_METHOD']);
        }
        $GLOBALS['HTTP_RAW_POST_DATA'] = $raw;
    }
}
