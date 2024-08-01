<?php

// +----------------------------------------------------------------------
// | Worker Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-worker
// | github 代码仓库：https://github.com/zoujingli/think-plugs-worker
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\worker\support;

use think\Request;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Worker;

/**
 * 自定义 Request
 * @class ThinkRequest
 * @package plugin\worker\support
 */
class ThinkRequest extends Request
{
    /**
     * 初始化属性
     * @return void
     */
    public function reset()
    {
        static $props = [];
        if (empty($props)) $props = (new \ReflectionClass(Request::class))->getDefaultProperties();
        foreach ($props as $k => $v) isset($this->$k) && $this->$k = $v;
    }

    /**
     * WorkermanRequest 转换为 ThinkRequest 对象
     * @param \Workerman\Connection\TcpConnection $connection
     * @param \Workerman\Protocols\Http\Request $request
     * @return \plugin\worker\support\ThinkRequest
     */
    public function withWorkerRequest(TcpConnection $connection, WorkerRequest $request): ThinkRequest
    {
        // 初始化变量
        $this->reset();

        // 赋值新的变量
        $this->get = $request->get();
        $this->file = $request->file() ?? [];
        $this->post = $request->post();
        $this->cookie = $request->cookie();
        $this->header = $request->header();
        $this->method = $request->method();
        $this->request = $this->post + $this->get;
        $this->pathinfo = ltrim($request->path(), '\\/');

        // 请求真实IP
        $this->realIP = $this->header['x-real-ip'] ?? ($this->header['x-forwarded-for'] ?? $connection->getRemoteIp());

        // 请求地址域名及端口处理
        $this->host = $this->header['x-host'] ?? ($this->header['x-requested-host'] ?? ($this->header['remote-host'] ?? $request->host()));
        $port = intval($this->header['x-requested-port'] ?? (strpos($this->host, ':') !== false ? explode(':', $this->host)[1] : $connection->getRemoteIp()));
        if (strpos($this->host, ':') !== false && in_array($port, [80, 443])) $this->host = strstr($this->host, ':', true);

        // 替换全局变量
        $_GET = $request->get();
        $_POST = $request->post();
        $_REQUEST = $request->post() + $request->get() + $request->cookie();
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->method());
        $GLOBALS['HTTP_RAW_POST_DATA'] = $request->rawBody();

        // 服务变量替换
        return $this->withInput($request->rawBody())->withServer(array_filter([
            'HTTP_HOST'              => $this->host,
            'PATH_INFO'              => $this->pathinfo,
            'REQUEST_URI'            => $request->uri(),
            'SERVER_NAME'            => $request->host(true),
            'SERVER_ADDR'            => $connection->getLocalIp(),
            'SERVER_PORT'            => $port,
            'REMOTE_ADDR'            => $this->realIP,
            'REMOTE_PORT'            => $connection->getRemotePort(),
            'QUERY_STRING'           => $request->queryString(),
            'REQUEST_METHOD'         => $request->method(),
            'REQUEST_SCHEME'         => $this->header['x-scheme'] ?? null,
            'HTTP_X_PJAX'            => $this->header['x-pjax'] ?? null,
            'HTTP_X_FORWARDED_HOST'  => $this->header['x-forwarded-host'] ?? null,
            'HTTP_X_REQUESTED_WITH'  => $this->header['x-forwarded-with'] ?? null,
            'HTTP_X_FORWARDED_PORT'  => $this->header['x-requested-port'] ?? null,
            'HTTP_X_FORWARDED_PROTO' => $this->header['x-forwarded-proto'] ?? null,
            'HTTP_ACCEPT'            => $this->header['accept'] ?? null,
            'HTTP_ACCEPT_ENCODING'   => $this->header['accept-encoding'] ?? null,
            'HTTP_ACCEPT_LANGUAGE'   => $this->header['accept-language'] ?? null,
            'HTTP_USER_AGENT'        => $this->header['user-agent'] ?? null,
            'HTTP_COOKIE'            => $this->header['cookie'] ?? null,
            'HTTP_CACHE_CONTROL'     => $this->header['cache-control'] ?? null,
            'HTTP_PRAGMA'            => $this->header['pragma'] ?? null,
            'SERVER_PROTOCOL'        => $this->header['x-scheme'] ?? ($port === 443 ? 'https' : 'http'),
            'SERVER_SOFTWARE'        => 'Server/' . Worker::VERSION,
            'REQUEST_TIME'           => time(),
            'REQUEST_TIME_FLOAT'     => microtime(true),
        ]));
    }
}