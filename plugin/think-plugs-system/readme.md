# ThinkPlugsSystem for ThinkAdmin

## 版本基线
- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 组件说明
`ThinkPlugsSystem` 是后台系统壳层，负责登录、权限、菜单、用户、系统配置、日志和系统级后台页面。

它负责“系统后台本身”，不再承担存储驱动实现和队列守护进程实现。

## 主要职责
- 提供系统登录、首页、权限、菜单、用户、日志、配置等页面。
- 实现 JWT 登录态、RBAC 权限校验和系统上下文。
- 维护系统共享表与后台核心表。
- 提供系统级辅助函数与脚本变量。

## 共享表归属
- `system_auth`
- `system_auth_node`
- `system_menu`
- `system_user`
- `system_config`
- `system_data`
- `system_base`
- `system_oplog`

## 组件边界
- `ThinkPlugsStorage` 负责 `system_file`。
- `ThinkPlugsWorker` 负责 `system_queue`。
- `ThinkLibrary` 只保留基础设施、契约、运行时门面和通用 Helper。

## 主要入口
- `/system/login`
- `/system`
- `/system/config`
- `/system/menu`
- `/system/auth`
- `/system/user`
- `/system/oplog`
- `/system/queue`
- `/system/file`

## 双入口标准
- Web 页面：`/system/...`
- API 接口：`/api/system/...`

## 全局能力
- 辅助函数：`auth`、`system_user`、`system_uri`
- 系统函数：`sysconf`、`sysdata`、`sysoplog`
- 前端变量：`taSystem`、`taSystemApi`、`taApiPrefix`、`taStorage`、`taStorageApi`

## 安装
```bash
composer require zoujingli/think-plugs-system
php think xadmin:publish --migrate
```

## 平台说明
- Windows 兼容
- Linux 兼容

## 许可证
`ThinkPlugsSystem` 基于 `Apache-2.0` 发布。
