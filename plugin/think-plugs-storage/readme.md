# ThinkPlugsStorage for ThinkAdmin

**ThinkPlugsStorage** 是 ThinkAdmin 8 / ThinkPHP 8.1 的标准存储管理组件，负责统一管理文件存储驱动、驱动元数据、标准配置结构和上传授权协议。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsStorage` 是统一文件存储中心，负责驱动注册、标准配置、上传授权、上传接口和存储配置页面。
- 组件把本地、Alist、Qiniu、Upyun、AliOSS、TxCos 等驱动统一成一套配置和授权协议。
- 后台用户通过 `storage/config/*` 管理存储，前端上传脚本通过 `storage/api.upload/*` 获取授权与上传状态。
- 组件不再让 `Admin` 承载具体存储实现，`Admin` 只保留入口摘要。

## 架构说明

- 配置层：`StorageConfig` 统一读取全局配置和驱动参数。
- 驱动层：`src/storage/*` 对每种存储实现上传、访问地址、区域和授权差异。
- 接入层：`src/controller/Config.php` 与 `src/controller/api/Upload.php` 提供配置页和上传接口。
- 门面层：`ThinkLibrary` 里的 `Storage` 门面只做统一调用入口，真正驱动实现由本组件提供。

## 组件边界

- `ThinkLibrary` 保留 `Storage` 门面、契约与公共 Trait
- `ThinkPlugsStorage` 负责具体驱动实现、驱动元数据、配置页面和上传入口
- `ThinkPlugsAdmin` 仅保留系统配置摘要与入口跳转
- 组件不再依赖 Composer 安装阶段自动复制目录

## 依赖关系

- 必需：`zoujingli/think-library`
- 推荐宿主：`zoujingli/think-plugs-admin`
- 运行扩展：`ext-curl`、`ext-json`

## 安装组件

```bash
composer require zoujingli/think-plugs-storage

# 首次发布配置模板
php think xadmin:publish
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-storage
```

组件卸载不会自动删除已有系统配置项，也不会删除已上传文件。

## 发布内容

发布后会生成或更新：

- `config/storage.php`
- 组件内 `stc/config/storage.php` 对应的标准配置模板

## 后台入口

当前版本的后台管理页由 `ThinkPlugsStorage` 自己承载，标准入口为：

- `storage/config/index`
- `storage/config/storage?type=<driver>`
- `storage/api.upload/index`

说明：

- `ThinkPlugsAdmin` 中只保留“进入存储中心”的入口卡片
- `admin/config/storage` 与 `admin/api.upload/*` 仅作为兼容桥接，不再承载具体实现
- 驱动枚举、模板名称、区域列表、上传授权都由 `ThinkPlugsStorage` 提供

## 标准配置

配置文件模板为 `config/storage.php`。

全局参数：

- `storage.driver`
- `storage.naming`
- `storage.link`
- `storage.allowed_exts`

驱动参数统一按 `storage.<driver>.<field>` 组织，例如：

- `storage.local.protocol`
- `storage.local.domain`
- `storage.alist.domain`
- `storage.alist.path`
- `storage.qiniu.region`
- `storage.qiniu.bucket`
- `storage.upyun.bucket`
- `storage.txcos.region`
- `storage.alioss.region`

## 驱动能力

当前内置驱动：

- Local
- Alist
- Qiniu
- Upyun
- Txcos
- Alioss

核心类位置：

- 驱动实现：`src/storage`
- 配置标准化：`src/StorageConfig.php`
- 驱动管理器：`src/StorageManager.php`
- 上传授权适配：`src/support/StorageAuthorize.php`
- 元数据注册：`stc/config/storage.php`

## 运行时接口

常用标准入口：

- `Storage::types()`
- `Storage::regions($driver)`
- `Storage::template($driver)`
- `Storage::authorize($driver, $key, ...)`
- `storage/api.upload/state`
- `storage/api.upload/file`
- `storage/api.upload/done`

## 兼容规则

- 新版本后台只写标准键名
- 运行时仍会回退读取旧键名
- 旧配置可平滑迁移到新结构

## 插件数据

本组件不创建独立业务表。

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有命令

## 许可证

`ThinkPlugsStorage` 基于 `MIT` 发布。
