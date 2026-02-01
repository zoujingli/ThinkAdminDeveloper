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

namespace plugin\worker;

use Workerman\Worker;

/**
 * Worker 服务基础类.
 * @class Server
 */
abstract class Server
{
    protected $worker;

    protected $socket = '';

    protected $protocol = 'http';

    protected $host = '0.0.0.0';

    protected $port = '2346';

    protected $option = [];

    protected $context = [];

    protected $event = [
        'onWorkerStart',
        'onConnect',
        'onMessage',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerReload',
        'onWebSocketConnect',
    ];

    /**
     * 服务构造方法.
     */
    public function __construct()
    {
        // 实例化 Websocket 服务
        $this->worker = new Worker($this->socket ?: $this->protocol . '://' . $this->host . ':' . $this->port, $this->context);

        // 设置参数
        if (!empty($this->option)) {
            foreach ($this->option as $key => $val) {
                $this->worker->{$key} = $val;
            }
        }

        // 设置回调
        foreach ($this->event as $event) {
            if (method_exists($this, $event)) {
                $this->worker->{$event} = [$this, $event];
            }
        }

        // 初始化
        $this->init();
    }

    /**
     * 动态设置属性.
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $this->worker->{$name} = $value;
    }

    /**
     * 动态调用方法.
     */
    public function __call(string $method, array $args)
    {
        call_user_func_array([$this->worker, $method], $args);
    }

    /**
     * 服务初始化方法.
     * @return mixed
     */
    abstract protected function init();
}
