# 插件详细说明

本文档详细描述每个插件的功能、结构、依赖和使用方式。

## 核心组件

### ThinkLibrary

**包名**: `zoujingli/think-library`

**定位**: 核心基础库，提供整个框架的运行时基础设施

**命名空间**: `think\admin\`

**服务注册**:
```json
{
  "extra": {
    "think": {
      "services": ["think\admin\Library"]
    }
  }
}
```

**核心功能**:
- **运行时服务**: `RuntimeService`, `AppService`, `NodeService`
- **认证会话**: `JwtToken`, `CacheSession`, `RequestTokenService`
- **路由适配**: `Route`, `Url`, `MultAccess` 中间件
- **队列契约**: `QueueService` (门面), `QueueServiceInterface` (契约)
- **Storage 门面**: `Storage` 统一存储接口
- **基础类型**: `Controller`, `Model`, `Command`, `Plugin`, `Service`, `Exception`
- **Helper 工具**: `QueryHelper`, `FormBuilder`, `PageBuilder`, `ValidateHelper`
- **扩展工具**: `CodeToolkit`, `FileTools`, `HttpClient`, `ArrayTree`

**目录结构**:
```
think-library/
├── src/
│   ├── contract/        # 标准契约接口
│   ├── extend/          # 扩展工具类
│   ├── helper/          # 构建器工具
│   ├── middleware/      # 中间件
│   ├── model/           # 模型扩展
│   ├── route/           # 路由相关
│   ├── runtime/         # 运行时上下文
│   ├── service/         # 核心服务
│   ├── common.php       # 全局函数
│   ├── Controller.php   # 标准控制器
│   ├── Model.php        # 标准模型
│   └── Library.php      # 服务注册类
└── tests/               # 单元测试
```

**依赖关系**:
- PHP >= 8.1
- ThinkPHP 8.1+
- 扩展：gd, curl, json, zlib, iconv, openssl, mbstring

---

### ThinkPlugsSystem

**包名**: `zoujingli/think-plugs-system`

**定位**: 系统后台，提供后台壳层、认证权限和系统运维能力

**命名空间**: `plugin\system\`

**服务注册**:
```json
{
  "extra": {
    "think": {
      "services": ["plugin\system\Service"]
    },
    "xadmin": {
      "app": {
        "code": "system",
        "prefix": "system"
      }
    }
  }
}
```

**数据表**:
- `system_base` - 系统基础配置
- `system_config` - 系统参数配置
- `system_data` - 系统扩展数据
- `system_oplog` - 系统操作日志
- `system_auth` - 系统权限角色
- `system_auth_node` - 权限节点绑定
- `system_menu` - 系统菜单管理
- `system_user` - 系统用户管理

**核心功能**:
- **后台壳层**: 登录页面、首页、后台框架
- **认证授权**: JWT 认证、RBAC 权限、菜单控制
- **系统用户**: 用户管理、角色管理、权限分配
- **系统配置**: 参数配置、字典管理、配置缓存
- **操作日志**: 操作记录、日志审计
- **任务管理**: 系统任务查看和管理

**目录结构**:
```
think-plugs-system/
├── src/
│   ├── controller/      # 控制器
│   ├── lang/           # 语言包
│   ├── middleware/     # 中间件 (JwtTokenAuth, RbacAccess)
│   ├── model/          # 模型
│   ├── route/          # 路由
│   ├── service/        # 服务
│   │   ├── CaptchaService.php
│   │   ├── SystemAuthService.php
│   │   ├── SystemContext.php
│   │   ├── SystemService.php
│   │   └── UserService.php
│   └── view/           # 视图模板
├── stc/database/       # 数据库迁移
└── tests/              # 单元测试
```

**依赖**:
- ThinkLibrary
- ThinkPlugsStatic
- ThinkPlugsStorage
- ThinkPlugsWorker

---

### ThinkPlugsWorker

**包名**: `zoujingli/think-plugs-worker`

**定位**: Workerman 运行时，提供 HTTP 和队列常驻服务

**命名空间**: `plugin\worker\`

**服务注册**:
```json
{
  "extra": {
    "think": {
      "services": ["plugin\worker\Service"]
    },
    "xadmin": {
      "app": {
        "code": "worker",
        "prefix": "worker"
      }
    }
  }
}
```

**数据表**:
- `system_queue` - 系统任务队列

**核心功能**:
- **HTTP 服务**: 托管系统 HTTP 服务，支持多进程
- **队列服务**: 长耗时任务调度、延时任务处理
- **进程管理**: 跨平台进程控制（start/stop/status/restart）
- **状态监控**: 内存监控、文件监控、健康检查

**命令入口**:
```bash
php think xadmin:worker start http -d      # 启动 HTTP 服务
php think xadmin:worker start queue -d     # 启动队列服务
php think xadmin:worker status all         # 查看运行状态
php think xadmin:worker stop http          # 停止 HTTP 服务
php think xadmin:worker restart all        # 重启所有服务
```

**跨平台实现**:
- **Linux/macOS**: Workerman 守护进程 + 信号控制（reload/stop）
- **Windows**: console.exe 启动后台 PHP 进程 + 进程扫描

**目录结构**:
```
think-plugs-worker/
├── src/
│   ├── command/         # CLI 命令
│   ├── model/           # 模型
│   ├── service/         # 服务
│   │   ├── Server.php
│   │   ├── QueueService.php
│   │   └── ProcessService.php
│   └── common.php       # 全局函数
├── stc/
│   └── worker.php       # 配置文件模板
└── tests/               # 单元测试
```

**依赖**:
- ThinkLibrary
- Workerman 5.1.9+
- Symfony/Process 6.0+

---

### ThinkPlugsStorage

**包名**: `zoujingli/think-plugs-storage`

**定位**: 存储中心，统一管理存储驱动和上传授权

**命名空间**: `plugin\storage\`

**服务注册**:
```json
{
  "extra": {
    "think": {
      "services": ["plugin\storage\Service"]
    },
    "xadmin": {
      "app": {
        "code": "storage",
        "prefix": "storage"
      }
    }
  }
}
```

**数据表**:
- `system_file` - 系统文件记录

**核心功能**:
- **驱动管理**: 本地存储、OSS、COS、七牛等驱动注册
- **配置中心**: 存储配置管理、驱动切换
- **上传授权**: 临时授权、上传令牌、安全校验
- **文件管理**: 文件列表、下载、删除、统计

**目录结构**:
```
think-plugs-storage/
├── src/
│   ├── controller/      # 控制器
│   ├── model/           # 模型
│   ├── service/         # 服务
│   │   ├── StorageConfig.php
│   │   ├── StorageManager.php
│   │   ├── StorageAuthorize.php
│   │   └── LocalStorage.php
│   └── view/           # 视图模板
├── stc/database/       # 数据库迁移
└── tests/               # 单元测试
```

**依赖**:
- ThinkLibrary

---

### ThinkPlugsHelper

**包名**: `zoujingli/think-plugs-helper`

**定位**: 开发辅助工具，提供迁移、发布、打包等开发功能

**命名空间**: `plugin\helper\`

**服务注册**:
```json
{
  "extra": {
    "think": {
      "services": ["plugin\helper\Service"]
    }
  }
}
```

**核心功能**:
- **发布工具**: `xadmin:publish` - 发布配置、静态资源、迁移脚本
- **打包工具**: `xadmin:package` - 生成安装包
- **迁移工具**: `xadmin:helper:migrate` - 生成插件迁移脚本
- **模型工具**: `xadmin:helper:model` - 生成模型文件
- **菜单工具**: 菜单校验、节点同步
- **注释工具**: 自动生成代码注释

**目录结构**:
```
think-plugs-helper/
├── src/
│   ├── command/         # CLI 命令
│   │   └── database/    # 数据库相关命令
│   ├── database/        # 数据库工具
│   ├── integration/     # 集成服务
│   ├── migration/       # 迁移工具
│   ├── model/           # 模型工具
│   ├── plugin/          # 插件工具
│   │   ├── PluginMenuService.php
│   │   └── PluginRegistry.php
│   └── service/         # 服务
├── tests/               # 单元测试
└── stc/
    └── database/        # 迁移模板
```

**依赖**:
- ThinkLibrary
- ThinkPlugsSystem
- ThinkPlugsWorker
- Doctrine/DBAL
- ThinkPHP Migration

---

### ThinkPlugsStatic

**包名**: `zoujingli/think-plugs-static`

**定位**: 静态资源和项目骨架组件

**特点**:
- 无前缀，不直接访问
- 通过其他插件间接使用
- 提供静态资源发布能力

**核心功能**:
- **静态资源**: LayUI、jQuery、前端模块等
- **模板文件**: 后台模板、错误页面、主题样式
- **项目骨架**: 新项目初始化模板
- **图标字体**: iconfont 图标库

---

## 平台插件

### ThinkPlugsWechatClient

**包名**: `zoujingli/think-plugs-wechat-client`

**定位**: 微信公众号标准平台

**命名空间**: `plugin\wechat\`

**访问前缀**: `wechat`

**核心功能**:
- 公众号基础配置
- 菜单管理
- 消息推送
- 粉丝管理
- 素材管理

**数据表**:
- 微信配置表
- 菜单表
- 消息记录表
- 粉丝表
- 素材表

---

### ThinkPlugsWechatService

**包名**: `zoujingli/think-plugs-wechat-service`

**定位**: 微信公众号开放平台（第三方平台托管）

**命名空间**: `plugin\wechat\service\`

**访问前缀**: `plugin-wechat-service`

**核心功能**:
- 第三方平台授权
- 组件配置管理
- 代公众号实现业务
- JSON-RPC 接口

**数据表**:
- 第三方授权表
- 组件配置表
- 预授权码表

---

## 业务插件

### ThinkPlugsAccount

**包名**: `zoujingli/think-plugs-account`

**定位**: 多端账号体系管理

**命名空间**: `plugin\account\`

**访问前缀**: `account`

**许可证**: VIP 授权

**数据表**:
- 用户账号表
- 终端绑定表
- 短信记录表
- 其他账号相关表

**菜单**:
- 用户管理
  - 用户账号管理
  - 终端账号管理
  - 手机短信管理

**依赖**:
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsStorage
- ThinkPlugsSystem
- ThinkPlugsWechatClient

---

### ThinkPlugsPayment

**包名**: `zoujingli/think-plugs-payment`

**定位**: 支付中心

**命名空间**: `plugin\payment\`

**访问前缀**: `payment`

**许可证**: VIP 授权

**数据表**:
- 支付配置表
- 交易记录表
- 退款记录表
- 余额明细表
- 积分明细表

**菜单**:
- 支付管理
  - 支付配置管理
  - 支付行为管理
  - 支付退款管理
  - 余额明细管理
  - 积分明细管理

**依赖**:
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsStorage
- ThinkPlugsSystem
- ThinkPlugsWorker
- ThinkPlugsAccount

---

### ThinkPlugsWemall

**包名**: `zoujingli/think-plugs-wemall`

**定位**: 分销商城系统

**命名空间**: `plugin\wemall\`

**访问前缀**: `wemall`

**许可证**: VIP 授权

**数据表**:
- 商品表
- 订单表
- 用户表
- 会员等级表
- 代理等级表
- 物流表
- 其他商城相关表

**菜单**:
- 商城配置
  - 数据统计报表
  - 系统通知管理
  - 商城参数管理
  - 推广海报管理
  - 店铺页面装修
  - 快递公司管理
  - 邮费模板管理
- 用户管理
  - 会员等级管理
  - 会员折扣方案
  - 会员用户管理
  - 创建会员用户
  - 用户余额充值
- 商城管理
  - 商品数据管理
  - 订单数据管理
  - 订单发货管理
  - 售后订单管理
  - 商品评论管理
- 代理管理
  - 代理等级管理
  - 代理返佣管理
  - 代理提现管理
- 帮助咨询
  - 常见问题管理
  - 意见反馈管理

**依赖**:
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsStorage
- ThinkPlugsSystem
- ThinkPlugsAccount
- ThinkPlugsPayment

---

### ThinkPlugsWuma

**包名**: `zoujingli/think-plugs-wuma`

**定位**: 一物一码与防伪溯源系统

**命名空间**: `plugin\wuma\`

**访问前缀**: `wuma`

**许可证**: VIP 授权

**数据表**:
- 防伪码表
- 溯源记录表
- 其他相关表

**依赖**:
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsSystem

---

## 插件依赖关系图

```
ThinkLibrary (核心基础)
    │
    ├─► ThinkPlugsSystem (系统后台)
    │       │
    │       ├─► ThinkPlugsStorage (存储中心)
    │       ├─► ThinkPlugsWorker (运行时)
    │       └─► ThinkPlugsHelper (开发辅助)
    │
    ├─► ThinkPlugsCenter (插件中心)
    ├─► ThinkPlugsWechatClient (公众号)
    └─► ThinkPlugsWechatService (开放平台)

业务插件依赖:
┌─────────────────────────────────────┐
│ ThinkPlugsAccount (账号)            │
│ 依赖：Helper, Storage, System,      │
│      WechatClient                   │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ ThinkPlugsPayment (支付)            │
│ 依赖：Account, Helper, Storage,     │
│      System, Worker                 │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ ThinkPlugsWemall (商城)             │
│ 依赖：Account, Payment, Helper,     │
│      Storage, System                │
└─────────────────────────────────────┘
                ↓
┌─────────────────────────────────────┐
│ ThinkPlugsWuma (一物一码)           │
│ 依赖：Helper, System                │
└─────────────────────────────────────┘
```

---

## 插件开发标准

### 目录规范

每个插件必须遵循以下目录结构:

```
plugin-name/
├── src/
│   ├── controller/      # 控制器（必需）
│   ├── model/           # 模型（可选）
│   ├── service/         # 服务（必需）
│   │   └── Service.php  # 服务注册入口
│   ├── view/            # 视图（可选）
│   ├── lang/            # 语言包（可选）
│   ├── route/           # 路由（可选）
│   ├── middleware/      # 中间件（可选）
│   ├── command/         # CLI 命令（可选）
│   └── common.php       # 全局函数（可选）
├── stc/
│   ├── config/          # 配置发布目录
│   ├── database/        # 数据库迁移
│   └── ...             # 其他发布资源
├── tests/               # 单元测试
├── composer.json        # Composer 配置
└── readme.md           # 插件说明
```

### Composer 配置标准

```json
{
  "type": "think-admin-plugin",
  "name": "zoujingli/think-plugs-{name}",
  "autoload": {
    "psr-4": {
      "plugin\\{name}\\": "src"
    }
  },
  "extra": {
    "think": {
      "services": ["plugin\\{name}\\Service"]
    },
    "xadmin": {
      "app": {
        "code": "{name}",
        "prefix": "{prefix}"
      },
      "menu": { ... },
      "migrate": { ... }
    }
  }
}
```

### 服务注册标准

每个插件必须在 `src/Service.php` 中定义服务类:

```php
<?php
namespace plugin\{name};

use think\Service;

class Service extends Service
{
    public function boot(): void
    {
        // 插件启动逻辑
    }
    
    public function register(): void
    {
        // 插件注册逻辑
    }
}
```

### 菜单元数据标准

菜单配置在 `composer.json` 的 `extra.xadmin.menu` 中:

```json
{
  "menu": {
    "show": true,
    "root": {
      "name": "菜单根名称",
      "sort": 100
    },
    "items": [
      {
        "name": "一级菜单",
        "subs": [
          {
            "name": "二级菜单",
            "icon": "layui-icon xxx",
            "node": "plugin/controller/action"
          }
        ]
      }
    ]
  }
}
```

### 迁移元数据标准

迁移配置在 `composer.json` 的 `extra.xadmin.migrate` 中:

```json
{
  "migrate": {
    "file": "20241010000001_install_{name}20241010.php",
    "class": "Install{Name}20241010",
    "name": "{Name}Plugin"
  }
}
```

---

## 插件测试标准

每个插件必须包含单元测试:

```php
<?php
namespace plugin\{name}\tests;

use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testPluginExists(): void
    {
        $this->assertTrue(class_exists('plugin\\{name}\\Service'));
    }
}
```

运行测试:
```bash
vendor/bin/phpunit --filter PluginTest
```

---

## 插件发布流程

### 发布配置

```bash
# 发布所有插件配置
php think xadmin:publish

# 发布并执行迁移
php think xadmin:publish --migrate
```

### 发布流程

1. 扫描所有插件的 `stc/config/` 目录
2. 复制配置文件到根目录 `config/`
3. 同步 `stc/database/` 到 `database/migrations/`
4. 发布静态资源到 `public/static/`
5. 记录发布状态到 `.xadmin-published.json`

---

## 插件卸载说明

**重要提示**: 插件卸载通常不会自动删除:
- 已执行的数据库迁移
- 历史业务数据
- 已发布的配置文件

如需完全卸载，需要:
1. 手动回滚数据库迁移
2. 手动删除业务数据
3. 手动清理配置文件

