# ThinkPlugsPayment for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-payment/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-payment)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-payment/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-payment)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-payment/downloads)](https://packagist.org/packages/zoujingli/think-plugs-payment)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-payment/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-payment)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-payment/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-payment)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-vip.svg)](https://thinkadmin.top/vip-introduce)

**ThinkPlugsPayment** 是 **ThinkAdmin** 的多端支付插件，本插件属于[会员尊享插件](https://thinkadmin.top/vip-introduce)，未经授权不得用于商业用途。

支付类型主要涵盖线上支付和抵扣支付两大类。

- 抵扣支付包括账户余额支付和账户积分抵扣。
- 线上支付则涵盖各类微信支付、支付宝支付以及大额凭证支付。

默认情况下，账户余额支付和账户积分抵扣均得到支持，但业务系统可根据需求控制是否向用户开放。在支付优先级方面，积分优先于余额，余额则优先于其他支付方式。
若您希望完全关闭积分抵扣或余额支付功能，只需在支付配置中取消对应的支付方式选项即可。

当前，同一业务订单支持混合支付模式，业务系统需传入订单需支付的总金额及此次支付金额。支付完成情况将根据已完成的支付总额来判断，并触发全局支付事件。您可以在任意初始化文件中监听支付事件，以便实时刷新订单状态。

**待办事项**：未来子支付单将支持独立的退款操作。目前，积分抵扣、余额支付、凭证支付以及微信支付退款操作已得到支持。敬请期待更多更新与优化。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！.

### 开放接口

接口文档：https://thinkadmin.apifox.cn

### 支付事件

* `PluginAccountBind` 注册用户绑定事件，回调参数 `function (array $data);`
* `PluginPaymentAudit` 注册支付审核事件，回调参数 `function (PluginPaymentRecord $payment);`
* `PluginPaymentRefuse` 注册支付拒审事件，回调参数 `function (PluginPaymentRecord $payment);`
* `PluginPaymentSuccess` 注册支付完成事件，回调参数 `function (PluginPaymentRecord $payment);`
* `PluginPaymentCancel` 注册支付取消事件，回调参数 `function (PluginPaymentRecord $payment);`
* `PluginPaymentConfirm` 注册订单确认事件，回调参数 `function (array $data);`

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 安装稳定版本 ( 插件仅支持在 ThinkAdmin v6.1 中使用 )
composer require zoujingli/think-plugs-payment --optimize-autoloader

### 安装测试版本（ 插件仅支持在 ThinkAdmin v6.1 中使用 ）
composer require zoujingli/think-plugs-payment dev-master --optimize-autoloader
```

### 卸载插件

```shell
### 注意，插件卸载不会删除数据表，需要手动删除
composer remove zoujingli/think-plugs-payment
```

### 业务功能特性

**核心支付功能：**
- **多端支付支持**: 支持微信服务号、微信小程序、APP、网页等多终端支付场景
- **混合支付模式**: 支持余额、积分、微信、支付宝等多种支付方式组合使用
- **凭证支付审核**: 支持上传凭证的线下支付，包含待审核、已审核、已拒绝等状态管理
- **支付事件驱动**: 通过支付事件（审核、完成、取消、确认）实现业务逻辑解耦
- **退款管理**: 支持部分退款和全额退款，自动处理余额、积分的退回操作
- **支付配置管理**: 可视化配置各种支付通道参数，支持动态启用/禁用支付方式
- **高精度金融计算**: 全面采用 BC Math 高精度数学函数，确保金融计算的准确性，避免浮点数精度丢失问题

**账户资金管理：**
- **余额管理系统**: 完整的余额充值、消费、锁定、解锁、作废等操作
- **积分管理系统**: 积分获取、消耗、兑换比率配置、积分有效期管理
- **高精度计算**: 使用 BC Math 高精度数学函数，确保金融计算的准确性
- **资金流水追踪**: 完整的资金变动记录，支持来源追溯和审计
- **并发安全控制**: 支持高并发场景下的余额和积分操作，避免超支问题
- **数据完整性保障**: 通过数据库约束确保业务数据的一致性和有效性

**技术特性：**
- **支付接口抽象**: 统一的支付接口标准，便于扩展新的支付方式
- **数据库约束优化**: 添加金额非负约束、状态枚举约束，确保数据完整性
- **异常处理机制**: 完善的异常捕获和日志记录，便于问题排查
- **事务一致性**: 关键业务操作保证数据一致性，避免脏数据产生
- **向后兼容**: 保持 API 稳定性，确保平滑升级

### 插件数据

本插件涉及数据表有：

* 插件-支付-地址：`plugin_payment_address`
* 插件-支付-余额：`plugin_payment_balance`  
* 插件-支付-积分：`plugin_payment_integral`
* 插件-支付-配置：`plugin_payment_config`
* 插件-支付-行为：`plugin_payment_record`
* 插件-支付-退款：`plugin_payment_refund`

### 版权说明

**ThinkPlugsPayment** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2026 by ThinkAdmin (https://thinkadmin.top) All rights reserved。