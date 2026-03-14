# ThinkPlugsWechatClient for ThinkAdmin

**ThinkPlugsWechatClient** 是 ThinkAdmin 8 / ThinkPHP 8.1 的公众号标准平台组件，负责公众号配置、粉丝同步、素材图文、菜单、回复规则和微信支付后台能力。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsWechatClient` 是公众号标准平台组件，负责公众号基础配置、粉丝同步、素材图文、菜单、关键字回复、自动回复和公众号支付后台页。
- 它面向“单个公众号业务接入”，适合系统自己直接管理公众号，而不是开放平台代理模式。
- 组件同时暴露后台管理页与若干公众号回调入口，但开放平台授权和多公众号托管不属于这个组件。
- 业务插件可以复用它的公众号能力，例如账号登录、消息回复、支付通知等。

## 架构说明

- 接入层：`src/controller/*` 提供公众号配置、粉丝、菜单、素材、支付等后台页面。
- 服务层：微信服务、消息处理、支付处理与素材同步逻辑负责串联公众号业务流程。
- 领域层：粉丝、素材、图文、菜单、支付记录等模型承载公众号侧状态。
- 集成层：对接微信公众平台接口，并为 `Account`、`Payment`、`Wemall` 提供公众号标准能力。

## 组件边界

- 插件编码：`wechat`
- 访问前缀：`wechat`
- 负责公众号标准平台后台、粉丝管理和微信支付记录
- 负责公众号接口推送、网页授权和 JS SDK 接口
- 不负责微信开放平台代理和第三方平台授权
- 长耗时同步任务统一交给 `ThinkPlugsWorker queue`

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 推荐宿主：`zoujingli/think-plugs-system`
- 推荐运行时：`zoujingli/think-plugs-worker`

## 安装组件

```bash
composer require zoujingli/think-plugs-wechat-client

# 首次发布迁移脚本
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-wechat-client
```

组件卸载不会自动删除已执行的迁移和微信业务表。

## 后台入口与 API

后台节点：

- `wechat/config/options`
- `wechat/config/payment`
- `wechat/fans/index`
- `wechat/news/index`
- `wechat/menu/index`
- `wechat/keys/index`
- `wechat/auto/index`
- `wechat/payment.record/index`
- `wechat/payment.refund/index`

接口节点：

- `wechat/api.push/*`
- `wechat/api.view/*`
- `wechat/api.js/*`
- `wechat/api.login/*`

## 命令说明

本组件注册了三个命令：

- `php think xadmin:fansall`
- `php think xadmin:fansmsg`
- `php think xadmin:fanspay`

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000003_install_wechat20241010.php`

迁移内容包括：

- `wechat_auto`
- `wechat_fans`
- `wechat_fans_tags`
- `wechat_keys`
- `wechat_media`
- `wechat_news`
- `wechat_news_article`
- `wechat_payment_record`
- `wechat_payment_refund`

## 业务能力

- 公众号参数配置
- 微信支付参数配置
- 粉丝同步与标签管理
- 素材与图文管理
- 自定义菜单管理
- 关键词回复与关注自动回复
- 微信支付行为管理
- 微信退款管理

## 运行说明

- 粉丝同步、自动回复和支付清理可结合 `ThinkPlugsWorker queue` 执行
- 支付通知和公众号事件推送通过插件接口直接进入应用
- 组件源码统一维护在 `plugin/think-plugs-wechat-client/src`

## 插件数据

- 自动回复：`wechat_auto`
- 粉丝资料：`wechat_fans`
- 粉丝标签：`wechat_fans_tags`
- 关键字规则：`wechat_keys`
- 素材库：`wechat_media`
- 图文主表：`wechat_news`
- 图文文章：`wechat_news_article`
- 支付记录：`wechat_payment_record`
- 退款记录：`wechat_payment_refund`

## 平台说明

- Windows 兼容
- Linux 兼容
- 后台长任务建议配合 `ThinkPlugsWorker`

## 许可证

`ThinkPlugsWechatClient` 基于 `MIT` 发布。
