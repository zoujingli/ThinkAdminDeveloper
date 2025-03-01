# ThinkPlugsHelper for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-helper/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-helper)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-helper/downloads)](https://packagist.org/packages/zoujingli/think-plugs-helper)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-helper/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-helper)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-helper/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-helper)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-apache2.svg)](https://www.apache.org/licenses/LICENSE-2.0)

**ThinkPlugsHelper** 是为便捷 ThinkAdmin 开发而给的工具包。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 安装稳定版本 ( 插件仅支持在 ThinkAdmin v6.1 中使用 )
composer require zoujingli/think-plugs-helper --optimize-autoloader --dev

### 安装测试版本（ 插件仅支持在 ThinkAdmin v6.1 中使用 ）
composer require zoujingli/think-plugs-helper dev-master --optimize-autoloader --dev
```

### 使用注释

执行下面的指令即可实现模型字段注释。

```shell
php think xadmin:helper:model
```

### 卸载插件

```shell
composer remove zoujingli/think-plugs-helper
```

### 插件数据

该插件未使用独立数据表；

### 版权说明

**ThinkPlugsHelper** 遵循 **Apache2** 开源协议发布，并提供免费使用。

本项目包含的第三方源码和二进制文件之版权信息另行标注。

版权所有 Copyright © 2014-2025 by ThinkAdmin (https://thinkadmin.top) All rights reserved。

更多细节参阅 [LICENSE.txt](license)
