# ThinkPlugsWorker for ThinkAdmin

ThinkPlugsWorker 是 ThinkAdmin 8 的 Workerman 常驻运行插件，当前对齐 `Workerman 5` 正式版运行模型，默认面向 `PHP 8.1+`。

## 版本基线

- ThinkAdmin: `8.x`
- PHP: `8.1+`
- Workerman: `5.1+`
- 当前插件约束: `workerman/workerman:^5.1.9`

## 特性

- 默认提供基于 Workerman 5 的 ThinkAdmin HTTP 启动方式
- 长驻模式下预热路由和中间件，减少重复加载
- 支持自定义 `Workerman`、`Gateway`、`Register`、`BusinessWorker` 服务
- 支持静态文件直出和文件下载响应优化
- 支持调试模式文件变更监控和内存阈值监控
- Windows 下补充了 `start -d`、`status`、`stop` 的进程识别

## 安装

```bash
composer require zoujingli/think-plugs-worker
```

如果项目已经安装旧版依赖，建议同步更新到当前稳定版：

```bash
composer update workerman/workerman zoujingli/think-plugs-worker --with-all-dependencies
```

## 卸载

```bash
composer remove zoujingli/think-plugs-worker
```

## 默认配置

配置文件为 `config/worker.php`，安装插件后会自动生成。

```php
<?php

declare(strict_types=1);

return [
    'host' => '127.0.0.1',
    'port' => 2346,
    'context' => [],
    'classes' => '',
    'callable' => null,
    'worker' => [
        'name' => 'ThinkAdmin',
        'count' => 4,
        // 'logFileMaxSize' => 10 * 1024 * 1024,
        // 'stopTimeout' => 2,
        // 'eventLoopClass' => \Workerman\Events\Event::class,
    ],
    'files' => [
        'time' => 3,
        'path' => [],
        'exts' => ['php', 'env', 'ini', 'yaml', 'yml'],
    ],
    'memory' => [
        'time' => 60,
        'limit' => '1G',
    ],
    'customs' => [
        'websocket' => [
            'type' => 'Workerman',
            'listen' => 'websocket://0.0.0.0:8686',
            'context' => [],
            'classes' => '',
            'worker' => [
                'name' => 'ThinkAdminWebSocket',
                'count' => 1,
                'onMessage' => static function ($connection, $data): void {
                    $connection->send((string)$data);
                },
            ],
        ],
    ],
];
```

## 启动方式

默认 HTTP 服务：

```bash
php think xadmin:worker
php think xadmin:worker start
php think xadmin:worker start -d
```

指定主机和端口：

```bash
php think xadmin:worker --host 0.0.0.0 --port 9501
```

启动自定义服务：

```bash
php think xadmin:worker --custom websocket
```

## 管理命令

Linux / macOS:

```bash
php think xadmin:worker [start|stop|reload|restart|status|connections]
php think xadmin:worker start -d
```

Windows:

```bash
php think xadmin:worker [start|stop|status]
php think xadmin:worker start -d
```

说明：

- `reload`、`restart`、`connections` 依赖 POSIX 信号，不适用于 Windows
- Windows 下调试文件监控只输出重启提示，不会像 Linux 一样自动向 master 发送重载信号
- 生产环境仍建议优先部署在 Linux，并使用 Supervisor、systemd、Docker 或容器编排托管

## 自定义服务

`customs` 中的每个节点都会通过 `--custom <name>` 启动。常见类型：

- `Workerman`
- `Gateway`
- `Register`
- `Business`

如果你要使用 GatewayWorker，请先安装：

```bash
composer require workerman/gateway-worker
```

示例：

```php
'customs' => [
    'websocket' => [
        'type' => 'Workerman',
        'listen' => 'websocket://0.0.0.0:8686',
        'worker' => [
            'name' => 'ThinkAdminWebSocket',
            'count' => 1,
            'onMessage' => static function ($connection, $data): void {
                $connection->send((string)$data);
            },
        ],
    ],
],
```

## 反向代理

推荐把公网流量通过 Nginx 转发到 Worker 端口：

```nginx
location / {
    proxy_pass http://127.0.0.1:2346;
    proxy_http_version 1.1;

    proxy_set_header Host $http_host;
    proxy_set_header X-Host $http_host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Port $server_port;

    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection $connection_upgrade;
}
```

## 运行建议

- 生产环境建议关闭 ThinkAdmin 调试模式
- 建议安装 `ext-event` 以获得更好的事件循环性能
- 大文件下载优先走 Worker 直出，避免 PHP 进程内存暴涨
- 长驻模式下请避免把请求级状态写到静态变量

## 许可证

`ThinkPlugsWorker` 基于 `Apache-2.0` 发布。
