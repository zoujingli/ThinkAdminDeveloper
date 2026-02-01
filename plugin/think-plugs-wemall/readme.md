# ThinkPlugsWemall for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-wemall/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-wemall/downloads)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-wemall/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-wemall/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-wemall)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-vip.svg)](https://thinkadmin.top/vip-introduce)

**注意：** 该插件测试版有数据库结构变化，未生成升级补丁，每次更新需要全新安装！

多终端微商城系统，此插件为[会员尊享插件](https://thinkadmin.top/vip-introduce)，未授权不可商用。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！.

### 依赖插件

* 插件服务管理中心：[ThinkPlugsCenter](https://thinkadmin.top/plugin/think-plugs-center.html)
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

### 业务功能特性

**核心功能模块：**
- **多级分销体系**: 支持三级分销模式，可配置不同等级的代理返佣规则
- **灵活返佣机制**: 支持下单奖励、首购奖励、复购奖励、升级奖励、平推返佣等多种返佣类型
- **会员等级管理**: 基于订单金额和数量的自动等级升级，支持自定义等级规则
- **代理等级管理**: 团队业绩统计，支持多维度的代理等级升级条件
- **混合支付支持**: 集成余额支付、积分抵扣、微信支付、支付宝等多种支付方式
- **商品管理系统**: 完整的商品分类、规格、库存、价格管理
- **订单全流程管理**: 从下单、支付、发货到售后的完整订单生命周期管理
- **推广海报管理**: 支持为不同用户等级生成个性化推广海报
- **团队业绩统计**: 实时统计团队销售业绩和返佣数据
- **高精度计算保障**: 全面采用 BC Math 高精度计算，避免浮点数精度丢失问题

**技术特性：**
- **高精度金融计算**: 使用 BC Math 高精度计算，避免浮点数精度丢失问题
- **事件驱动架构**: 通过支付事件、订单事件等实现业务逻辑解耦
- **数据库约束优化**: 添加完整的外键约束、检查约束和索引优化
- **并发安全处理**: 支持高并发场景下的余额、积分、库存操作
- **数据完整性保障**: 通过数据库约束确保业务数据的一致性和有效性
- **向后兼容**: 保持 API 稳定性，确保平滑升级

### 插件数据

本插件涉及数据表有：
- 商城-配置-等级: `plugin_wemall_config_agent`, `plugin_wemall_config_level`
- 商城-配置-返利: `plugin_wemall_config_rebate`, `plugin_wemall_config_discount`
- 商城-商品-内容: `plugin_wemall_goods`, `plugin_wemall_goods_item`, `plugin_wemall_goods_stock`
- 商城-订单-内容: `plugin_wemall_order`, `plugin_wemall_order_item`, `plugin_wemall_order_sender`
- 商城-用户-关系: `plugin_wemall_user_relation`, `plugin_wemall_user_rebate`, `plugin_wemall_user_transfer`
- 数据-快递-公司: `plugin_wemall_express_company`, `plugin_wemall_express_template`
- 数据-意见-反馈: `plugin_wemall_help_feedback`, `plugin_wemall_help_problem`

### 版权说明

**ThinkPlugsWemall** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2026 by ThinkAdmin (https://thinkadmin.top) All rights reserved。
