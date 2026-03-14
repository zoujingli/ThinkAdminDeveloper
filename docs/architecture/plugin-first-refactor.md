# Plugin-First Refactor

## 相关文档

- [插件标准](./plugin-standard.md)
- [软删除标准](./soft-delete-standard.md)

## 目标

项目已经切换到 ThinkPHP 8.1 的插件优先架构，当前运行原则为：

- `plugin/*` 是主模块形态
- `app/*` 不再作为默认多应用集合
- `app` 只保留单应用兜底入口
- 当 `/x/...` 命中已注册插件前缀时进入插件
- Web 页面标准入口为 `/{plugin}/...`
- API 标准入口为 `/api/{plugin}/{controller}/{action}`
- 当 `/x/...` 未命中插件时回退到单应用控制器解析
- `_plugin / X-Plugin-App` 只保留为可选调试开关，默认关闭

## 组件边界

当前总图见：[plugin-boundaries.md](/Users/anyon/Runtime/ThinkAdminDeveloper/docs/architecture/plugin-boundaries.md)

### ThinkLibrary

负责核心运行时与基础设施：

- 插件发现、插件元数据解析、URL 前缀绑定、单应用兜底
- `Controller`、`Model`、`QueryHelper`、`Storage` 门面和公共工具
- JWT 认证、任务协议契约、基础命令
- 不再承载后台守护进程和数据库脚本导出

### ThinkPlugsSystem

负责系统后台与共享系统能力：

- `system_auth / system_auth_node / system_menu / system_user`
- `system_config / system_data / system_base / system_oplog`
- 后台登录、权限菜单、后台用户、系统配置、日志、文件、队列入口
- 共享系统配置、扩展数据、字典与日志服务

### ThinkPlugsWorker

负责标准运行时：

- Workerman `http` 托管
- Workerman `queue` 调度
- 双平台进程状态查询与控制
- `xadmin:worker` 统一入口

### ThinkPlugsHelper

负责开发与安装期工具：

- Model 注释生成
- 数据库结构导出
- 插件迁移打包
- `xadmin:publish`
- `xadmin:package`

### ThinkPlugsStorage

负责标准存储中心：

- 驱动注册中心与统一配置协议
- 本地、OSS、COS、Qiniu、Upyun、Alist 等驱动适配
- 上传授权与上传接口
- 存储后台配置页和入口

### ThinkPlugsWechatClient

负责公众号标准平台：

- 微信配置、菜单、粉丝、素材、关键字、支付与退款
- 不再依赖 `app/wechat`

### ThinkPlugsWechatService

负责公众号开放平台：

- 开放平台配置、第三方授权接入、远程 JSON-RPC 调度
- 作为 `ThinkPlugsWechatClient` 的开放平台服务端

## 当前已落地

- 插件元数据统一收敛到 `extra.think.services / extra.xadmin.service / extra.xadmin.menu / extra.xadmin.migrate`
- `MultAccess` 改为插件前缀优先，再回退单应用
- 本地 `app/*` 不再作为多应用集合，只保留单应用入口
- `ThinkPlugsSystem` 与 `ThinkPlugsWechatClient` 改为插件直载
- `ThinkPlugsStorage` 已独立成完整插件，包含驱动、配置页和上传入口
- 后台认证已切换为纯 JWT / Token
- 前端已统一为 `LayUI + $.module.use(...)`
- `RequireJS` 已彻底移除
- `extend` 根目录旧门面已清理，内部统一改用新分域实现

## 当前约定

### 插件注册

- 插件服务统一命名为 `Service`
- 插件前缀由 `Service` 属性和 `app.plugin.bindings` 共同决定
- 插件菜单必须由 `Service::menu()` 和控制器 `@auth + @menu` 同步声明

### 运行时

- `ThinkLibrary` 只保留运行时绑定和执行协议
- `ThinkPlugsSystem` 负责系统后台壳层、认证权限和 `system_*` 核心表
- `ThinkPlugsWorker` 负责守护进程生命周期
- `ThinkPlugsHelper` 负责安装、发布和迁移导出
- 新 API 入口直接映射现有 `controller/api/*`，旧的 `plugin/api.xxx/...` 仍保留兼容

### 双入口标准

- 页面入口统一使用 `/{plugin}/...`
- 接口入口统一使用 `/api/{plugin}/{controller}/{action}`
- 页面链接优先使用 `sysuri()`
- 接口链接优先使用 `apiuri()`
- 系统后台脚本会统一下发 `taSystem / taSystemApi / taStorage / taStorageApi / taApiPrefix`
- 插件模板、静态脚本、上传脚本和公开预览页都应优先消费这组变量或 `apiuri()`

示例：

- `/system/index/index`
- `/storage/config/index`
- `/api/system/plugs/script`
- `/api/storage/upload/index`
- `/api/wechat/view/text`
- `/api/plugin-wechat-service/client/jsonrpc`

### 数据库脚本

- 每个插件只保留一份最终安装脚本
- 根目录 `database/migrations` 视为发布产物
- 主来源以插件 `stc/database` 为准
- `system_base / system_config / system_data / system_oplog` 归 `ThinkPlugsSystem`
- `system_file` 归 `ThinkPlugsStorage`
- `system_queue` 归 `ThinkPlugsWorker`
- `system_auth / system_auth_node / system_menu / system_user` 归 `ThinkPlugsSystem`

## 后续收尾

1. 继续统一剩余文档模板和模块说明。
2. 为 `System / Storage / Worker` 补一轮完整安装升级回归。
3. 按 Win/Linux 再做一轮完整运行回归。
