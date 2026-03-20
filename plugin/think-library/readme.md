# ThinkLibrary for ThinkAdmin

**ThinkLibrary** 是 ThinkAdmin 8 / ThinkPHP 8.1 的核心基础库，负责控制器基类、模型基类、插件元数据解析、通用门面、上下文、路由适配和基础工具。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkLibrary` 是整个仓库的核心基础库，负责定义统一的控制器基类、模型基类、插件基类、命令基类和框架级公共能力。
- 它承载的是“框架级通用能力”，包括插件优先路由、上下文门面、模型与查询标准化、存储/会话/JWT/RPC 门面和通用工具。
- 组件聚焦于框架级通用能力，不再承载存储驱动、守护进程、发布导出、物流短信等非核心实现；但跨组件通用的安全校验、通信协议和图标构建能力仍可留在基础库。
- 组件目标是作为所有插件共享的核心层，而不是再继续堆放具体业务逻辑。

## 架构说明

- 基础入口层：`Controller`、`Model`、`Plugin`、`Command` 为所有插件提供统一基类和调用约定。
- 服务层：`service` 负责插件注册查询、运行时门面、存储门面、会话和标准能力适配。
- 运行时层：`runtime` 负责请求上下文、系统上下文和插件命中结果传递。
- 路由层：`route` 负责 `Route` / `Url` 适配和插件前缀 URL 生成。
- 中间件层：`middleware` 负责多入口切换与请求改写。
- 支撑层：`model / helper / extend / contract` 负责模型与查询标准化、控制器辅助能力、基础工具和标准契约。

## 组件边界

- 提供 `think\admin\Controller`、`Model`、`Plugin`、`Command` 等基础类型
- 提供插件优先、本地多应用兼容、默认本地应用回退的运行时路由能力
- 提供基础 JWT、会话、RPC、运行控制、存储等门面能力
- 提供 `Storage` 门面、契约和公共 Trait
- 基础库只定义框架级标准与通用实现，具体业务能力由其他插件承载
- 根层基类已改为显式 getter 与强类型属性，不再依赖魔术属性读取插件元数据
- 不负责守护进程管理，`http/queue` 常驻运行由 `ThinkPlugsWorker` 负责
- 不负责具体存储驱动和上传授权，驱动实现由 `ThinkPlugsStorage` 负责
- 不负责数据库脚本导出、安装包生成和发布命令，这些能力已迁到 `ThinkPlugsHelper`
- 不负责具体物流查询和短信厂商实现，这类非核心能力应放到 `ThinkPlugsHelper` 或独立插件

## 内部分域

当前核心实现已经按职责收敛到这些域：

- `service`
  负责 `AppService / NodeService / CacheSession / JwtToken / RuntimeService / QueueService` 等基础门面能力。
- `runtime`
  只保留 `SystemContext / NullSystemContext / RequestContext / RequestTokenService` 这类上下文对象。
- `route`
  负责 `Route / Url` 适配。
- `middleware`
  负责 `MultAccess` 多应用切换中间件。
- `model`
  负责基础模型、`ModelFactory` 和 `QueryFactory` 等模型标准化能力。
- `extend`
  只保留纯工具，例如 `CodeToolkit`、`ArrayTree`、`FileTools`、`HttpClient`。
- `contract`
  负责 `StorageInterface`、队列运行时与处理器等基础契约，是其他组件实现标准的入口。
- `helper`
  负责表单构建、查询、分页和列表表格输出等控制器辅助类。
- `ImageSliderVerify / FaviconBuilder / JsonRpcHttpClient / JsonRpcHttpServer` 作为跨组件公共能力统一保留在 `service`

## 依赖关系

- 必需：`topthink/framework`
- 必需：`topthink/think-orm`
- 可选上层：`zoujingli/think-plugs-system`
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

- `app/*` 使用本地多应用目录结构，默认本地应用为 `app/index`
- 插件通过 URL 前缀注册访问入口
- 本地应用显式入口使用 `/{app}/{controller}/{action}`
- 默认本地应用生成 URL 时可以省略首段 `index`
- 插件页面入口统一使用 `/{plugin}/...`
- 插件 API 接口入口统一使用 `/api/{plugin}/{controller}/{action}`
- API 入口会自动映射到现有 `controller/api/*`，例如 `/api/storage/upload/file` -> `controller/api/Upload::file`
- 请求调度优先级为：插件前缀 -> 本地应用前缀 -> 根路由目标声明 -> 动态插件切换 -> 默认本地应用
- 根目录 `route/*.php` 的全局路由应通过 `Route::bindApp()`、`Route::bindPlugin()`、`Route::appGroup()`、`Route::pluginGroup()` 显式声明目标
- 根路由里 `$route` 必须写成目标应用内部相对地址，例如 `dashboard/index`、`api.upload/file`
- 旧式三段路由 `system/login/index`、`index/demo/index` 仍保留目标推断兼容，但新代码不再推荐
- 动态插件切换默认关闭，需要显式开启 `app.plugin.switch.enabled`
- 统一 URL 生成规则为：页面用 `sysuri()`，标准接口用 `apiuri()`

配置示例：

```php
// config/route.php
return [
    'default_app' => 'index',
];
```

```php
// config/app.php
return [
    // single_app 仅保留兼容回退，建议优先使用 route.default_app
    'single_app' => 'index',
    'plugin' => [
        'bindings' => [
            'system' => 'system',
            'wechat' => ['wechat', 'mp'],
        ],
        'api_prefix' => 'api',
        'switch' => [
            'enabled' => false,
            'query' => '_plugin',
            'header' => 'X-Plugin-App',
        ],
    ],
];
```

标准示例：

- 本地应用：`/index/index/index`
- 本地应用：`/member/center/index`
- 页面：`/system/config/index`
- 页面：`/storage/config/index`
- 接口：`/api/system/plugs/script`
- 接口：`/api/storage/upload/file`
- 接口：`/api/wechat/view/news?id=1`

根路由目标声明示例：

```php
use think\admin\runtime\RequestContext;
use think\facade\Route;

Route::bindApp('portal', 'home/index', 'index');
Route::bindPlugin('open-upload', 'api.upload/file', 'system', RequestContext::ENTRY_API);

Route::appGroup('member', function () {
    Route::get('member-center', 'center/index');
});

Route::pluginGroup('system', function () {
    Route::get('quick-upload', 'api.upload/file');
}, RequestContext::ENTRY_API);
```

## 前端脚本变量

系统后台脚本统一注入这些变量，供前端模块按标准入口加载：

- `taApiPrefix`：当前 API 前缀，默认 `api`
- `taSystem`：系统后台 Web 根地址，例如 `/system`
- `taSystemApi`：系统后台 API 根地址，例如 `/api/system`
- `taStorage`：存储中心 Web 根地址，例如 `/storage`
- `taStorageApi`：存储中心 API 根地址，例如 `/api/storage`
- `taTokenHeader`：认证请求头名称
- `taTokenScheme`：认证方案，默认 `Bearer`

## 认证机制

- 后台统一使用 `Authorization: Bearer <JWT>`
- 后台登录态不再由 Session 或认证 Cookie 承载
- 后台壳页首跳允许使用一次性 `access_key` 引导，随后统一落到本地 `localStorage + Authorization`
- `ThinkLibrary` 负责基础 JWT 与请求令牌解析门面
- 令牌会话统一使用 `CacheSession` / `tsession()`，并按 Token SID 隔离
- 表单防重与一次性令牌基于缓存实现，不再依赖 Session
- 统一规范以 [../../docs/architecture/auth-token-session.md](../../docs/architecture/auth-token-session.md) 为准

统一请求头规范：

- 认证令牌统一使用 `Authorization: Bearer <token>`
- 设备类接口可附加 `X-Device-Code`、`X-Device-Type`
- 不再兼容 `Api-Token`、`Api-Type`、`Api-Code`

## 队列机制

- `ThinkLibrary` 只保留队列标准定义与调用门面，不承载队列实现
- 标准契约包括 `QueueRuntimeInterface`、`QueueHandlerInterface`、`QueueManagerInterface`
- 标准入口为 `think\admin\service\QueueService`
- 队列记录、任务协议实现、执行器、常驻调度和运维命令统一由 `ThinkPlugsWorker` 提供

## 命令说明

`ThinkLibrary` 不再注册运维命令；队列清理与手动执行入口由 `ThinkPlugsWorker` 提供。

## 常用能力

- 控制器 CRUD 封装
- QueryHelper 列表查询、分页与 Layui.Table 输出能力
- QueryFactory / ModelFactory 标准工厂
- 全局函数与系统配置读取
- JWT / 会话 / 请求令牌门面
- 存储门面与统一调用入口
- HTTP / RPC / 文件 / 编码工具
- 插件元数据读取与菜单解析
- `apiuri()` 统一生成 `/api/{plugin}/...` 风格地址

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
use think\admin\extend\CodeToolkit;
use think\admin\extend\ArrayTree;
use think\admin\service\JwtToken;

$uuid = CodeToolkit::uuid();
$tree = ArrayTree::arr2tree($data);
$token = JwtToken::token(['uid' => 1]);
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

当前直接使用以下标准命名空间：

- 基础工具：`extend/*`
- 门面与协议能力：`service/*`
- 请求 / 系统上下文：`runtime/*`
- 路由适配：`route/*`
- 中间件：`middleware/*`
- 模型与查询：`model/*`
- 控制器辅助：`helper/*`

## 数据依赖

`ThinkLibrary` 本身不附带安装迁移，但部分能力依赖这些系统表：

- `system_config`
- `system_data`
- `system_oplog`
- `system_queue`

这些表已分别由上层插件提供：

- `system_config`、`system_data`、`system_oplog` 由 `ThinkPlugsSystem` 创建
- `system_queue` 由 `ThinkPlugsWorker` 创建

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有守护机制
- 常驻进程建议交由 `ThinkPlugsWorker` 处理

## 许可证

`ThinkLibrary` 基于 `MIT` 发布。
