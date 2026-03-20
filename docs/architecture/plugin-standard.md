# Plugin Standard

## 当前结论

当前项目的运行时标准是：

- `app/*` 负责本地多应用
- `plugin/*` 负责插件应用
- 插件分发优先于本地应用分发
- 默认本地应用是 `app/index`

插件标准不是口头约定，而是由以下几部分共同约束：

- `think\admin\Plugin`
- `AppService`
- `plugin\helper\service\PluginMenuService`
- `PhinxExtend`
- Composer 元数据
- 架构边界测试

## 插件包标准

### 1. Composer 类型

- 插件包类型统一使用 `think-admin-plugin`

### 2. 服务发现

- `composer.json` 必须通过 `extra.think.services` 注册插件服务类
- 标准服务类名是 `plugin\\{name}\\Service`

### 3. 插件元数据

插件元数据统一由 `composer.json` 的 `extra.xadmin` 描述，至少应覆盖：

- `extra.xadmin.service`
  - `code`
  - `prefix`
  - `type`
  - `name`
- `extra.xadmin.menu`
  - `root`
  - `exists`
- `extra.xadmin.migrate`
  - `file`
  - `class`
  - `name`

这些元数据会被 `Plugin` 和发布命令用于注册、菜单安装和迁移发布。

## 目录标准

### 1. 插件根目录

每个插件根目录只保留这几个主要层级：

- `composer.json`
- `src`
- `stc`
- `tests`

### 2. `src` 根目录

`src` 根目录只允许保留最小入口文件：

- 必须有 `Service.php`
- 只有确实需要全局函数时才保留 `common.php`

不再新增 `helper.php`、`Query.php`、`Script.php` 这类职责不清的根文件。

### 3. 标准子目录

常规业务插件优先使用：

- `controller`
- `lang`
- `model`
- `service`
- `view`

按职责不同允许少量变体：

- `worker`：`command / model / service`
- `helper`：`command / service`
- `storage`：`controller / model / service / view`

除非确有必要，不再新增这些模糊目录：

- `auth`
- `runtime`
- `system`
- `support`
- `integration`
- `queue`
- `storage`
- `handle`

## 运行时标准

### 1. 路由优先级

请求分发规则是：

1. 先按插件前缀匹配
2. 再按本地应用首段匹配
3. 再按根目录全局路由的目标声明匹配
4. 再按动态插件切换匹配
5. 最后回退到默认本地应用

### 2. 标准入口

- 本地应用显式入口：`/{app}/{controller}/{action}`
- 默认本地应用可以省略首段 `index`
- 插件页面入口：`/{plugin}/...`
- 插件 API 入口：`/api/{plugin}/{controller}/{action}`

插件绝对链接、前端脚本和上传入口都应围绕这套前缀生成。

### 3. 根路由目标声明

根目录 `route/*.php` 下的全局路由，必须显式声明它要进入哪个本地应用或插件，再执行真正的控制器分发。

标准写法：

- `Route::bindApp()`
- `Route::bindPlugin()`
- `Route::appGroup()`
- `Route::pluginGroup()`

目标路由地址必须写成目标应用内部的相对控制器地址，例如：

- `dashboard/index`
- `api.upload/file`

旧式三段路由：

- `index/demo/index`
- `system/login/index`

仍保留兼容推断，但它只是迁移兜底，不再是新代码标准。

### 4. 动态切换

- `_plugin`
- `X-Plugin-App`

这两个入口只保留为调试开关，默认关闭，不能再作为正式路由依赖。

## 插件服务标准

### 1. 服务基类

- 所有插件服务统一继承 `think\admin\Plugin`

### 2. 服务职责

`Service.php` 负责：

- 插件注册
- 中间件装配
- 插件元数据
- 菜单定义

不负责堆放业务实现细节。

### 3. 元数据来源

插件元数据可以来自两处：

- `Service.php` 里的属性
- `composer.json` 的 `extra.xadmin.service`

最终会在 `Plugin` 构造阶段合并并标准化。

## 菜单与权限标准

### 1. 菜单定义入口

- 插件菜单统一由 `Service::menu()` 返回

### 2. 菜单节点约束

菜单引用的控制器节点必须真实存在，且必须声明：

- `@auth true`
- `@menu true`

否则 `PluginMenuService::assertMenus()` 会把它判定为无效菜单绑定。

### 3. 登录约束

如需登录保护，应使用：

- `@login true`

不要再靠旧后台约定或隐式目录规则推断权限。

### 4. 迁移写菜单

插件迁移安装菜单时，应优先通过：

- `PhinxExtend::writePluginMenu(Service::class, ...)`

不要在迁移里手写零散的菜单插入逻辑。

## 数据库与发布标准

### 1. 迁移脚本

每个插件只保留一份最终安装脚本：

- 位置：`plugin/<package>/stc/database/*.php`

根目录的：

- `database/migrations`

只是发布产物，不是主源文件。

### 2. 共享表归属

当前共享表归属已经固定：

- `system_*` 核心表：`think-plugs-system`
- `system_file`：`think-plugs-storage`
- `system_queue`：`think-plugs-worker`

其他插件不能再重复持有这些共享表迁移。

### 3. 静态与配置资源

插件可通过以下目录提供发布源：

- `stc/config`
- `stc/public`
- `public`
- `config`

统一通过：

- `php think xadmin:publish --force`

同步到根项目。

## 依赖标准

### 1. 单仓依赖

根仓通过 Composer `path` repository 管理本地插件包，插件之间的依赖关系也走 Composer。

### 2. 依赖图约束

当前依赖图要求：

- 本地插件依赖图必须无环
- `think-library` 处于底层
- `think-plugs-system` 可以依赖 `library / static / storage / worker`
- `storage` 和 `helper` 不能重新引入已知循环依赖

### 3. 边界测试

插件标准已经由测试守护，包括：

- 目录结构边界
- 迁移归属边界
- Composer 依赖边界
- 路由模板边界

路由与调度细节见：

- [route-dispatch-standard.md](./route-dispatch-standard.md)

## 新插件准入规则

新增插件时，默认遵循下面的最小模板：

1. 提供独立 `composer.json`
2. 注册 `extra.think.services`
3. 提供 `src/Service.php`
4. 在 `extra.xadmin.service` 中声明 `code / prefix / type / name`
5. 在 `stc/database` 中只保留一份安装脚本
6. 需要菜单时，通过 `Service::menu()` 和控制器注释节点配套声明
7. 静态资源与配置资源放到 `stc/*`，通过发布命令下发
8. 目录结构必须落入现有边界测试允许范围

## 当前不再推荐的旧做法

以下做法都不再是当前插件标准：

- 把业务继续堆在 `app/*`
- 插件没有 `Service.php`
- 通过旧后台路径或旧静态资源路径访问插件
- 在根目录长期维护多份迁移脚本副本
- 菜单只写数据库，不写 `Service::menu()`
- 菜单节点没有 `@auth true` 和 `@menu true`
- 新增模糊职责目录来承载服务代码

## 后续收口方向

当前插件标准已经成立，后续重点不是“再定义一套规则”，而是继续让源码完全贴齐这套规则：

- 清理残留的旧目录习惯
- 清理旧路由和旧静态资源引用
- 持续把发布产物和源目录分离清楚
- 继续让测试守护目录、依赖和迁移边界
