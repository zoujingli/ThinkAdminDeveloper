# ThinkPlugsCenter for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-center/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-center)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-center/downloads)](https://packagist.org/packages/zoujingli/think-plugs-center)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-center/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-center)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-center/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-center)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-apache2.svg)](https://www.apache.org/licenses/LICENSE-2.0)

**ThinkAdmin** 插件管理中心是一个高效便捷的工具，专为管理已安装的插件而设计。

自 `v1.0.28` 版本起，该中心不再加载线上插件信息，从而确保在内网环境下安全稳定运行，无需依赖外网支持。

此外，我们还对插件机制进行了优化，替换了原有的 **ThinkPlugsSimpleCenter** 模式，进一步简化了插件管理流程。

无论是企业用户还是个人开发者，都能通过 **ThinkAdmin** 插件管理中心轻松管理插件，提升系统功能和用户体验。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 安装稳定版本 ( 插件仅支持在 ThinkAdmin v6.1 中使用 )
composer require zoujingli/think-plugs-center --optimize-autoloader

### 安装测试版本（ 插件仅支持在 ThinkAdmin v6.1 中使用 ）
composer require zoujingli/think-plugs-center dev-master --optimize-autoloader
```

### 卸载插件

```shell
composer remove zoujingli/think-plugs-center
```

### 插件数据

该插件未使用独立数据表；

### 版权说明

**ThinkPlugsCenter** 遵循 **Apache2** 开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有 Copyright © 2014-2025 by ThinkAdmin (https://thinkadmin.top) All rights reserved。

更多细节参阅 [LICENSE.txt](license)
