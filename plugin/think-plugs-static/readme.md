# ThinkPlugsStatic for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-static/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-static/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-static/downloads)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-static/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-static/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-static)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-mit.svg)](https://mit-license.org)

**ThinkAdmin** 后台提供了功能强大的 **UI** 框架，并附带部分系统初始化文件，遵循 MIT 协议，不仅开源，而且完全免费并可用于商业项目。

请注意，安装此插件将会占用并替换 `public/static` 目录下的部分文件（但自定义脚本和样式保存在 `public/static/extra` 目录内的文件将不会被替换）。因此，如果您曾对 `public/static` 目录进行了自定义修改，我们建议您在安装此插件之前备份相关文件，以避免重要内容丢失。

当您使用 `Composer` 卸载此插件时，请留意它并不会自动删除或还原 `public/static` 目录中的文件，也不会自动移除系统初始化文件。为了确保系统的整洁和一致性，这些操作需要您手动完成。

我们建议您在安装或更新插件前，仔细阅读相关文档，确保了解可能的影响，并采取相应的预防措施。

### 业务功能特性

**核心静态资源管理：**
- **UI 框架集成**: 集成 LayUI 2.8 前端框架，提供丰富的 UI 组件和交互体验
- **静态资源管理**: 统一管理 CSS、JavaScript、图片等静态资源文件
- **CDN 加速支持**: 支持 CDN 加速和资源版本控制，提升页面加载速度
- **自定义扩展**: 保留 `public/static/extra` 目录用于自定义脚本和样式，避免被覆盖
- **自动更新**: 同步保持 LayUI 框架的最新版本，确保安全性和功能完整性

**技术特性：**
- **MIT 开源协议**: 遵循 MIT 开源协议，免费可商用
- **模块化设计**: 静态资源按功能模块组织，便于维护和扩展
- **性能优化**: 针对前端资源进行压缩和优化，提升页面加载性能
- **向后兼容**: 保持与现有 ThinkAdmin 版本的兼容性，确保平滑升级
- **安全防护**: 定期更新依赖库，修复已知安全漏洞

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

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 版权说明

**ThinkPlugsStatic** 遵循 **MIT** 开源协议发布，并免费提供使用。

本项目包含的第三方源码和二进制文件的版权信息将另行标注，请在对应文件查看。

版权所有 Copyright © 2014-2025 by ThinkAdmin (https://thinkadmin.top) All rights reserved。