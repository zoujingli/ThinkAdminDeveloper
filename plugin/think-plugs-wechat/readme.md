# ThinkPlugsWechat for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-wechat/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-wechat)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-wechat/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-wechat)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat/downloads)](https://packagist.org/packages/zoujingli/think-plugs-wechat)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-wechat)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-wechat/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-wechat)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-mit.svg)](https://mit-license.org)

**ThinkPlugsWechat** 是 **ThinkAdmin** 的核心插件，提供全面的微信基础管理功能，基于 MIT 协议开源，免费可商用！

请注意，安装此插件将会占用并替换 `app/wechat` 目录（采用先删除再写入的方式）。因此，如果您之前对 `app/wechat` 目录有过自定义修改，我们强烈建议您在安装插件前进行备份，否则这些修改可能会丢失。

此外，当您使用 `Composer` 卸载此插件时，请务必记得手动删除 `app/wechat` 目录及相关的数据表，因为这些内容不会被自动清理。

如果您希望保留自有的 `app/wechat` 目录，避免被插件更新替换，只需在该目录下创建一个名为 `ignore` 的文件（例如 `app/wechat/ignore`，请注意文件名不应有后缀）。这样，即使在执行插件安装或更新操作时，该目录也将被忽略，不会被更新替换。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 注意，插件仅支持在 ThinkAdmin v6.1 中使用
composer require zoujingli/think-plugs-wechat --optimize-autoloader
```

### 卸载插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 插件卸载不会删除数据表和 app/wechat 的代码
### 卸载后通过 composer update 时不会再更新，其他依赖除外
composer remove zoujingli/think-plugs-wechat
```

### 功能节点

可根据下面的功能节点配置菜单和访问权限，按钮操作级别的节点未展示！

* 微信接口配置：`wechat/config/options`
* 微信支付配置：`wechat/config/payment`
* 微信粉丝管理：`wechat/fans/index`
* 微信图文管理：`wechat/news/index`
* 微信菜单配置：`wechat/menu/index`
* 回复规则管理：`wechat/keys/index`
* 关注自动回复：`wechat/auto/index`
* 微信支付管理：`wechat/payment.record/index`
* 微信退款管理：`wechat/payment.refund/index`

### 业务功能特性

**核心微信功能：**
- **微信接口配置**: 提供完整的微信公众号接口配置管理，支持多公众号管理
- **微信支付配置**: 集成微信支付功能，支持 JSAPI、Native、APP 等多种支付方式
- **微信粉丝管理**: 完整的粉丝信息管理，支持标签分组、批量操作等功能
- **微信图文管理**: 可视化的图文素材编辑和管理，支持多图文组合
- **微信菜单配置**: 可视化的自定义菜单配置，支持点击、跳转、扫码等菜单类型
- **回复规则管理**: 灵活的关键词回复规则配置，支持文本、图片、图文等多种回复类型
- **关注自动回复**: 支持关注时的自动回复配置，提升用户体验
- **微信支付管理**: 完整的支付记录管理，支持订单查询和状态跟踪
- **微信退款管理**: 便捷的退款处理功能，支持部分退款和全额退款

**技术特性：**
- **MIT 开源协议**: 遵循 MIT 开源协议，免费可商用
- **模块化设计**: 各微信功能模块独立封装，便于扩展和维护
- **安全防护**: 内置微信消息签名验证、权限控制等安全机制
- **高性能优化**: 针对微信接口调用进行专门优化，确保响应速度
- **向后兼容**: 保持与现有 ThinkAdmin 版本的兼容性，确保平滑升级

### 插件数据库

本插件涉及数据表有：

* 微信-回复：`wechat_auto`
* 微信-粉丝：`wechat_fans`
* 微信-标签：`wechat_fans_tags`
* 微信-规则：`wechat_keys`
* 微信-素材：`wechat_media`
* 微信-图文：`wechat_news`
* 微信-文章：`wechat_news_article`
* 微信-支付：`wechat_payment_record`
* 微信-退款：`wechat_payment_refund`

### 版权说明

**ThinkPlugsWechat** 遵循 **MIT** 开源协议发布，并免费提供使用。

本项目包含的第三方源码和二进制文件的版权信息将另行标注，请在对应文件查看。

版权所有 Copyright © 2014-2025 by ThinkAdmin (https://thinkadmin.top) All rights reserved。
