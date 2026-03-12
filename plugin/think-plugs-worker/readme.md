# ThinkPlugsWorker for ThinkAdmin

`ThinkPlugsWorker` 是 ThinkAdmin 8 / ThinkPHP 8.1 的标准运行时插件，统一负责两类常驻服务：

- `http`：用 Workerman 托管 ThinkAdmin HTTP 服务
- `queue`：用 Workerman 托管后台任务调度与长耗时任务派发

当前实现不再沿用旧版 `default + customs` 配置模型，也不再建议通过 `xadmin:queue` 管理守护进程。外部统一入口为 `xadmin:worker`，配置结构统一为 `defaults + services`。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`
- Workerman `5.1+`

## 详细描述

- `ThinkPlugsWorker` 是标准运行时插件，负责把 ThinkAdmin 以常驻进程方式跑起来，而不是业务插件自己写守护逻辑。
- 当前只提供两类标准服务：`http` 用于托管 Web 服务，`queue` 用于托管后台任务调度。
- 组件不再承载队列记录、任务逻辑或后台页面，这些能力分别留在 `ThinkLibrary` 和 `ThinkPlugsAdmin`。
- 它的定位是“统一进程管理器”，而不是业务插件。

## 架构说明

- 命令层：`xadmin:worker` 负责启动、停止、重载、状态查询和后台守护管理。
- 运行层：`HttpServer`、`QueueWorker`、`ThinkApp` 把 Workerman 与 ThinkAdmin 运行时接到一起。
- 配置层：`WorkerConfig`、`WorkerState`、`WorkerMonitor` 负责标准化配置、进程状态与运行监控。
- 协同层：HTTP 服务回调给 `ThinkLibrary runtime`，队列调度调用 `xadmin:queue dorun` 执行具体任务。

## 模块边界

- `ThinkPlugsWorker` 只负责运行时和进程管理
- `ThinkLibrary` 负责队列记录、任务注册、任务执行逻辑
- `ThinkPlugsAdmin` 负责后台界面与运行状态管理
- 业务插件只负责注册任务，不直接管理守护进程

## 安装

```bash
composer require zoujingli/think-plugs-worker

# 初始化 config/worker.php
php think xadmin:publish
```

`ThinkPlugsWorker` 通过组件发布清单注册 `config/worker.php` 初始化模板，不再依赖 Composer 安装阶段自动写入配置。

## 卸载

```bash
composer remove zoujingli/think-plugs-worker
```

## 配置

配置文件为 `config/worker.php`。如果项目中还没有该文件，请执行 `php think xadmin:publish` 生成默认模板。

```php
<?php

declare(strict_types=1);

return [
    'defaults' => [
        'runtime' => [
            // 'stdout_file' => syspath('safefile/worker/shared.stdout.log'),
            // 'log_max_size' => 10 * 1024 * 1024,
            // 'stop_timeout' => 2,
            // 'event_loop' => \Workerman\Events\Event::class,
        ],
        'monitor' => [
            'files' => [
                'enabled' => true,
                'interval' => 3,
                'paths' => ['app', 'config', 'route', 'plugin'],
                'extensions' => ['php', 'env', 'ini', 'yaml', 'yml'],
            ],
            'memory' => [
                'enabled' => true,
                'interval' => 60,
                'limit' => '1G',
            ],
        ],
    ],
    'services' => [
        'http' => [
            'enabled' => true,
            'label' => 'ThinkAdmin HTTP',
            'driver' => 'http',
            'server' => [
                'host' => '127.0.0.1',
                'port' => 2346,
                'context' => [],
            ],
            'process' => [
                'name' => 'ThinkAdminHttp',
                'count' => 4,
            ],
        ],
        'queue' => [
            'enabled' => true,
            'label' => 'ThinkAdmin Queue',
            'driver' => 'queue',
            'process' => [
                'name' => 'ThinkAdminQueue',
                'count' => 1,
            ],
            'queue' => [
                'scan_interval' => 1,
                'batch_limit' => 20,
            ],
        ],
        'websocket' => [
            'enabled' => false,
            'label' => 'ThinkAdmin WebSocket',
            'driver' => 'socket',
            'server' => [
                'scheme' => 'websocket',
                'host' => '0.0.0.0',
                'port' => 8686,
                'context' => [],
            ],
            'socket' => [
                'type' => 'workerman',
            ],
            'process' => [
                'name' => 'ThinkAdminWebSocket',
                'count' => 1,
            ],
        ],
    ],
];
```

### 配置说明

- `defaults.runtime`：共享运行时参数，负责 `pid/log/status/stdout` 等文件和 Workerman 静态选项
- `defaults.monitor`：共享监控参数，负责文件变更监控和内存阈值监控
- `services.<name>.server`：服务地址、端口、协议、上下文、回调
- `services.<name>.process`：Workerman worker 进程参数，例如 `name/count`
- `services.<name>.queue`：队列服务的调度参数，例如 `scan_interval/batch_limit`
- `services.<name>.socket`：socket 服务类型，例如 `workerman/gateway/register/business`

### 标准键名

- 运行时建议使用蛇形键名：`stdout_file`、`log_max_size`、`stop_timeout`、`event_loop`
- 文件监控建议使用：`enabled`、`interval`、`paths`、`extensions`
- 队列调度建议使用：`scan_interval`、`batch_limit`
- 旧键仍可读取，例如 `runtime/monitor`、`host/port`、`worker`、`dispatch`，但新文档与新模板只保留标准结构

## 命令

### 单服务

```bash
php think xadmin:worker start http
php think xadmin:worker start http -d

php think xadmin:worker start queue
php think xadmin:worker start queue -d

php think xadmin:worker stop http
php think xadmin:worker stop queue

php think xadmin:worker status http
php think xadmin:worker status queue

php think xadmin:worker query http
php think xadmin:worker query queue
```

### 批量服务

```bash
php think xadmin:worker start all -d
php think xadmin:worker stop all
php think xadmin:worker status all
php think xadmin:worker query all
```

### 热重载

```bash
php think xadmin:worker reload http
php think xadmin:worker reload queue
```

`reload` 仅适用于 Linux / macOS，Windows 下不会发送 POSIX 信号。

## 平台说明

### Linux / macOS

- `start -d` 会通过 Workerman 守护化运行
- 支持 `reload`
- 推荐配合 `systemd`、Supervisor、Docker 使用

### Windows

- `start -d` 通过独立后台进程启动，不依赖 POSIX 信号
- 支持 `start / stop / status / query`
- 文件变化监控仅输出重启提示，不自动向 master 发送 reload 信号
- 建议优先用 `services.<name>.server.host/port` 管理监听地址，避免依赖 shell 包装脚本

## HTTP 服务说明

- `http` 服务会直接启动 ThinkAdmin 应用
- 支持静态文件直出
- 支持常驻模式预热
- 适合替代内置 `php think run`
- 可通过 `server.callable` 注入额外请求拦截逻辑

## Queue 服务说明

- `queue` 服务定时扫描 `system_queue`
- 到期任务会派发为独立 CLI 子进程执行
- 适合延时任务、循环任务、长耗时任务
- 任务记录和执行结果仍由 `ThinkLibrary` 维护
- `queue.scan_interval` 控制轮询周期，`queue.batch_limit` 控制单次扫描量

## 生产建议

- 生产环境关闭调试模式
- 为 `http` 与 `queue` 分别保留独立日志
- 队列任务本身应保持幂等
- 长驻服务内避免使用请求级静态状态

## 许可证

`ThinkPlugsWorker` 基于 `Apache-2.0` 发布。
