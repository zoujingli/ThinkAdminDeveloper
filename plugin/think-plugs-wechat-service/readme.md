# ThinkPlugsWechatService for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/downloads)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![PHP Version Require](http://poser.pugx.org/zoujingli/think-plugs-wechat-service/require/php)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![ThinkAdmin VIP 授权](https://img.shields.io/badge/license-VIP%20授权-blueviolet.svg)](https://thinkadmin.top/vip-introduce)

微信开放平台管理插件是一款专为会员尊享的插件，非授权用户不得将其用于商业目的。此插件旨在简化[微信开放平台](https://open.weixin.qq.com)的功能开发流程，让您无需再为服务对接和接口调度而烦恼，所有复杂性都已为您妥善处理。

今后，**ThinkAdmin** 将把微信开放平台的基础功能统一集中在此插件中，实现功能的集中管理和深度优化。目前，该插件已全面集成 **公众号** 和 **小程序** 管理等核心接口，为您的微信开发工作提供强大的后盾。无论您是希望高效开发公众号、小程序等应用，还是执行其他微信开放平台的操作，此插件都将是您不可或缺的好帮手。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 开放接口

此插件支持 [**ThinkPlugsWechat**](https://thinkadmin.top/plugin/think-plugs-wechat.html) 应用插件远程调用，需要增加配置`sysconf('wechat.service_jsonrpc')`远程调用的 **JSON-RPC** 接口地址；

接口地址可以在此插件的节点 `plugin-wechat-service/config/index` 页面查看，注意此插件接口地址需要带有 `TOKEN` 占位字符；

**JSON-RPC** 接口地址格式如：`http://admin.local.cuci.cc/plugin-wechat-service/api.client/jsonrpc?token=TOKEN`

### 安装插件

```shell
### 注意，仅支持在 ThinkAdmin v6.1 中使用
composer require zoujingli/think-plugs-wechat-service
```

### 卸载插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 注意，插件仅支持在 ThinkAdmin v6.1 中使用
composer remove zoujingli/think-plugs-wechat-service --optimize-autoloader
```

### 调用案例

```php
// 开放平台SDK调用入口
use plugin\wechat\service\AuthService;

// 1. 实例公众号 APPID 的 User 接口
$user = AuthService::WeChatUser(APPID);

// 2. 获取公众号 APPID 的粉丝列表（ 第一页 100 条 ）
$userList = $user->getUserList();
var_dump($userList);

// 3. 获取公众号 APPID 的 OPENID 资料
// 现在调用此接口获取不到粉丝详情资料
$userInfo = $user->getUserInfo(OPENID);
var_dump($userInfo);

// 其他 WeChatDeveloper 的接口实例以此类推
// 具体接口实例对象可以阅读SDK的源码或对应文档

```

### 功能节点

可根据下面的功能节点配置菜单及访问权限，按钮操作级别的节点未展示！

* 开放平台配置：`plugin-wechat-service/config/index`
* 授权微信管理：`plugin-wechat-service/wechat/index`

### 插件数据

本插件涉及数据表有：

* 微信-授权 `wechat_auth`

### 版权说明

**ThinkPlugsWechatService** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2025 by ThinkAdmin (https://thinkadmin.top) All rights reserved。