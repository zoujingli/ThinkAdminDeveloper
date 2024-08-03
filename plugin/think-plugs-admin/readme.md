# ThinkPlugsAdmin for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-admin/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-admin)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-admin/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-admin)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-admin/downloads)](https://packagist.org/packages/zoujingli/think-plugs-admin)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-admin/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-admin)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-admin/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-admin)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-mit.svg)](https://mit-license.org)

**ThinkPlugsAdmin** 是 **ThinkAdmin** 的核心插件，提供后台基础管理模块功能，基于 MIT 协议开源，免费可商用！

请注意，安装此插件将占用并替换 `app/admin` 目录（采用先删除再写入的方式）。若您曾对 `app/admin` 进行了自定义修改，我们不建议您安装此插件，以避免修改内容丢失。

当您使用 `Composer` 卸载此插件时，请留意它并不会自动删除 `app/admin` 目录及对应的数据表，这些操作需要您手动完成。

如果您不希望 `app/admin` 目录被插件更新替换，有一个简单的方法可以避免这一情况：在 `app/admin` 目录下创建一个名为 `ignore` 的文件（例如 `app/admin/ignore`，请确保文件名没有后缀）。这样，即使执行了插件的安装或更新操作，该目录也将被忽略，不会被替换更新。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果基础仓库操作或提交问题不会也不能处理。

### 插件文档

https://thinkadmin.top/plugin/think-plugs-admin.html

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 注意，插件仅支持在 ThinkAdmin v6.1 中使用
composer require zoujingli/think-plugs-admin --optimize-autoloader
```

### 卸载插件

```shell
### 插件卸载不会删除数据表和 app/admin 的代码
### 卸载后通过 composer update 时不会再更新，其他依赖除外
composer remove zoujingli/think-plugs-admin
```

### 功能节点

可根据下面的功能节点配置菜单和访问权限，按钮操作级别的节点未展示！

* 系统参数配置：`admin/config/index`
* 系统任务管理：`admin/queue/index`
* 系统日志管理：`admin/oplog/index`
* 数据字典管理：`admin/base/index`
* 系统文件管理：`admin/file/index`
* 系统菜单管理：`admin/menu/index`
* 系统权限管理：`admin/auth/index`
* 系统用户管理：`admin/user/index`

### 插件数据库

本插件涉及数据表有：

* 系统-权限：`system_auth`
* 系统-授权：`system_auth_node`
* 系统-字典：`system_base`
* 系统-配置：`system_config`
* 系统-数据：`system_data`
* 系统-文件：`system_file`
* 系统-菜单：`system_menu`
* 系统-日志：`system_oplog`
* 系统-任务：`system_queue`
* 系统-用户：`system_user`

### 版权说明

**ThinkPlugsAdmin** 遵循 **MIT** 开源协议发布，并免费提供使用。

本项目包含的第三方源码和二进制文件的版权信息将另行标注，请在对应文件查看。

版权所有 Copyright © 2014-2024 by ThinkAdmin (https://thinkadmin.top) All rights reserved。
