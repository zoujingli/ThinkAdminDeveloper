# ThinkPlugsWuma for ThinkAdmin

**ThinkPlugsWuma** 是 ThinkAdmin 8 / ThinkPHP 8.1 的防伪溯源组件，负责一物一码、溯源模板、赋码批次、仓储流转、代理库存和扫码验证。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsWuma` 是防伪溯源业务组件，负责一物一码、码批次、模板、赋码、仓储流转、代理库存和扫码验证。
- 它面向“码体系 + 仓储 + 渠道”这类业务闭环，既有后台运营页，也有扫码查询和出入库处理。
- 组件包含码生成、批量导入导出、仓储关系和来源模板等子能力，源码统一维护在插件目录。
- 组件不负责统一账号登录、支付或公众号标准能力，而是按需复用其它组件。

## 架构说明

- 模块层：`source / warehouse / sales / api` 等控制器分组分别处理码源、仓储、销售渠道和接口能力。
- 应用层：`src/service/*` 负责码生成、关系绑定、导入导出、仓储处理和流转校验。
- 领域层：码记录、批次、仓库、证书、区块链、模板等模型承载溯源业务状态。
- 协同层：可与 `Account`、`WechatClient` 等组件联动，但自身保持独立的码业务边界。

## 组件边界

- 插件编码：`wuma`
- 访问前缀：`wuma`
- 负责物码规则、溯源内容、仓储出入库、代理库存和扫码查询
- 负责对外提供防伪查询路由
- 不负责支付中心和账号底层认证逻辑

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 必需：`zoujingli/think-plugs-storage`
- 推荐宿主：`zoujingli/think-plugs-system`
- 可联动：`zoujingli/think-plugs-wemall`

## 安装组件

```bash
composer require zoujingli/think-plugs-wuma

# 首次发布迁移脚本
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-wuma
```

组件卸载不会自动删除溯源业务表和已执行迁移。

## 后台入口与 API

后台节点按模块划分：

- `wuma/code/index`
- `wuma/source.*/*`
- `wuma/warehouse*/*`
- `wuma/sales.*/*`
- `wuma/scaner.*/*`

典型入口：

- `wuma/code/index`
- `wuma/source.template/index`
- `wuma/source.produce/index`
- `wuma/source.assign/index`
- `wuma/warehouse/index`
- `wuma/warehouse.stock/index`
- `wuma/sales.level/index`
- `wuma/sales.order/index`

接口节点：

- `wuma/api.base/*`
- `wuma/api.coder/*`
- `wuma/api.auth/*`
- `wuma/api.login/*`

接口请求头规范：

- 认证统一使用 `Authorization: Bearer <token>`
- 设备标识统一使用 `X-Device-Code: <code>`
- 设备类型统一使用 `X-Device-Type: <type>`
- 不再使用 `Api-Token`、`Api-Code`、`Api-Type`
- 当前设备 token 为独立设备认证，不属于通用账号 JWT 体系
- 统一规范见 [../../docs/architecture/auth-token-session.md](../../docs/architecture/auth-token-session.md)

设备接口示例：

```http
POST /wuma/api.login/login HTTP/1.1
Authorization: Bearer <token>
X-Device-Code: PDA-001
X-Device-Type: warehouse
Content-Type: application/json
```

公开查询路由：

- `<mode>/<code>!<verify><extra?>`

## 命令说明

本组件注册命令：

- `php think xdata:wuma:create`

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000008_install_wuma20241010.php`

迁移内容覆盖：

- 物码规则与区间
- 溯源模板、生产批次、赋码批次
- 区块链内容与授权证书
- 查询记录、验证记录、扫码通知
- 仓库、出入库、库存、标签替换与关联
- 代理等级、代理用户、库存与调货

## 业务能力

- 一物一码规则管理
- 防伪验证与溯源展示
- 生产与赋码批次管理
- 区块链证书与内容管理
- 仓库、入库、出库、库存调度
- 代理库存与调货管理
- 消费者扫码查询与通知分析

## 插件数据

主要数据表分组：

- 物码规则：`plugin_wuma_code_rule*`
- 溯源模块：`plugin_wuma_source_*`
- 仓储模块：`plugin_wuma_warehouse*`
- 代理销售：`plugin_wuma_sales_*`

## 平台说明

- Windows 兼容
- Linux 兼容
- 长耗时批量处理建议结合 `ThinkPlugsWorker queue`

## 许可证

`ThinkPlugsWuma` 基于专有授权分发，未授权不可商用。
