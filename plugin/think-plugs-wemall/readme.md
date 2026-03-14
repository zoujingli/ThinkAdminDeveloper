# ThinkPlugsWemall for ThinkAdmin

**ThinkPlugsWemall** 是 ThinkAdmin 8 / ThinkPHP 8.1 的分销商城组件，负责商品、订单、售后、会员、分销、海报、报表和商城 API。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsWemall` 是完整的分销商城组件，覆盖商品、订单、售后、会员、分销、优惠券、海报、充值和商城 API。
- 它不是基础设施组件，而是聚合 `Account`、`Payment`、`WechatClient` 等能力之上的业务域。
- 当前商城后台既包含运营配置，也包含订单履约、代理分销、报表和用户营销等页面。
- 组件内还保留商城专属开放接口和集成服务，不再把这部分能力放回 `ThinkLibrary`。

## 架构说明

- 模块层：`base / help / shop / user / api` 等控制器分组分别处理商城配置、内容、交易和用户体系。
- 应用层：`src/service/*` 与 `src/integration/*` 负责下单、履约、分销、海报、通知和商城开放接口。
- 领域层：商品、订单、退款、用户、分销、签到、优惠券等模型组成商城核心数据域。
- 协同层：依赖 `Payment` 处理支付账务，依赖 `Account` 管理用户身份，依赖 `WechatClient` 处理公众号交互。

## 组件边界

- 插件编码：`wemall`
- 访问前缀：`wemall`
- 负责商城后台、商城 API、分销关系和返佣链路
- 通过支付事件、账号绑定事件与其它插件解耦联动
- 不负责底层账号体系和底层支付通道本身

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 必需：`zoujingli/think-plugs-account`
- 必需：`zoujingli/think-plugs-payment`
- 必需：`zoujingli/think-plugs-storage`
- 推荐宿主：`zoujingli/think-plugs-system`

## 安装组件

```bash
composer require zoujingli/think-plugs-wemall

# 首次发布迁移脚本
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-wemall
```

组件卸载不会自动删除商城业务表和已执行迁移。

## 后台入口与 API

后台节点按模块划分：

- `wemall/base.*/*`
- `wemall/user.*/*`
- `wemall/shop.*/*`
- `wemall/help.*/*`

典型入口：

- `wemall/base.report/index`
- `wemall/base.config/index`
- `wemall/base.poster/index`
- `wemall/user.admin/index`
- `wemall/user.rebate/index`
- `wemall/shop.goods/index`
- `wemall/shop.order/index`
- `wemall/shop.refund/index`
- `wemall/help.problem/index`

接口节点：

- `wemall/api.goods/*`
- `wemall/api.data/*`
- `wemall/api.auth/*`
- `wemall/api.help/*`

## 命令说明

本组件注册命令：

- `php think xdata:mall:clear`
- `php think xdata:mall:trans`
- `php think xdata:mall:users`

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000007_install_wemall20241010.php`

迁移内容覆盖：

- 商城配置
- 商品与规格库存
- 订单、发货、售后
- 会员行为、收藏、搜索、签到
- 返佣、关系、充值、提现
- 快递模板、通知、海报、帮助中心

## 事件联动

组件内部监听：

- `PluginAccountBind`
- `PluginPaymentAudit`
- `PluginPaymentRefuse`
- `PluginPaymentSuccess`
- `PluginPaymentCancel`
- `PluginPaymentConfirm`
- `PluginWemallOrderConfirm`

这些事件用于绑定关系初始化、订单状态推进和返佣确认。

## 业务能力

- 商城参数和页面装修
- 商品、分类、规格、库存管理
- 订单、发货、退款、评论管理
- 会员等级、折扣、创建、充值
- 分销关系、返佣、提现
- 海报、通知、报表
- 帮助中心与意见反馈
- 面向客户端的商城 API
- 商城开放平台快递查询适配

## 插件数据

主要数据表分组：

- 商城配置：`plugin_wemall_config_*`
- 快递与模板：`plugin_wemall_express_*`
- 商品：`plugin_wemall_goods*`
- 订单：`plugin_wemall_order*`
- 帮助中心：`plugin_wemall_help_*`
- 用户行为：`plugin_wemall_user_action_*`
- 会员与返佣：`plugin_wemall_user_*`

## 平台说明

- Windows 兼容
- Linux 兼容
- 长耗时任务建议结合 `ThinkPlugsWorker queue`

## 许可证

`ThinkPlugsWemall` 基于专有授权分发，未授权不可商用。
