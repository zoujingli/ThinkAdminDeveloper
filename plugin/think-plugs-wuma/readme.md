# ThinkPlugsWuma for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-wuma/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-wuma)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-wuma/downloads)](https://packagist.org/packages/zoujingli/think-plugs-wuma)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-wuma/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-wuma)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-wuma/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-wuma)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-fee.svg)](https://thinkadmin.top/fee-introduce)

**注意：** 该插件测试版有数据库结构变化，未生成升级补丁，每次更新需要全新安装！

**ThinkPlugsWuma** 是 **ThinkAdmin** 的一物一码收费授权插件，覆盖物码批次、防伪溯源、仓储流转、代理库存和标签流转等业务，未授权不可商用。

### 业务功能特性

**核心一物一码功能：**
- **物码批次管理**: 管理标签规则、批次生成和物码数据。
- **防伪溯源**: 提供溯源模板、生产批次、赋码批次、区块链授权证书和区块链内容管理。
- **库存调度**: 覆盖总部仓库、仓库账号、入库、出库、库存统计、标签关联、标签替换和标签历史。
- **代理库存**: 管理代理等级、代理用户、代理库存和调货记录。
- **扫码与窜货**: 预留扫码查询、扫码明细、窜货代理、窜货区域和实时监测等菜单，当前仍标注为开发中。

**技术特性：**
- **收费授权**: 需要联系作者获取授权，未授权不可商用。
- **商城依赖**: 依赖 ThinkPlugsWemall 的商品与代理相关模型。
- **批次化数据**: 围绕物码、生产、赋码、仓储、代理库存和标签流转建立独立数据表。
- **事件扩展**: 通过插件事件脚本接入安装、同步或业务扩展流程。

物码标签管理系统，此插件为收费授权插件，请联系作者获取授权，未授权不可商用。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！.

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 安装稳定版本 ( 插件仅支持在 ThinkAdmin v6.1 中使用 )
// 暂不可用
composer require zoujingli/think-plugs-wuma --optimize-autoloader

### 安装测试版本（ 插件仅支持在 ThinkAdmin v6.1 中使用 ）
// 暂不可用
composer require zoujingli/think-plugs-wuma dev-master --optimize-autoloader
```

### 卸载插件

```shell
// 暂不可用
composer remove zoujingli/think-plugs-wuma
```

### 插件数据

本插件涉及物码规则、生产赋码、溯源查询、仓储流转、代理库存等多组数据表，具体以安装迁移脚本为准。

### 版权说明

**ThinkPlugsWuma** 为 **ThinkAdmin** 收费授权插件，请联系作者获取授权，未授权不可商用。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《付费授权》](https://thinkadmin.top/fee-introduce.html)。

版权所有 Copyright © 2014-2026 by ThinkAdmin (https://thinkadmin.top) All rights reserved。
