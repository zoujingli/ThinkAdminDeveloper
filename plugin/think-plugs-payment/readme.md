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

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

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

### 插件数据

本插件涉及数据表有：

* 插件-支付-地址：`plugin_payment_address`
* 插件-支付-余额：`plugin_payment_balance`
* 插件-支付-积分：`plugin_payment_integral`
* 插件-支付-配置：`plugin_payment_config`
* 插件-支付-行为：`plugin_payment_record`

### 版权说明

**ThinkPlugsPayment** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2024 by ThinkAdmin (https://thinkadmin.top) All rights reserved。