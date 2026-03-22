# ThinkPlugsPayment for ThinkAdmin

**ThinkPlugsPayment** 是 ThinkAdmin 8 / ThinkPHP 8.1 的支付中心组件，负责支付配置、支付行为、退款处理、余额积分账本和支付事件分发。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsPayment` 是系统支付中台，负责支付配置、支付单、退款、余额积分账本和支付事件分发。
- 组件把具体支付行为抽象成统一支付记录与事件，不直接等同于商城；商城、充值、会员等业务都可以复用它。
- 后台侧主要提供配置与记录查询，支付状态变更、退款回调和账本更新由组件内部服务处理。
- 组件不管理守护进程，也不负责账号登录，只依赖其它组件提供用户和业务上下文。

## 架构说明

- 接入层：`src/controller/*` 提供支付配置、记录、退款、账本等后台页面与接口。
- 应用层：支付服务、退款服务、账本处理和事件分发逻辑负责串联支付流程。
- 领域层：支付记录、退款记录、余额积分等模型承载支付状态机和账本数据。
- 集成层：对接微信、余额、积分等支付通道，并为 `Wemall` 等业务插件暴露统一支付能力。

## 组件边界

- 插件编码：`payment`
- 访问前缀：`payment`
- 负责余额、积分、支付记录、退款记录和支付通道配置
- 负责对外派发支付审核、支付成功、支付取消、订单确认等事件
- 不负责商城业务订单本身，业务订单由上层插件维护

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 必需：`zoujingli/think-plugs-account`
- 必需：`zoujingli/think-plugs-system`
- 推荐宿主：`zoujingli/think-plugs-system`

## 安装组件

```bash
composer require zoujingli/think-plugs-payment

# 首次发布迁移脚本
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-payment
```

组件卸载不会自动删除已执行的迁移和支付数据表。

## 后台入口与接口

后台节点：

- `payment/config/index`
- `payment/record/index`
- `payment/refund/index`
- `payment/balance/index`
- `payment/integral/index`

接口节点：

- `/api/payment/auth/address/*`
- `/api/payment/auth/balance/*`
- `/api/payment/auth/integral/*`

双入口约定：

- 后台页面走 `/payment/...`
- 用户侧支付接口走 `/api/payment/...`

支付通知路由：

- `/plugin-payment-notify/:vars`

## 命令说明

本组件没有独立 CLI 命令。

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000006_install_payment20241010.php`

迁移内容包括：

- `plugin_payment_address`
- `plugin_payment_balance`
- `plugin_payment_config`
- `plugin_payment_integral`
- `plugin_payment_record`
- `plugin_payment_refund`

## 支付事件

组件会派发或联动这些标准事件：

- `PluginAccountBind`
- `PluginPaymentAudit`
- `PluginPaymentRefuse`
- `PluginPaymentSuccess`
- `PluginPaymentCancel`
- `PluginPaymentConfirm`

上层业务插件可以通过事件监听解耦订单状态刷新和业务记账。

## 业务能力

- 支付配置管理
- 混合支付能力
- 余额支付与流水
- 积分支付与流水
- 线下凭证支付审核
- 支付行为追踪
- 退款记录管理
- 高精度金额计算

## 插件数据

- 收货地址：`plugin_payment_address`
- 余额账本：`plugin_payment_balance`
- 支付配置：`plugin_payment_config`
- 积分账本：`plugin_payment_integral`
- 支付记录：`plugin_payment_record`
- 退款记录：`plugin_payment_refund`

## 平台说明

- Windows 兼容
- Linux 兼容
- 不直接管理守护进程

## 许可证

`ThinkPlugsPayment` 基于专有授权分发，未授权不可商用。
