# ThinkLibrary for ThinkAdmin

**ThinkLibrary** 是 ThinkAdmin 8 / ThinkPHP 8.1 的核心基础库，负责控制器基类、模型基类、运行时路由绑定、插件元数据解析、JWT 认证、任务执行协议、通用工具和公共门面。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkLibrary` 是整个仓库的核心基础库，负责定义统一的控制器基类、模型基类、插件基类、命令基类和运行时公共能力。
- 它承载的是“框架级通用能力”，包括插件优先路由、JWT 认证、任务执行协议、菜单节点扫描、模型与查询标准化、系统配置和通用工具。
- 当前已经不再保留旧多应用模式的历史兼容结构，也不再承担存储驱动、守护进程、发布导出、物流短信等非核心实现。
- 组件目标是作为所有插件共享的核心层，而不是再继续堆放具体业务逻辑。

## 架构说明

- 基础入口层：`Controller`、`Model`、`Service`、`Plugin`、`Command` 为所有插件提供统一基类和调用约定。
- 运行时层：`runtime` 负责插件发现、URL 前缀绑定、单应用兜底、URL 生成和运行时同步。
- 请求上下文层：`context` 负责当前插件、当前后台用户、当前令牌这类请求级状态，不再散落在 `sysvar()` 字符串键中。
- 认证层：`auth` 负责后台 JWT、图形验证码、令牌校验和认证中间件。
- 任务层：`queue` 只保留任务记录、任务执行协议和执行命令，常驻调度已交给 `ThinkPlugsWorker`。
- 支撑层：`menu / node / module / process / system / query / model / view / extend / contract / helper` 负责菜单、节点、模块、进程、系统配置、查询工厂、模型工厂、视图工具和通用工具集合。

## 组件边界

- 提供 `think\admin\Controller`、`Model`、`Service`、`Plugin` 等基础类型
- 提供插件优先、单应用兜底的运行时路由能力
- 提供后台 JWT 签发、解析、续签和认证中间件
- 提供队列记录、任务执行协议和执行层命令
- 提供 `Storage` 门面、契约和公共 Trait
- 根层基类已改为显式 getter 与强类型属性，不再依赖魔术属性读取插件元数据
- 不负责守护进程管理，`http/queue` 常驻运行由 `ThinkPlugsWorker` 负责
- 不负责具体存储驱动和上传授权，驱动实现由 `ThinkPlugsStorage` 负责
- 不负责数据库脚本导出、安装包生成和发布命令，这些能力已迁到 `ThinkPlugsHelper`
- 不负责具体物流查询和短信厂商实现，这类非核心能力应放到 `ThinkPlugsHelper` 或独立插件

## 内部分域

当前核心实现已经按职责收敛到这些域：

- `runtime`
  负责插件发现、前缀绑定、单应用兜底、URL 生成和运行时同步。
- `context`
  负责请求级上下文，例如当前插件、当前后台用户和当前令牌。
- `auth`
  负责后台认证、图形验证码、表单令牌和认证中间件。
- `queue`
  负责任务运行时、任务命令和任务基类。
- `command`
  负责基础命令入口，例如数据库维护、内容替换和菜单重建。
- `process`
  负责 PHP / Think / Composer 进程封装与跨平台进程查询。
- `module`
  负责模块列表、版本信息和运行时二进制路径解析。
- `node`
  负责控制器节点扫描、节点命名规范和当前节点解析。
- `menu`
  负责后台菜单树和插件菜单过滤。
- `model`
  负责基础模型、系统模型和 `ModelFactory` 等模型标准化能力。
- `system`
  负责系统配置、系统数据、系统日志、静态资源路径和 favicon 处理。
- `view`
  负责轻量视图构建工具，例如 `FormBuilder`。
- `query`
  负责查询对象标准化与查询前事件挂载，例如 `QueryFactory`。
- `extend/auth|codec|data|filesystem|http|image|model|rpc`
  负责 JWT、编码、树结构、文件、HTTP、图像、虚拟模型和 RPC 工具。
- `contract`
  负责 `QueueRuntimeInterface`、`QueueHandlerInterface`、`StorageInterface` 等基础契约。
- `helper`
  负责 Token、查询、分页和列表表格输出等控制器辅助类。

## 依赖关系

- 必需：`topthink/framework`
- 必需：`topthink/think-orm`
- 必需：`symfony/process`
- 可选上层：`zoujingli/think-plugs-admin`
- 可选上层：`zoujingli/think-plugs-worker`
- 可选上层：`zoujingli/think-plugs-storage`
- 可选上层：`zoujingli/think-plugs-helper`

## 安装组件

```bash
composer require zoujingli/think-library
```

## 服务注册

`ThinkLibrary` 基于 ThinkPHP 原生 `Service` 机制注册：

- `composer.json > extra.think.services`
- 服务类：`think\admin\Library`

插件标准元数据约定：

- `extra.xadmin.service`
- `extra.xadmin.menu`
- `extra.xadmin.migrate`

标准服务声明示例：

```json
{
  "extra": {
    "think": {
      "services": [
        "plugin\\\\demo\\\\Service"
      ]
    },
    "xadmin": {
      "service": {
        "code": "demo",
        "prefix": "demo",
        "type": "plugin",
        "name": "演示插件"
      }
    }
  }
}
```

## 运行时机制

当前运行时约定为：

- `app` 只保留一个 `single_app`
- 插件通过 URL 前缀注册访问入口
- 请求首段命中已注册前缀时切换到对应插件
- 未命中插件前缀时回退到单应用
- 动态插件切换默认关闭，需要显式开启 `app.plugin.switch.enabled`

配置示例：

```php
return [
    'single_app' => 'index',
    'plugin' => [
        'bindings' => [
            'admin' => 'admin',
            'wechat' => ['wechat', 'mp'],
        ],
        'switch' => [
            'enabled' => false,
            'query' => '_plugin',
            'header' => 'X-Plugin-App',
        ],
    ],
];
```

## 认证机制

- 后台统一使用 `Authorization: Bearer <JWT>`
- 不再使用 Session/Cookie 承载后台登录态
- `ThinkLibrary` 负责 JWT 签发、解析、续签和认证中间件
- 表单防重与一次性令牌基于缓存实现，不再依赖 Session

## 队列机制

- `ThinkLibrary` 负责 `system_queue` 记录、任务状态、进度和任务执行协议
- `xadmin:queue` 只保留执行层动作，不再管理守护进程
- 守护进程统一通过 `ThinkPlugsWorker queue` 运行

队列相关契约：

- `think\admin\contract\QueueRuntimeInterface`
- `think\admin\contract\QueueHandlerInterface`

## 命令说明

`ThinkLibrary` 当前提供这些基础命令：

- `php think xadmin:queue`
  只支持 `clean` 和 `dorun`
- `php think xadmin:database`
  数据库修复与辅助处理
- `php think xadmin:replace`
  项目内容替换工具
- `php think xadmin:sysmenu`
  系统菜单辅助命令

## 常用能力

- 控制器 CRUD 封装
- QueryHelper 列表查询、分页与 Layui.Table 输出能力
- QueryFactory / ModelFactory 标准工厂
- 全局函数与系统配置读取
- JWT Token 认证
- 存储门面与统一调用入口
- HTTP / RPC / 文件 / 编码工具
- 插件元数据读取与菜单解析

## 使用示例

控制器示例：

```php
namespace app\index\controller;

use think\admin\Controller;

class Demo extends Controller
{
    protected $dbQuery = 'SystemUser';

    public function index()
    {
        $this->_page($this->dbQuery);
    }
}
```

工具示例：

```php
use think\admin\extend\codec\CodeToolkit;
use think\admin\extend\data\ArrayTree;

$uuid = CodeToolkit::uuid();
$tree = ArrayTree::arr2tree($data);
```

## 发布与迁移

`ThinkLibrary` 自身不提供：

- `stc/config`
- `stc/database`
- 静态资源发布模板

这些能力已经拆分到独立组件：

- `ThinkPlugsStatic`
- `ThinkPlugsHelper`
- `ThinkPlugsWorker`
- `ThinkPlugsStorage`

## 命名空间约定

历史兼容包装层已经移除，当前应直接引用真实分域实现：

- 认证相关：`auth/*`
- 队列相关：`queue/*`
- 基础命令：`command/*`
- 运行时相关：`runtime/*`
- 模块相关：`module/*`
- 节点相关：`node/*`
- 系统相关：`system/*`
- 视图构建：`view/*`

## 数据依赖

`ThinkLibrary` 本身不附带安装迁移，但部分能力依赖这些系统表：

- `system_config`
- `system_data`
- `system_oplog`
- `system_queue`

这类系统表通常由 `ThinkPlugsAdmin` 的迁移脚本统一创建。

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有守护机制
- 常驻进程建议交由 `ThinkPlugsWorker` 处理

## 许可证

`ThinkLibrary` 基于 `MIT` 发布。
