# ThinkPlugsAdmin for ThinkAdmin

**ThinkPlugsAdmin** 是 ThinkAdmin 8 / ThinkPHP 8.1 的后台管理组件，负责系统配置、菜单权限、后台用户、文件管理、日志、任务状态和后台登录入口。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsAdmin` 是系统后台壳层，负责后台登录、导航布局、菜单权限、角色授权、后台用户、系统配置、文件记录和操作日志。
- 组件只承载“后台管理能力”，不再负责存储驱动、队列守护和插件运行时，这些能力已经拆给 `Storage`、`Worker`、`Center`。
- 当前后台认证统一为 `Authorization: Bearer <JWT>`，不再依赖 Session 登录态。
- 所有业务插件都通过菜单节点接入这个后台壳，但业务实现仍保留在各自插件内部。

## 架构说明

- 接入层：`src/controller/*` 与 `src/view/*` 提供后台页面、接口和统一 LayUI 视图框架。
- 授权层：依赖 `ThinkLibrary` 的 `auth/menu/node/system` 分域完成菜单树、角色树、用户授权和节点校验。
- 协同层：队列页调用 `ThinkPlugsWorker`，存储入口跳转 `ThinkPlugsStorage`，插件中心入口对接 `ThinkPlugsCenter`。
- 数据层：`system_menu`、`system_auth`、`system_user`、`system_file`、`system_oplog` 等后台公共表由本组件维护。

## 组件边界

- 插件编码：`admin`
- 访问前缀：`admin`
- 负责后台界面、权限体系、日志、文件记录和任务管理页
- 后台认证统一为 `Authorization: Bearer <JWT>`
- 队列守护进程由 `ThinkPlugsWorker` 负责
- 存储驱动、上传授权和存储配置页由 `ThinkPlugsStorage` 负责

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 推荐运行时：`zoujingli/think-plugs-worker`
- 推荐存储：`zoujingli/think-plugs-storage`

## 安装组件

```bash
composer require zoujingli/think-plugs-admin

php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-admin
```

组件卸载不会自动删除已执行的迁移和系统业务表。

## 后台入口

标准入口：

- `admin/login/index`
- `admin/index/index`

主要管理节点：

- `admin/config/index`
- `admin/queue/index`
- `admin/oplog/index`
- `admin/base/index`
- `admin/file/index`
- `admin/menu/index`
- `admin/auth/index`
- `admin/user/index`

## 命令说明

本组件不直接管理常驻进程，后台任务相关命令统一使用：

- `php think xadmin:worker start queue -d`
- `php think xadmin:worker status queue`
- `php think xadmin:worker stop queue`

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000001_install_admin20241010.php`

迁移内容包括：

- 系统菜单
- 系统权限
- 系统用户
- 系统配置
- 系统任务
- 系统日志
- 系统文件
- 系统字典

## 业务能力

- 系统参数配置
- 菜单管理
- 权限角色管理
- 后台用户管理
- 数据字典管理
- 文件记录管理
- 队列任务管理
- 操作日志查询

## 运行说明

- 插件源码直接从插件包加载，不再复制到 `app/admin`
- 后台角色、菜单、用户、字典都已支持按插件维度分组和筛选
- `ThinkPlugsAdmin` 只消费 `ThinkPlugsStorage` 和 `ThinkPlugsWorker` 的能力，不再承载这两类底层实现

## 插件数据

- 系统权限：`system_auth`
- 权限节点：`system_auth_node`
- 数据字典：`system_base`
- 系统配置：`system_config`
- 扩展数据：`system_data`
- 文件记录：`system_file`
- 系统菜单：`system_menu`
- 操作日志：`system_oplog`
- 队列记录：`system_queue`
- 后台用户：`system_user`

## 平台说明

- Windows 兼容
- Linux 兼容
- 后台守护进程建议配合 `ThinkPlugsWorker`

## 许可证

`ThinkPlugsAdmin` 基于 `MIT` 发布。
