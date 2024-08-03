# ThinkPlugsStatic for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-static/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-static/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-static/downloads)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-static/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-static/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-mit.svg)](https://mit-license.org)

**ThinkAdmin** 后台提供了功能强大的 **UI** 框架，并附带部分系统初始化文件，遵循 MIT 协议，不仅开源，而且完全免费并可用于商业项目。

主要代码仓库托管在 **Gitee**，而 **Github** 仅作为镜像仓库，用于发布 **Composer** 包，方便开发者快速集成。

请注意，安装此插件将会占用并替换 `public/static` 目录下的部分文件（但自定义脚本和样式保存在 `public/static/extra` 目录内的文件将不会被替换）。因此，如果您曾对 `public/static` 目录进行了自定义修改，我们建议您在安装此插件之前备份相关文件，以避免重要内容丢失。

当您使用 `Composer` 卸载此插件时，请留意它并不会自动删除或还原 `public/static` 目录中的文件，也不会自动移除系统初始化文件。为了确保系统的整洁和一致性，这些操作需要您手动完成。

我们建议您在安装或更新插件前，仔细阅读相关文档，确保了解可能的影响，并采取相应的预防措施。

`layui 2.8` 于 2023/04/24 正式发布，此插件会同步保持更新。

### 安装插件

```shell
#### 注意，此插件仅支持在 ThinkAdmin v6.1 中使用
composer require zoujingli/think-plugs-static
```

### 卸载插件

```shell
### 卸载后通过 composer update 不会再更新
### 插件本卸载不会删除 public/static 目录的代码
composer remove zoujingli/think-plugs-static
```

### 版权说明

**ThinkPlugsStatic** 遵循 **MIT** 开源协议发布，并免费提供使用。

本项目包含的第三方源码和二进制文件的版权信息将另行标注，请在对应文件查看。

版权所有 Copyright © 2014-2024 by ThinkAdmin (https://thinkadmin.top) All rights reserved。