# Plugin-First Refactor

## 相关文档
- [插件标准](./plugin-standard.md)
- [路由调度标准](./route-dispatch-standard.md)
- [软删除标准](./soft-delete-standard.md)

## 目标
- `plugin/*` 作为主业务承载目录。
- `app/*` 仅保留本地多应用和过渡代码。
- 页面入口统一为 `/{plugin}/...`。
- 接口入口统一为 `/api/{plugin}/{controller}/{action}`。
- 路由分发优先命中插件，再命中本地应用，最后回退到默认本地应用。
- `_plugin` 与 `X-Plugin-App` 只保留为调试开关，默认关闭。

## 组件边界

### ThinkLibrary
- 提供 `Controller`、`Model`、`Plugin`、`Command` 等基础类型。
- 负责插件发现、元数据读取、URL 构建、运行时上下文与路由适配。
- 负责通用会话、JWT、RPC、Storage 门面与标准契约。
- 不承载后台壳层、守护进程与发布导出能力。

### ThinkPlugsSystem
- 负责后台登录、首页、权限菜单、用户、系统配置、日志等后台壳层能力。
- 持有 `system_auth`、`system_auth_node`、`system_menu`、`system_user`。
- 持有 `system_config`、`system_data`、`system_base`、`system_oplog`。

### ThinkPlugsWorker
- 负责 Workerman 常驻进程、HTTP 托管、队列调度与运行控制。
- 提供 `xadmin:worker` 统一入口。
- 持有 `system_queue`。

### ThinkPlugsStorage
- 负责驱动注册、配置协议、上传授权、上传接口与文件管理。
- 提供 `storage/config/*`、`storage/file/*` 与 `/api/storage/upload/*`。
- 持有 `system_file`。

### ThinkPlugsHelper
- 负责迁移导出、安装包生成、菜单校验、发布命令和开发辅助工具。
- 提供 `xadmin:publish`、`xadmin:package`、`xadmin:helper:*`。

### 业务插件
- `ThinkPlugsWechatClient`、`ThinkPlugsWechatService`、`ThinkPlugsPayment`、`ThinkPlugsWemall` 等只承载自己的业务模型、控制器和菜单。
- 不再把公共基础能力回写到 `app/*` 或 `extend/*`。

## 当前状态
- 插件元数据统一收敛到 `composer.json > extra.xadmin`。
- `ThinkLibrary` 已支持插件优先分发、本地应用兼容和默认本地应用回退。
- `ThinkPlugsSystem`、`ThinkPlugsStorage`、`ThinkPlugsWorker` 的共享表已明确归属。
- 旧的根级门面与历史兼容目录已逐步收缩到最小集合。
- 插件中心、存储中心、系统后台都已切换到插件化入口。

## 当前约定

### 插件注册
- 每个插件统一使用 `src/Service.php` 作为服务入口。
- 服务类统一通过 `composer.json > extra.think.services` 注册。
- 插件编码、前缀、菜单、迁移、说明文档统一写在 `extra.xadmin`。

### 运行时
- 页面链接优先使用 `sysuri()`。
- 标准接口链接优先使用 `apiuri()`。
- 根目录全局路由优先使用 `Route::bindApp()`、`Route::bindPlugin()`、`Route::appGroup()`、`Route::pluginGroup()`。
- 新代码不再依赖旧式三段路由推断目标应用。

### 共享表归属
- `system_auth`、`system_auth_node`、`system_menu`、`system_user` 归 `ThinkPlugsSystem`。
- `system_config`、`system_data`、`system_base`、`system_oplog` 归 `ThinkPlugsSystem`。
- `system_file` 归 `ThinkPlugsStorage`。
- `system_queue` 归 `ThinkPlugsWorker`。

### 前端脚本变量
- 系统脚本统一下发 `taSystem`、`taSystemApi`、`taStorage`、`taStorageApi`、`taApiPrefix`。
- 插件模板、静态脚本、上传脚本和公开预览页都优先消费这组变量。

## 后续收尾
1. 继续清理历史文档和模板中的旧路由示例。
2. 持续补齐跨平台回归，覆盖 Windows 与 Linux。
3. 新增插件时严格走 `plugin-standard.md` 中的准入规则。
