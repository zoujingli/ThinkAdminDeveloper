# ThinkPlugsStorage for ThinkAdmin

## 版本基线
- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 组件说明
`ThinkPlugsStorage` 是统一文件存储中心，负责存储驱动注册、标准配置、上传授权、上传接口和文件管理。

组件把本地、Alist、Qiniu、Upyun、AliOSS、TxCos 等驱动统一为一套配置与调用协议，并将文件管理入口统一收敛到存储中心。

## 主要职责
- 提供 `storage/config/*` 存储配置页面。
- 提供 `storage/file/*` 文件管理页面。
- 提供 `/api/storage/upload/*` 上传授权与上传接口。
- 负责驱动配置模板、驱动区域枚举和上传授权适配。

## 组件边界
- `ThinkLibrary` 保留 `Storage` 门面、契约和公共 Trait。
- `ThinkPlugsStorage` 负责具体驱动实现、配置页面和上传入口。
- `ThinkPlugsSystem` 只保留系统后台入口和兼容跳转。

## 主要入口
- `storage/config/index`
- `storage/file/index`
- `/api/storage/upload/index`
- `/api/storage/upload/state`
- `/api/storage/upload/file`
- `/api/storage/upload/done`

## 安装
```bash
composer require zoujingli/think-plugs-storage
php think xadmin:publish --migrate
```

## 标准配置
- `storage.driver`
- `storage.naming`
- `storage.link`
- `storage.allowed_exts`
- `storage.local.*`
- `storage.alist.*`
- `storage.qiniu.*`
- `storage.upyun.*`
- `storage.txcos.*`
- `storage.alioss.*`

## 支持驱动
- Local
- Alist
- Qiniu
- Upyun
- TxCos
- AliOSS

## 数据依赖
- `system_file`

## 平台说明
- Windows 兼容
- Linux 兼容

## 许可证
`ThinkPlugsStorage` 基于 `Apache-2.0` 发布。
