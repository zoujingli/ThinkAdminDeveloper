# ThinkPlugsSystem for ThinkAdmin

**ThinkPlugsSystem** 是 ThinkAdmin 8 / ThinkPHP 8.1 的系统后台与共享系统能力组件，统一承载后台登录、权限菜单、系统用户，以及 `system_*` 共享数据表与服务。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 组件职责

- 后台壳层：登录、首页、系统配置、任务、日志、菜单、权限、用户、文件等后台页面
- 认证权限：JWT 登录态、RBAC 权限校验、系统上下文实现
- 系统共享表：`system_base`、`system_config`、`system_data`、`system_oplog`
- 后台核心表：`system_auth`、`system_auth_node`、`system_menu`、`system_user`

## 组件边界

- `ThinkLibrary` 只保留基础设施、契约、运行时门面和通用 Helper
- `ThinkPlugsSystem` 负责系统后台入口和所有 `system_*` 核心模型/控制器
- `ThinkPlugsStorage` 负责 `system_file`
- `ThinkPlugsWorker` 负责 `system_queue`

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 必需：`zoujingli/think-plugs-static`
- 必需：`zoujingli/think-plugs-storage`
- 必需：`zoujingli/think-plugs-worker`

## 安装组件

```bash
composer require zoujingli/think-plugs-system

php think xadmin:publish --migrate
```

## 主要迁移

- `stc/database/20241010000001_install_system20241010.php`

## 主要入口

- `system/login/index`
- `system/index/index`
- `system/config/index`
- `system/queue/index`
- `system/oplog/index`
- `system/base/index`
- `system/file/index`
- `system/menu/index`
- `system/auth/index`
- `system/user/index`

## 双入口标准

`ThinkPlugsSystem` 同时承载后台页面入口和系统 API 入口：

- Web 页面：`/system/...`
- API 接口：`/api/system/...`

典型示例：

- `/system/index/index`
- `/system/config/index`
- `/system/queue/index`
- `/api/system/plugs/script`
- `/api/system/queue/status`
- `/api/system/system/config`

前端公共脚本会下发：

- `taSystem`
- `taSystemApi`
- `taApiPrefix`
- `taStorage`
- `taStorageApi`

## 全局能力

- 辅助函数：`auth`、`system_user`、`system_uri`
- 系统函数：`sysconf`、`sysdata`、`sysoplog`

## 平台说明

- Windows 兼容
- Linux 兼容

## 许可证

`ThinkPlugsSystem` 基于 `MIT` 发布。
