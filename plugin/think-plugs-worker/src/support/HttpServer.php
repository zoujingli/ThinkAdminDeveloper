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

use plugin\worker\Monitor;
use plugin\worker\Server;
use think\admin\Library;
use think\admin\service\RuntimeService;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;
use Workerman\Protocols\Http\Session;
use Workerman\Timer;
use Workerman\Worker;

/**
 * 自定义 Http 服务
 * @class HttpServer
 * @package plugin\worker\support
 */
class HttpServer extends Server
{
    /** @var ThinkApp */
    protected $app;

    /** @var string */
    protected $root;

    /** @var array */
    protected $monitor;

    /** @var callable */
    protected $callable;

    public function __construct(string $host = '127.0.0.1', int $port = 2346, array $context = [], ?callable $callable = null)
    {
        $this->port = $port;
        $this->host = $host;
        $this->root = dirname(__DIR__, 4);
        $this->context = $context;
        $this->protocol = 'http';
        $this->callable = $callable;
        parent::__construct();
    }

    protected function init()
    {
    }

    /**
     * onWorkerStart
     * @param \Workerman\Worker $worker
     */
    public function onWorkerStart(Worker $worker)
    {
        // 创建基础应用
        $this->app = new ThinkApp($this->root);
        $this->app->bind('think\Cookie', ThinkCookie::class);
        $this->app->bind('think\Request', ThinkRequest::class);

        // 抢占必需替换的类名，并优先加载进内存
        if (!class_exists('think\response\File', false)) {
            class_alias(ThinkResponseFile::class, 'think\response\File');
        }

        // 设置文件变化及内存超限监控管理
        if (0 == $worker->id && $this->monitor && Library::$sapp->isDebug()) {
            Monitor::enableChangeMonitor($this->monitor['change_path'] ?? [], $this->monitor['change_exts'] ?? ['*'], $this->monitor['change_time'] ?? 0);
            Monitor::enableMemoryMonitor($this->monitor['memory_limit'] ?? null, $this->monitor['memory_time'] ?? 0);
        }

        // 初始化运行环境
        RuntimeService::init($this->app)->initialize();

        // 定时发起数据库请求，防止失效而锁死
        Timer::add(60, function () {
            $this->app->db->query(sprintf('select %d as stime', time()));
        });

        // 初始化会话
        Session::$name = $this->app->config->get('session.name', 'ssid');
        Session::$domain = $this->app->config->get('cookie.domain', '');
        Session::$secure = $this->app->config->get('cookie.secure', false);
        Session::$httpOnly = $this->app->config->get('cookie.httponly', true);
        Session::$sameSite = $this->app->config->get('cookie.samesite', '');
        Session::$lifetime = $this->app->config->get('session.expire', 7200);
        Session::$cookiePath = $this->app->config->get('cookie.path', '/');
        Session::$cookieLifetime = $this->app->config->get('cookie.expire', 0);
    }

    /**
     * onMessage
     * @param TcpConnection $connection
     * @param WorkerRequest $request
     */
    public function onMessage(TcpConnection $connection, WorkerRequest $request)
    {
        // 请求服务器实体文件，检测文件状态并发送结果
        if (is_file($file = syspath("public{$request->path()}"))) {
            // 检查 if-modified-since 头判断文件是否修改过
            if (!empty($modifiedSince = $request->header('if-modified-since'))) {
                $modifiedTime = date('D, d M Y H:i:s', filemtime($file)) . ' ' . date_default_timezone_get();
                // 文件未修改则返回 304，直接使用本地文件缓存
                if ($modifiedTime === $modifiedSince) {
                    $connection->send(new WorkerResponse(304, ['Server' => 'x-server']));
                    return;
                }
            }
            // 文件修改过或者没有 if-modified-since 头则发送文件
            $connection->send((new WorkerResponse())->withFile($file)->header('Server', 'x-server'));
            return;
        }

        // 自定义消息回调处理，返回 true 则终止后面的处理
        if (is_callable($this->callable)) {
            if (call_user_func($this->callable, $connection, $request) === true) return;
        }

        // 转发消息并初始化框架，调度 path 对应的系统功能
        $this->app->worker($connection, $request);
    }

    /**
     * 设置系统根路径
     * @param string $path
     * @return void
     */
    public function setRoot(string $path)
    {
        $this->root = $path;
    }

    /**
     * 设置文件监控配置
     * @param integer $time
     * @param array $path 监听目录
     * @param array $exts 文件后缀
     * @return void
     */
    public function setMonitorChange(int $time = 2, array $path = [], array $exts = ['*'])
    {
        $this->monitor['change_path'] = $path;
        $this->monitor['change_exts'] = $exts;
        $this->monitor['change_time'] = $time;
    }

    /**
     * 设置内存监控配置
     * @param integer $time
     * @param ?string $limit
     * @return void
     */
    public function setMonitorMemory(int $time = 60, ?string $limit = null)
    {
        $this->monitor['memory_time'] = $time;
        $this->monitor['memory_limit'] = $limit;
    }
}