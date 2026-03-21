# ThinkPlugsHelper for ThinkAdmin

## 版本基线
- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 组件说明
`ThinkPlugsHelper` 是开发期和交付期使用的辅助组件，负责迁移导出、安装包生成、发布命令、菜单校验和模型辅助工具。

它定位为工程化工具箱，不承担线上后台壳层和业务运行时职责。

## 主要职责
- 提供 `xadmin:publish`、`xadmin:package`、`xadmin:helper:*` 等命令。
- 负责插件迁移主脚本识别与导出。
- 负责菜单元数据校验与菜单迁移写入。
- 提供模型注释生成和少量开发辅助工具。

## 常用命令
```bash
php think xadmin:publish
php think xadmin:publish --migrate
php think xadmin:package
php think xadmin:helper:model
php think xadmin:helper:migrate
```

## 组件边界
- 不负责运行时路由、认证和后台页面。
- 不负责常驻进程、队列和存储驱动。
- 依赖 `ThinkLibrary` 提供基础运行时与元数据读取能力。

## 元数据来源
- 插件服务：`composer.json > extra.think.services`
- 插件元数据：`composer.json > extra.xadmin.app`
- 菜单元数据：`composer.json > extra.xadmin.menu`
- 迁移元数据：`composer.json > extra.xadmin.migrate`

## 菜单校验规则
- 菜单数据统一来自 `extra.xadmin.menu.items`。
- 叶子节点必须真实存在。
- 叶子节点必须同时声明 `@auth true` 和 `@menu true`。
- 写入菜单前统一经过 `PluginMenuService::assertMenus()` 校验。

## 安装
```bash
composer require zoujingli/think-plugs-helper
```

## 平台说明
- Windows 兼容
- Linux 兼容

## 许可证
`ThinkPlugsHelper` 基于 `Apache-2.0` 发布。
