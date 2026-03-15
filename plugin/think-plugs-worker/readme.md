# ThinkPlugsWorker for ThinkAdmin

`ThinkPlugsWorker` 是 ThinkAdmin 8 / ThinkPHP 8.1 的标准运行时插件，统一负责两类常驻服务：

- `http`：用 Workerman 托管 ThinkAdmin HTTP 服务
- `queue`：用 Workerman 托管后台任务调度与长耗时任务派发

当前实现不再沿用旧版 `default + customs` 配置模型。运行时统一入口为 `xadmin:worker`，队列运维入口为 `xadmin:queue`，配置结构统一为 `defaults + services`，并且只保留 `http` 与 `queue` 两类标准服务。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`
- Workerman `5.1+`

## 详细描述

- `ThinkPlugsWorker` 是标准运行时插件，负责把 ThinkAdmin 以常驻进程方式跑起来，而不是业务插件自己写守护逻辑。
- 当前只提供两类标准服务：`http` 用于托管 Web 服务，`queue` 用于托管后台任务调度。
- 组件同时承载完整的队列实现，包括 `system_queue` 模型、任务协议实现、执行器、运维命令和常驻调度；后台页面由 `ThinkPlugsSystem` 提供。
- 业务任务基类与运行时实现统一由 Worker 提供。
- 它的定位是“统一进程管理器”，而不是业务插件。
- `src` 目录已经统一收口为 `command / model / service`，其余 `ThinkApp / ThinkHttp / ThinkRequest / ThinkCookie / WorkerMonitor / WorkerState / ProcessService` 都归到 `service` 层，不再单独拆 `queue/support` 目录。

## 架构说明

- 命令层：`xadmin:worker` 负责启动、停止、重载、状态查询、健康检查和后台守护管理。
- 服务层：`HttpServer`、`QueueServer`、`QueueService`、`QueueExecutor`、`ThinkApp`、`WorkerConfig`、`WorkerState`、`WorkerMonitor`、`ProcessService` 统一放在 `src/service`。
- 协同层：HTTP 服务回调给 `ThinkLibrary runtime`，队列标准由 `ThinkLibrary` 定义并通过容器绑定到 `plugin\\worker\\service` 实现，`xadmin:queue` 负责队列清理与手动执行。

## 模块边界

- `ThinkPlugsWorker` 只负责运行时和进程管理
- `ThinkPlugsWorker` 负责队列运行时、任务记录与队列表迁移
- `ThinkLibrary` 只保留队列与进程的标准契约、门面和调用入口，不再存放具体实现
- `ThinkPlugsSystem` 负责后台界面与运行状态管理
- 业务插件只负责注册任务，不直接管理守护进程
- 标准 `ProcessService` 由 `ThinkLibrary` 暴露门面，具体 shell 与 Worker 控制实现都由 `ThinkPlugsWorker` 提供
- 全局辅助函数 `sysqueue` 由 `ThinkPlugsWorker` 提供

## 进程管理标准

- `ThinkPlugsWorker` 采用“固定命令签名 + 进程扫描 + 健康检查”的纯 PHP 进程控制模型，统一通过 `php think xadmin:worker` 对外提供管理入口。
- 每个标准服务都对应唯一命令签名：`http` 使用 `xadmin:worker serve http`，`queue` 使用 `xadmin:worker serve queue`。`start / stop / status / query / restart / check` 都围绕这个签名工作，而不是围绕临时 pid 文件工作。
- `pidFile / statusFile / logFile / stdoutFile` 仍然保留，用于 Workerman 运行时输出、调试与辅助诊断；但 `pidFile` 不再是唯一状态源。即使 runtime 文件被误删、清理脚本移除，或者服务异常退出后遗留脏文件，`status`、`query`、`stop`、`reload` 仍会优先回退到进程扫描。
- `ProcessService` 负责构建标准命令、启动后台进程、查询命令行、关闭匹配进程；`WorkerState` 负责把 pid、命令签名扫描结果与运行期检查统一折叠为标准状态对象，确保 CLI、后台页面和接口层拿到同一套判断结果。
- Linux / macOS 下优先使用 Workerman 的常规守护化与信号机制：有有效 `pidFile` 时按主进程控制，没有有效 `pidFile` 时自动回退到命令签名扫描，再执行停止、重启或重载操作。
- Windows 下后台启动统一通过 `src/service/bin/console.exe` 拉起独立 PHP 进程，不依赖 POSIX 信号，也不依赖 `pidFile` 存活。Windows 的 `status / query / stop / restart` 均通过固定命令签名扫描 `php.exe` 进程，再执行受控操作。
- `query` 会返回当前所有命中命令签名的进程，便于排查重复拉起、孤儿进程或旧版本残留进程；`stop` 会针对匹配到的服务进程执行统一清理，而不是假设系统中只存在一个 pid。
- `reload` 的语义统一为“尽可能平滑更新服务”：在 Linux / macOS 下优先发送 Workerman reload 信号，在 Windows 下自动退化为受控 `restart`，保证管理入口一致。
- `check` 属于运行健康检查而不是简单进程探测：`http` 会发起本地 HTTP 探针，`queue` 会创建并等待一个轻量 smoke task 完成。这样即使进程存在但业务循环已经失效，也能被识别出来。
- 这套标准的目标是把“进程身份”和“业务健康”分离：命令签名解决服务是谁的问题，健康检查解决服务是否真的可用的问题，最终避免因 pid 文件不可靠而导致的后台管理失真。

## 安装

```bash
composer require zoujingli/think-plugs-worker

# 初始化 config/worker.php 并同步队列迁移
php think xadmin:publish --migrate
```

`ThinkPlugsWorker` 通过组件发布清单注册 `config/worker.php` 初始化模板，不再依赖 Composer 安装阶段自动写入配置。

## 卸载

```bash
composer remove zoujingli/think-plugs-worker
```

## 配置

配置文件为 `config/worker.php`。如果项目中还没有该文件，请执行 `php think xadmin:publish --migrate` 生成默认模板并同步迁移。

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
                'count' => 2,
            ],
            'queue' => [
                'scan_interval' => 1,
                'batch_limit' => 20,
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
- `services.queue.process.count`：队列并发执行数，每个 Worker 进程同一时刻执行一个任务

### 标准键名

- 运行时建议使用蛇形键名：`stdout_file`、`log_max_size`、`stop_timeout`、`event_loop`
- 文件监控建议使用：`enabled`、`interval`、`paths`、`extensions`
- 队列调度建议使用：`scan_interval`、`batch_limit`
- 当前只支持标准结构：`defaults.runtime`、`defaults.monitor`、`services.<name>.server`、`services.<name>.process`、`services.<name>.queue`

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

php think xadmin:worker check http
php think xadmin:worker check queue
```

### 批量服务

```bash
php think xadmin:worker start all -d
php think xadmin:worker stop all
php think xadmin:worker status all
php think xadmin:worker query all
php think xadmin:worker check all
```

### 热重载

```bash
php think xadmin:worker reload http
php think xadmin:worker reload queue
```

`reload` 在 Linux / macOS 下发送 Workerman reload 信号，在 Windows 下会自动退化为受控 `restart`，尽量保持操作语义一致。

## 平台说明

### Linux / macOS

- `start -d` 会通过 Workerman 守护化运行
- 支持 `reload`
- 优先通过主进程与信号控制服务，`pidFile` 缺失时会自动回退到命令签名扫描
- 推荐配合 `systemd`、Supervisor、Docker 使用

### Windows

- `start -d` 通过独立后台进程启动，不依赖 POSIX 信号
- 支持 `start / stop / status / query / restart`
- `reload` 会自动退化为 `restart`
- 统一通过 `console.exe + php think xadmin:worker serve <service>` 运行后台服务
- 运行状态以命令签名扫描为主，不再要求 `pidFile` 必须存在
- 文件变化监控会通过独立控制进程触发重启，不再只输出手工重启提示
- 建议优先用 `services.<name>.server.host/port` 管理监听地址，避免依赖 shell 包装脚本

## HTTP 服务说明

- `http` 服务会直接启动 ThinkAdmin 应用
- 支持静态文件直出
- 支持常驻模式预热
- 适合替代内置 `php think run`
- 可通过 `server.callable` 注入额外请求拦截逻辑
- 闭环是：`config/worker.php -> xadmin:worker -> service/HttpServer -> service/ThinkApp/ThinkHttp/ThinkRequest/ThinkCookie -> ThinkAdmin 响应`

## Queue 服务说明

- `queue` 服务由多个 Worker 进程直接扫描并认领 `system_queue`
- 并发度由 `services.queue.process.count` 控制
- 适合延时任务、循环任务、长耗时任务
- 任务记录、执行结果和 `system_queue` 数据表由 `ThinkPlugsWorker` 维护
- `queue.scan_interval` 控制轮询周期，`queue.batch_limit` 控制单次认领候选量
- 闭环是：`QueueService::register/sysqueue -> system_queue -> QueueServer claim -> QueueExecutor run -> progress/message/exec_desc -> System 队列页`

## 插件数据

- 队列记录：`system_queue`

## 生产建议

- 生产环境关闭调试模式
- 为 `http` 与 `queue` 分别保留独立日志
- 队列任务本身应保持幂等
- 长驻服务内避免使用请求级静态状态

## 许可证

`ThinkPlugsWorker` 基于 `Apache-2.0` 发布。
