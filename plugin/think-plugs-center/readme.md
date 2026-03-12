# ThinkPlugsCenter for ThinkAdmin

**ThinkPlugsCenter** 是 ThinkAdmin 8 / ThinkPHP 8.1 的插件应用中心，负责扫描已安装插件、读取插件元数据、展示可进入的模块插件，并提供默认插件跳转能力。

当前插件识别统一基于：

- `composer.json > extra.think.services` 的 ThinkPHP 服务注册
- `composer.json > extra.xadmin.service` 的插件运行时元数据
- `composer.json > extra.xadmin.menu` 的菜单根节点与安装检测元数据

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsCenter` 负责插件中心展示，不承载具体业务，只管理“系统里有哪些插件、插件叫什么、入口在哪”这类元信息。
- 组件读取已安装插件的 `Service` 注册、菜单根信息和 Composer 元数据，统一输出后台插件入口列表。
- 它更像后台里的应用中心，不负责插件下载市场、安装脚本生成或守护进程管理。
- 当前默认插件跳转、插件卡片和插件摘要页都通过它组织。

## 架构说明

- 展示层：`src/controller/Index.php` 与视图模板负责渲染插件入口列表和卡片。
- 元数据层：依赖 `ThinkLibrary runtime/PluginService` 读取插件编码、名称、前缀、菜单根和安装信息。
- 协同层：与 `Admin` 一起呈现后台入口，但不介入 `Worker`、`Storage`、`Wechat` 等插件自身业务。
- 数据层：只消费插件元数据和菜单信息，不维护专属业务表。

## 组件边界

- 负责插件发现结果的后台展示与入口跳转
- 负责读取插件菜单并渲染插件布局页
- 不负责插件安装下载市场，不依赖外网插件仓库
- 不负责运行时路由匹配，路由匹配仍由 `ThinkLibrary runtime` 负责

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 推荐宿主：`zoujingli/think-plugs-admin`

## 安装组件

```bash
composer require zoujingli/think-plugs-center

# 发布菜单迁移
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-center
```

组件卸载不会自动删除已执行的菜单迁移。

## 后台入口

插件编码：

- `plugin-center`

主要节点：

- `plugin-center/index/index`
- `plugin-center/index/layout`
- `plugin-center/index/setDefault`

说明：

- `index` 负责展示插件中心或自动跳转默认插件
- `layout` 负责渲染指定插件的菜单布局
- `setDefault` 负责设置默认打开插件

## 命令说明

本组件没有独立 CLI 命令。

## 发布与迁移

本组件包含迁移脚本：

- `stc/database/20241010000004_install_center20241010.php`

迁移内容只负责写入插件中心菜单入口，不创建独立业务表。

## 业务能力

- 已安装插件扫描
- 模块插件列表展示
- 默认插件自动跳转
- 插件菜单布局渲染
- 统一读取插件 `extra.xadmin.menu + Service::menu()` 生成布局菜单
- 多插件场景下的统一入口页

## 插件数据

本组件不创建独立业务表。

运行时会使用：

- `system_menu`
- `system_data`

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有守护进程

## 许可证

`ThinkPlugsCenter` 基于 `Apache-2.0` 发布。
