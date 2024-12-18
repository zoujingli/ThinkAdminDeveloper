# ThinkPlugsWorker for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-worker/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-worker)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-worker/downloads)](https://packagist.org/packages/zoujingli/think-plugs-worker)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-worker/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-worker)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-worker/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-worker)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://poser.pugx.org/zoujingli/think-plugs-worker/license)](https://gitee.com/zoujingli/think-plugs-worker/blob/master/license)

基于 **Workerman 4.x** 且支持多种通信协议的基础插件。

**提示：** 默认支持以 HTTP 方式直接启动 ThinkAdmin 项目，无需配置 Nginx 或 Apache 环境，访问速度提升 N 倍。

**注意：** 该插件支持 `Workerman` 或 `Gateway` 两种运行方式，默认只安装了 `Workerman` 组件，如果需要使用 `Gateway` 组件，请另行安装。
配置文件的根配置参数是启动 **http** 服务进程，用来运行 **ThinkAdmin v6** 程序。
如果需要使用其他协议，请使用并修改 `customs` 配置或追加配置，并通过指定 `--custom name` 配置名来启动对应服务进程。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 注意，插件仅支持在 ThinkAdmin v6.1 中使用
composer require zoujingli/think-plugs-worker --optimize-autoloader
```

### 卸载插件

```shell
composer remove zoujingli/think-plugs-worker
```

### 配置参数

配置文件 `config/worker.php`

```php
return [
    // 服务监听地址
    'host' => '127.0.0.1',
    // 服务监听端口
    'port' => 2346,
    // 套接字上下文选项
    'context' => [],
    // 高级自定义服务类
    'classes' => '',
    // 消息请求回调处理
    'callable' => null,
    // 服务进程参数配置
    'worker' => [
        // 进程名称
        "name" => 'ThinkAdmin',
        // 进程数量
        'count' => 4,
    ],
    // 自定义服务配置（可选）
    'customs' => [
        // 自定义 text 服务
        'text' => [
            // 进程类型(Workerman|Gateway|Register|Business)
            'type' => 'Workerman',
            // 监听地址(<协议>://<地址>:<端口>)
            'listen' => 'text://0.0.0.0:8685',
            // 高级自定义服务类
            'classes' => '',
            // 套接字上下文选项
            'context' => [],
            // 服务进程参数配置
            'worker' => [
                //'name' => 'TextTest',
                // onWorkerStart => [class,method]
                // onWorkerReload => [class,method]
                // onConnect => [class,method]
                // onBufferFull => [class,method]
                // onBufferDrain => [class,method]
                // onError => [class,method]
                // 设置连接的 onMessage 回调
                'onMessage' => function ($connection, $data) {
                    $connection->send("hello world");
                }
            ]
        ],
        // 自定义 websocket 服务
        'websocket' => [
            // 进程类型(Workerman|Gateway|Register|Business)
            'type' => 'Workerman',
            // 监听地址(<协议>://<地址>:<端口>)
            'listen' => 'websocket://0.0.0.0:8686',
            // 高级自定义服务类
            'classes' => '',
            // 套接字上下文选项
            'context' => [],
            // 服务进程参数配置
            'worker' => [
                //'name' => 'TextTest',
                // onWorkerStart => [class,method]
                // onWorkerReload => [class,method]
                // onConnect => [class,method]
                // onBufferFull => [class,method]
                // onBufferDrain => [class,method]
                // onError => [class,method]
                // 设置连接的 onMessage 回调
                'onMessage' => function ($connection, $data) {
                    $connection->send("hello world");
                }
            ]
        ],
        // 自定义 Gateway 服务
        'gateway' => [
            // 进程类型(Workerman|Gateway|Register|Business)
            'type' => 'Gateway',
            // 监听地址(<协议>://<地址>:<端口>)
            'listen' => 'websocket://127.0.0.1:8689',
            // 高级自定义服务类
            'classes' => '',
            // 套接字上下文选项
            'context' => [],
            // 服务进程参数配置
            'worker' => [
                // 进程名称
                "name" => 'Gateway',
                // 进程数量
                 "count" => 4,
                // 心跳发送时间，针对客户端
                'pingInterval' => 10,
                // 心跳容错次数，针对客户端
                'pingNotResponseLimit' => 0,
                // 心跳包内容，针对客户端
                'pingData' => '{"type":"ping"}',
                 // 服务器内网IP
                "lanIp" => '127.0.0.1',
                // Business 回复 Gateway 端口
                "startPort" => 2000,
               // 注册服务地址，与 Register 进程对应
                "registerAddress" => '127.0.0.1:1236',
                // 进程启动回调
                "onWorkerStart" => function () {
                    echo "Gateway onWorkerStart" . PHP_EOL;
                },
                 // 进程停止回调
                "onWorkerStop" => function () {
                    echo "Gateway onWorkerStop" . PHP_EOL;
                }
            ]
        ],
        // 自定义 Register 服务
        'register' => [
            // 进程类型(Workerman|Gateway|Register|Business)
            'type' => 'Register',
            // 监听地址(<协议>://<地址>:<端口>)
            // 注意：别改这里的协议，只支持 text 协议
            'listen' => 'text://127.0.0.1:1236'
        ],
        // 自定义 Business 服务
        'business' => [
            // 进程类型(Workerman|Gateway|Register|Business)
            'type' => 'Business',
            // 高级自定义服务类
            'classes' => '',
            // 服务进程参数配置
            'worker' => [
                // 进程名称
                "name" => 'Business',
                // 进程数量
                "count" => 4,
                // 注册服务地址，与 Register 进程对应
                "registerAddress" => '127.0.0.1:1236',
                // 业务处理类
                "eventHandler" => Events::class,
                // 进程启动回调
                "onWorkerStart" => function () {
                    echo "Business onWorkerStart" . PHP_EOL;
                },
                // 进程停止回调
                "onWorkerStop" => function () {
                    echo "Business onWorkerStart" . PHP_EOL;
                }
            ]
        ],
    ],
];

/**
 * 业务处理类
 * @class Events
 */
class Events
{

    /**
     * 业务进程启动
     * @param $businessWorker
     * @return void
     */
    public static function onWorkerStart($businessWorker)
    {
        echo "Events WorkerStart\n";
    }

    /**
     * 有消息时触发该方法
     * @param int $clientid 发消息的client_id
     * @param mixed $message 消息
     * @throws \Exception
     */
    public static function onMessage($clientid, $message)
    {
        // 群聊，转发请求给其它所有的客户端
        \GatewayWorker\Lib\Gateway::sendToAll("Message By Events : {$message}");
    }
}
```

### 使用方法

在命令行启动服务端

```shell
#========= 启动参数配置 =========
### 守护方式运行  -d
### 指定监听域名  --host 127.0.0.1
### 指定监听端口  --port 2346 
### 启动指定服务  --custom websocket

# 通过 Workerman 方式，启动默认 Http 服务
php think xadmin:worker

# 通过 Workerman 方式，启动自定义 text 服务，注意 text 为 customs 配置项
php think xadmin:worker --custom text

# 通过 Workerman 方式，启动自定义 WebSocket 服务，注意 websocket 为 customs 配置项
php think xadmin:worker --custom websocket

# 通过 Gateway 方式，需要同时启动三个进程，另外还需要安装 workerman/gateway-worker 依赖包。
# 具体业务处理逻辑写在 business 绑定的 Events 中，了解更新多配置请阅读 Workerman 官方文档。
php think xadmin:worker --custom register
php think xadmin:worker --custom gateway
php think xadmin:worker --custom business

```

然后就可以通过浏览器直接访问当前应用

```
http://localhost:2346
```

默认使用 `Workerman` 工作方式，如果需要使用  `Gateway` 方式，需要安装 `GatewayWorker` 组件。

安装 `GatewayWorker` 的指令如下：

```shell
# 安装 GatewayWorker 组件
composer require workerman/gateway-worker
```

**注意：** 启用 `Gateway` 时需要单独启动三个进程，分别是 `Gateway`、`Register`、`Business`，中间需要 `Register` 进程连接。

**数据通信模型：**

`Client` `<->` `Gateway` `<->` `Register` `<->` `Business` `<->` `Events`

**Linux** 支持操作指令如下：

```shell
php think xadmin:worker [start|stop|reload|restart|status|-d]

# 以上所有操作效果与 Workerman 官方操作一致，详情请阅读对应文档。
```

**Windows** 支持操作指令如下：

```shell
php think xadmin:worker [start|stop|status|-d]

# 以上 stop|status|-d 操作是基于 wimc 实现，Workerman 官方不支持此种方式操作。  
```

其他 **workerman** 的参数可以在应用配置目录下的 **worker.php** 里面 **worker** 项配置。

更多其他特性请阅读 **workerman** 文档 https://www.workerman.net/doc/workerman

### 代理配置

Nginx 代理代理配置参考如下：

```
location ^~ / {

    # 执行代理访问真实服务器
    proxy_pass http://127.0.0.1:2346/;
    
    # 将客户端的 Host 和 IP 信息一并转发到对应节点
    proxy_set_header Host $http_host;
    proxy_set_header X-Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    
    # 将协议转发到对应节点，如果使用非 https 请改为 http
    proxy_set_header X-scheme https;
    
    proxy_set_header REMOTE-HOST $remote_addr;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
    proxy_http_version 1.1;
    
    # proxy_hide_header Upgrade;
    add_header X-Cache $upstream_cache_status;
}
```

### 版权说明

**ThinkPlugsWorker** 遵循 **Apache2** 开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有 Copyright © 2014-2024 by ThinkAdmin (https://thinkadmin.top) All rights reserved。

更多细节参阅 [LICENSE.txt](license)
