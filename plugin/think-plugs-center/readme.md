# ThinkPlugsCenter for ThinkAdmin

## 版本基线
- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 组件说明
`ThinkPlugsCenter` 是后台插件中心，负责扫描已安装插件、读取插件元数据、展示插件入口，并提供默认应用跳转与插件布局页。

它负责“插件入口展示”，不负责插件下载市场、安装包生成、守护进程管理或具体业务实现。

## 主要职责
- 展示已安装插件列表。
- 读取插件菜单并渲染插件布局页。
- 提供默认应用设置与自动跳转。
- 在多插件场景下提供统一的插件入口页。

## 组件边界
- 依赖 `ThinkLibrary` 读取插件元数据、菜单和运行时 URL。
- 不介入 `Worker`、`Storage`、`Wechat`、`Payment` 等插件的内部业务。
- 不创建独立业务表，只消费系统菜单和配置数据。

## 主要入口
- `plugin-center/index/index`
- `plugin-center/layout`
- `plugin-center/layout/setDefaultApp`

## 安装
```bash
composer require zoujingli/think-plugs-center
php think xadmin:publish --migrate
```

## 运行说明
- 访问 `#/plugin-center.html` 时，若已配置默认应用，会优先跳转默认应用。
- 访问 `#/plugin-center.html?from=force` 时，会强制展示插件中心列表。
- 插件布局页使用 `plugin-center/layout?encode=...` 打开。

## 数据依赖
- `system_menu`
- `system_data`

## 相关元数据
- `composer.json > extra.think.services`
- `composer.json > extra.xadmin.app`
- `composer.json > extra.xadmin.menu`
- `composer.json > extra.xadmin.migrate`

## 平台说明
- Windows 兼容
- Linux 兼容

## 许可证
`ThinkPlugsCenter` 基于 `Apache-2.0` 发布。
