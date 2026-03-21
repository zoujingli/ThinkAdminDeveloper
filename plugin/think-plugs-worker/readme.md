# ThinkPlugsWorker

Workerman 运行时插件，提供 HTTP 和队列常驻服务，支持跨平台进程管理。

## 功能定位

- 使用 Workerman 托管系统 HTTP 服务
- 提供长耗时任务和延时任务调度
- 跨平台进程管理（Linux/macOS/Windows）
- 持有 `system_queue` 数据表

## 安装

```bash
composer require zoujingli/think-plugs-worker
```

## 配置

在 `composer.json` 中注册:

```json
{
  "extra": {
    "think": {
      "services": ["plugin\\worker\\Service"]
    },
    "xadmin": {
      "app": {
        "code": "worker",
        "prefix": "worker"
      },
      "publish": {
        "init": {
          "stc/worker.php": "config/worker.php"
        }
      },
      "migrate": {
        "file": "20241010000008_install_worker20241010.php",
        "class": "InstallWorker20241010",
        "name": "WorkerPlugin"
      }
    }
  }
}
```

## 命令入口

```bash
# 启动 HTTP 服务
php think xadmin:worker start http -d

# 启动队列服务
php think xadmin:worker start queue -d

# 查看运行状态
php think xadmin:worker status all

# 停止 HTTP 服务
php think xadmin:worker stop http

# 重启所有服务
php think xadmin:worker restart all

# 检查服务状态
php think xadmin:worker check
```

## 配置文件

`config/worker.php`:

```php
<?php
return [
    'defaults' => [
        'runtime' => [
            // 'stdout_file' => runpath('safefile/worker/shared.stdout.log'),
            // 'log_max_size' => 10 * 1024 * 1024,
            // 'stop_timeout' => 2,
        ],
        'monitor' => [
            'files' => [
                'enabled' => true,
                'interval' => 3,
                'paths' => ['app', 'config', 'route', 'plugin'],
                'extensions' => ['php', 'env', 'ini'],
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
                'scan_interval' => 1,      // 扫描间隔（秒）
                'batch_limit' => 20,       // 每批最多任务数
                'lock_timeout' => 3600,    // 任务锁超时（秒）
                'retain_days' => 7,        // 历史记录保留天数
            ],
        ],
    ],
];
```

## 核心功能

### 1. HTTP 服务

- 基于 Workerman 的高性能 HTTP 服务器
- 支持多进程并发（默认 4 进程）
- 支持文件监控和自动重载（开发环境）
- 内存监控和自动保护

### 2. 队列服务

- 长耗时任务调度
- 延时任务处理
- 任务锁机制
- 失败重试
- 历史记录保留

### 3. 进程管理

**跨平台实现**:

- **Linux/macOS**: 
  - Workerman 守护进程
  - 信号控制（reload/stop）
  - pidFile 辅助管理
  
- **Windows**:
  - console.exe 启动后台 PHP 进程
  - 进程扫描 + 健康检查
  - 不依赖 pidFile

**命令签名统一**:
```
xadmin:worker serve <service>
```

### 4. 状态监控

- 进程状态查看
- 内存使用监控
- 文件变更监控
- 健康检查

## 目录结构

```
think-plugs-worker/
├── src/
│   ├── command/
│   │   └── XadminWorker.php    # 统一命令入口
│   ├── model/
│   │   └── SystemQueue.php     # 队列模型
│   └── service/
│       ├── Server.php          # 服务器管理
│       ├── QueueService.php    # 队列服务
│       └── ProcessService.php  # 进程管理服务
├── stc/
│   └── worker.php              # 配置文件模板
└── tests/
```

## 使用示例

### 创建队列任务

```php
<?php
use think\admin\service\QueueService;

// 注册队列任务
$queue = QueueService::register(
    '导出数据任务',           // 任务标题
    'php think export:users', // 执行命令
    0,                        // 立即执行（延时秒数）
    ['user_id' => 1],         // 附加数据
    0                         // 循环等待时间
);

echo "任务编号：{$queue->getCode()}";
```

### 创建延时任务

```php
// 10 分钟后执行
$queue = QueueService::register(
    '定时清理缓存',
    'php think cache:clear',
    600,  // 10 分钟后执行
    [],
    0
);
```

### 创建循环任务

```php
// 每 5 分钟执行一次
$queue = QueueService::register(
    '定时统计',
    'php think statistics:daily',
    0,    // 立即执行
    [],
    300   // 每 300 秒（5 分钟）循环一次
);
```

### 在控制器中使用

```php
<?php
namespace plugin\system\controller;

use think\admin\Controller;

class Queue extends Controller
{
    /**
     * 创建导出任务
     */
    public function export()
    {
        $this->_queue(
            '导出用户数据',
            'php think export:users',
            0,
            ['status' => 1]
        );
    }
}
```

### 进程管理服务

```php
<?php
use plugin\worker\service\ProcessService;

// 获取服务状态
$status = ProcessService::status('http');
// 返回：['running' => true, 'workers' => 4, ...]

// 启动服务
ProcessService::start('http');

// 停止服务
ProcessService::stop('http');

// 重启服务
ProcessService::restart('http');

// 查询服务
$info = ProcessService::query('http');
```

## 队列数据表

`system_queue` 表结构:

```sql
CREATE TABLE `system_queue` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) NOT NULL COMMENT '任务编号',
  `title` varchar(200) NOT NULL COMMENT '任务名称',
  `command` varchar(500) NOT NULL COMMENT '执行命令',
  `status` tinyint(1) DEFAULT '0' COMMENT '状态 0:等待 1:处理 2:成功 3:失败',
  `loops` int(11) DEFAULT '0' COMMENT '循环间隔',
  `exec_time` int(11) DEFAULT '0' COMMENT '执行时间',
  `exec_desc` varchar(500) DEFAULT '' COMMENT '执行描述',
  `exec_at` int(11) DEFAULT '0' COMMENT '执行耗时',
  `entered_at` int(11) DEFAULT '0' COMMENT '进入时间',
  `created_at` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 跨平台说明

### Linux/macOS

**启动方式**:
```bash
# 守护进程启动
php think xadmin:worker start http -d

# 前台调试模式
php think xadmin:worker start http
```

**信号控制**:
```bash
# 平滑重载（不中断请求）
kill -USR1 <pid>

# 停止服务
kill -INT <pid>
```

**进程管理**:
- 使用 pidFile 记录主进程 PID
- 支持 reload 信号重载业务代码
- 支持优雅停止

### Windows

**启动方式**:
```bash
# 使用 console.exe 启动后台服务
plugin\think-plugs-worker\src\service\bin\console.exe start http

# 或者使用 PHP 启动
php think xadmin:worker start http
```

**进程管理**:
- 不使用 pidFile
- 通过进程扫描识别
- 通过健康检查判断状态
- 使用 Windows 服务管理

## 监控配置

### 文件监控

```php
'monitor' => [
    'files' => [
        'enabled' => true,        // 启用文件监控
        'interval' => 3,          // 检查间隔（秒）
        'paths' => ['app', 'config', 'plugin'],  // 监控目录
        'extensions' => ['php', 'env', 'ini'],   // 监控扩展名
    ],
]
```

### 内存监控

```php
'monitor' => [
    'memory' => [
        'enabled' => true,        // 启用内存监控
        'interval' => 60,         // 检查间隔（秒）
        'limit' => '1G',          // 内存限制
    ],
]
```

## 日志管理

### 日志位置

```
runtime/
└── log/
    ├── http/           # HTTP 服务日志
    │   ├── stdout.log  # 标准输出
    │   └── stderr.log  # 错误输出
    └── queue/          # 队列服务日志
        ├── stdout.log
        └── stderr.log
```

### 日志配置

```php
'runtime' => [
    'stdout_file' => runpath('safefile/worker/shared.stdout.log'),
    'log_max_size' => 10 * 1024 * 1024,  // 10MB
]
```

## 性能优化

### 进程数配置

```php
'process' => [
    'name' => 'ThinkAdminHttp',
    'count' => 4,  // 根据 CPU 核心数调整
]
```

建议配置:
- 1-2 核：2 进程
- 4 核：4 进程
- 8 核+: 8 进程

### 队列配置优化

```php
'queue' => [
    'scan_interval' => 1,      // 扫描间隔，高并发可增加
    'batch_limit' => 20,       // 批处理数量
    'lock_timeout' => 3600,    // 根据任务执行时间调整
    'retain_days' => 7,        // 历史记录保留天数
]
```

## 常见问题

### 1. 服务启动失败

**检查端口占用**:
```bash
netstat -ano | grep 2346
```

**检查权限**:
```bash
chmod +x think
```

### 2. 文件监控不生效

**检查配置**:
```php
'files' => [
    'enabled' => true,  // 确保启用
    'interval' => 3,    // 间隔不要太长
]
```

**检查目录权限**:
```bash
chmod -R 755 app/ config/ plugin/
```

### 3. 内存超限

**调整限制**:
```php
'memory' => [
    'limit' => '2G',  // 增加限制
]
```

**优化代码**:
- 避免大数组
- 及时释放资源
- 使用生成器

## 依赖

- PHP >= 8.1
- Workerman >= 5.1.9
- Symfony/Process >= 6.0
- ThinkLibrary

## 开发规范

### 命名空间

所有类使用 `plugin\worker\` 命名空间

### 代码风格

遵循 PSR-12 规范

## 测试

运行单元测试:

```bash
vendor/bin/phpunit --testsuite ThinkPlugsWorker
```

## 许可证

Apache-2.0 License

## 相关链接

- 官网文档：https://thinkadmin.top/plugin/think-plugs-worker.html
- Workerman 文档：https://www.workerman.net/doc/
- Gitee: https://gitee.com/zoujingli/ThinkAdmin
