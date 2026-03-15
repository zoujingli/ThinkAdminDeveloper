# Route Dispatch Standard

## 目标

当前项目的最佳实践不是让根路由“先命中、后猜应用”，而是让根路由先声明目标应用或插件，再进入真实调度。

这样可以在分发前就绑定正确的：

- 应用目录
- 命名空间
- `view_path`
- 应用级配置
- 中间件
- 语言包
- 插件入口类型（`web` / `api`）

否则全局路由虽然能命中，但很容易出现这些问题：

- 模板从错误目录加载
- 控制器命名空间错误
- 插件 API 被当成普通页面入口
- 本地应用配置和中间件没有生效

## 当前标准

### 1. 本地与插件模型

- `app/*`：本地多应用
- `plugin/*`：插件应用
- 默认本地应用：`index`

### 2. 分发优先级

`MultAccess` 当前的标准优先级是：

1. 显式插件前缀
2. 显式本地应用前缀
3. 根路由声明的目标应用/插件
4. 动态插件切换（兼容入口，默认关闭）
5. 默认本地应用

### 3. 标准入口

- 本地应用显式入口：`/{app}/{controller}/{action}`
- 默认本地应用：生成 URL 时可省略 `index`
- 插件页面入口：`/{plugin}/{controller}/{action}`
- 插件 API 入口：`/api/{plugin}/{controller}/{action}`

## 路由注册标准

### 1. 单条路由

```php
use think\admin\runtime\RequestContext;
use think\facade\Route;

Route::bindApp('portal', 'home/index', 'index');
Route::bindPlugin('open-upload', 'api.upload/file', 'system', RequestContext::ENTRY_API);
```

约束：

- `portal`、`open-upload` 是根路由规则
- `home/index`、`api.upload/file` 是目标应用内部相对路径
- 不再推荐把目标地址写成带应用前缀的旧三段式

### 2. 分组路由

```php
use think\admin\runtime\RequestContext;
use think\facade\Route;

Route::appGroup('member', function () {
    Route::get('member-center', 'center/index');
});

Route::pluginGroup('system', function () {
    Route::get('quick-upload', 'api.upload/file');
}, RequestContext::ENTRY_API);
```

这适合一批根路由都指向同一个本地应用或插件的场景。

### 3. 兼容旧路由

以下旧写法仍会尝试做目标推断：

```php
Route::rule('legacy-demo', 'index/demo/index');
Route::rule('legacy-login', 'system/login/index');
```

兼容规则只用于过渡：

- 如果第一段能识别为插件编码，优先按插件处理
- 否则如果第一段能识别为本地应用编码，按本地应用处理

新代码不要继续依赖这种隐式推断。

## 为什么这样最好

这套机制的关键价值是“先选上下文，再执行路由”。

只要目标应用/插件已经在分发前绑定完成，后面的控制器解析、模板加载、配置加载和中间件链都会落在正确目录，不需要再用各种运行时补丁去修视图路径或命名空间。

这比单纯在 `MultAccess` 里追加更多 if/else 更稳，因为：

- 路由语义在注册阶段就是显式的
- 全局路由不会再偷偷依赖当前默认应用
- 本地多应用和插件机制可以同时存在
- 迁移旧路由时有明确新标准和兼容兜底

## 推荐约束

- 根目录 `route/*.php` 只写跨应用或跨插件的全局路由
- 应用内部路由放各自 `app/{name}/route` 或 `plugin/*/src/route`
- 根路由一律使用 `bindApp` / `bindPlugin` / `appGroup` / `pluginGroup`
- 如果必须保留旧三段式，后续要逐步迁成显式目标声明
