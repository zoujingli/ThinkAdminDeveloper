# ThinkLibrary for ThinkAdmin

## 版本基线
- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 组件说明
`ThinkLibrary` 是整个仓库的基础核心层，负责提供统一的控制器基类、模型基类、插件基类、命令基类，以及插件运行时、路由适配、上下文、会话、JWT、RPC、Storage 门面等框架级公共能力。

它只负责“通用基础设施”，不再承载后台壳层、守护进程、存储驱动、发布导出、安装包生成等上层能力。

## 主要职责
- 提供 `Controller`、`Model`、`Plugin`、`Command` 等基础类型。
- 负责插件发现、元数据读取、URL 构建和运行时上下文。
- 提供插件优先、本地多应用兼容、默认本地应用回退的路由能力。
- 提供会话、JWT、RPC、Storage 门面与标准契约。
- 提供查询辅助、控制器辅助和常用扩展工具。

## 组件边界
- 不负责后台登录、菜单、权限和系统页面，这些由 `ThinkPlugsSystem` 提供。
- 不负责常驻进程和队列调度，这些由 `ThinkPlugsWorker` 提供。
- 不负责具体存储驱动和上传授权，这些由 `ThinkPlugsStorage` 提供。
- 不负责发布命令、迁移导出和安装包生成，这些由 `ThinkPlugsHelper` 提供。

## 内部分域
- `service`
- `runtime`
- `route`
- `middleware`
- `model`
- `helper`
- `extend`
- `contract`

## 安装
```bash
composer require zoujingli/think-library
```

## 运行时约定
- 本地应用页面入口：`/{app}/{controller}/{action}`
- 插件页面入口：`/{plugin}/...`
- 插件接口入口：`/api/{plugin}/{controller}/{action}`
- 页面 URL 优先使用 `sysuri()`
- 标准接口 URL 优先使用 `apiuri()`

## 常用能力
- `sysuri()`：生成后台页面地址。
- `apiuri()`：生成标准插件接口地址。
- `Storage::*`：统一存储门面。
- `AppService::*`：插件发现、元数据读取与菜单解析。
- `RequestContext`：当前插件、前缀、登录态与请求上下文。

## 共享表依赖
`ThinkLibrary` 自身不创建业务表，但会依赖这些共享表：
- `system_config`、`system_data`、`system_base`、`system_oplog` 由 `ThinkPlugsSystem` 提供。
- `system_file` 由 `ThinkPlugsStorage` 提供。
- `system_queue` 由 `ThinkPlugsWorker` 提供。

## 相关组件
- `zoujingli/think-plugs-system`
- `zoujingli/think-plugs-storage`
- `zoujingli/think-plugs-worker`
- `zoujingli/think-plugs-helper`

## 许可证
`ThinkLibrary` 基于 `MIT` 发布。
