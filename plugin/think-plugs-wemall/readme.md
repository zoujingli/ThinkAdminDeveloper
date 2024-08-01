# ThinkPlugsWemall for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-wemall/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-wemall/downloads)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-wemall/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-wemall/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-vip.svg)](https://thinkadmin.top/vip-introduce)

**注意：** 该插件测试版有数据库结构变化，未生成升级补丁，每次更新需要全新安装！

多终端微商城系统，此插件为[会员尊享插件](https://thinkadmin.top/vip-introduce)，未授权不可商用。

代码主仓库放在 **Gitee**，**Github** 仅为镜像仓库用于发布 **Composer** 包。

### 依赖插件

* 插件服务管理中心：[ThinkPlugsCenter](https://thinkadmin.top/plugin/think-plugs-center.html) 或者 [ThinkPlugsCenterSimple](https://thinkadmin.top/plugin/think-plugs-center-simple.html) 插件
* 多终端账号管理插件：[ThinkPlugsAccount](https://thinkadmin.top/vip-plugs-account)
* 多终端支付管理插件：[ThinkPlugsPayment](https://thinkadmin.top/vip-plugs-payment)

如果不安装《插件服务管理中心》将不显示商城菜单入口，需要自行手动添加菜单。

### 相关文档

* 接口文档：https://thinkadmin.apifox.cn
* 插件文档：https://thinkadmin.top/plugin/think-plugs-wemall.html

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 安装稳定版本 ( 插件仅支持在 ThinkAdmin v6.1 中使用 )
composer require zoujingli/think-plugs-wemall --optimize-autoloader

### 安装测试版本（ 插件仅支持在 ThinkAdmin v6.1 中使用 ）
composer require zoujingli/think-plugs-wemall dev-master --optimize-autoloader
```

### 卸载插件

```shell
composer remove zoujingli/think-plugs-wemall
```

### 插件数据

本插件涉及数据表有：--

### 版权说明

**ThinkPlugsWemall** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2024 by ThinkAdmin (https://thinkadmin.top) All rights reserved。