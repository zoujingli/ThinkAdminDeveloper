# ThinkPlugsWechatService for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/downloads)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat-service/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![PHP Version Require](http://poser.pugx.org/zoujingli/think-plugs-wechat-service/require/php)](https://packagist.org/packages/zoujingli/think-plugs-wechat-service)
[![ThinkAdmin VIP 授权](https://img.shields.io/badge/license-VIP%20授权-blueviolet.svg)](https://thinkadmin.top/vip-introduce)

**ThinkPlugsWechatService** 是 **ThinkAdmin** 的微信开放平台管理插件，用于开放平台配置、授权微信管理和 JSON-RPC 接口调度。本插件属于[会员尊享插件](https://thinkadmin.top/vip-introduce)，未经授权不得用于商业用途。

该插件可为 ThinkPlugsWechat 提供远程开放平台能力，适用于需要集中管理公众号、小程序授权与接口调用的部署场景。

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

### 业务功能特性

**核心微信开放平台功能：**
- **微信开放平台集成**: 提供开放平台配置和授权微信管理
- **远程接口调用**: 支持 JSON-RPC 远程调用，可与 ThinkPlugsWechat 插件无缝集成
- **授权管理**: 完整的微信授权管理，支持多公众号和小程序的授权配置
- **用户接口调用**: 通过开放平台 SDK 调度粉丝列表、用户信息等微信接口
- **接口调度**: 统一的接口调度机制，简化微信开放平台的复杂性

**技术特性：**
- **VIP 授权**: 会员专享插件，非授权用户不得用于商业目的
- **模块化设计**: 各微信功能模块独立封装，便于扩展和维护
- **远程调用支持**: 支持 JSON-RPC 远程调用，实现插件间的无缝集成
- **安全防护**: 内置 Token 验证和权限控制，确保接口调用安全
- **向后兼容**: 保持与现有 ThinkAdmin 版本的兼容性，确保平滑升级

### 插件数据

本插件涉及数据表有：

* 微信-授权 `wechat_auth`

### 版权说明

**ThinkPlugsWechatService** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2026 by ThinkAdmin (https://thinkadmin.top) All rights reserved。