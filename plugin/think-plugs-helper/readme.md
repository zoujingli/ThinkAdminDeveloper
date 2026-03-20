# ThinkPlugsHelper for ThinkAdmin

**ThinkPlugsHelper** 是 ThinkAdmin 8 / ThinkPHP 8.1 的开发辅助组件，负责模型注释生成、数据库脚本导出、插件迁移打包、发布安装和菜单迁移校验。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsHelper` 是开发辅助组件，负责模型注释生成、迁移脚本导出、插件安装包生成、发布命令和菜单迁移校验。
- 它主要面向开发期和交付期，不承担线上业务接口，也不提供后台业务中心。
- 当前数据库脚本导出、安装包模板、`xadmin:publish` 和 `xadmin:package` 都集中在这个组件。
- 组件定位是“工程化工具箱”，不是业务运行时核心。

## 架构说明

- 命令层：`src/command/*` 暴露 `publish / package / helper:*` 等 CLI 能力。
- 服务层：`src/service/*` 负责迁移元数据读取、菜单校验、导出逻辑、模型注释生成和辅助集成。
- 模板层：`src/service/bin/*` 提供安装包模板和脚本骨架。
- 协同层：依赖 `ThinkLibrary` 获取系统运行时能力，但不持有后台登录、队列、存储等业务实现。

## 组件边界

- 负责开发期和安装期工具链
- 负责 `xadmin:publish`、`xadmin:package`、`xadmin:helper:*`
- 负责插件迁移主脚本识别和菜单迁移校验
- 负责承载少量非核心的辅助型集成实现，例如物流轨迹查询
- 不负责运行时路由、认证和守护进程

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`topthink/think-migration`
- 推荐宿主：`zoujingli/think-plugs-system`

## 安装组件

```bash
composer require zoujingli/think-plugs-helper
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-helper
```

## 命令说明

常用命令：

- `php think xadmin:helper:model`
- `php think xadmin:helper:migrate`
- `php think xadmin:database`
- `php think xadmin:replace`
- `php think xadmin:sysmenu`
- `php think xadmin:publish`
- `php think xadmin:publish --migrate`
- `php think xadmin:package`

## 发布与迁移

本组件负责统一处理：

- 配置模板发布
- 静态资源发布
- 插件迁移同步
- 安装包生成

标准元数据来源：

- 服务注册：`composer.json > extra.think.services`
- 插件元数据：`composer.json > extra.xadmin.service`
- 菜单元数据：`composer.json > extra.xadmin.menu`
- 迁移元数据：`composer.json > extra.xadmin.migrate`

## 菜单校验规则

- `Service::menu()` 只声明菜单结构
- 叶子节点必须存在真实控制器节点
- 叶子节点必须同时声明 `@auth true` 和 `@menu true`
- `PluginMenuService::assertMenus()` 与 `PhinxExtend::writePluginMenu()` 会先做一致性校验

## 业务能力

- 模型注释生成
- 数据库结构导出
- 单插件最终迁移脚本生成
- 插件安装包打包
- 发布模板同步
- 菜单迁移写入与校验
- 物流轨迹辅助查询

## 插件数据

本组件不创建独立业务表。

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有守护进程

## 许可证

`ThinkPlugsHelper` 基于 `Apache-2.0` 发布。
