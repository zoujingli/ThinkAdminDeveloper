# ThinkPlugsAccount for ThinkAdmin

**ThinkPlugsAccount** 是 ThinkAdmin 8 / ThinkPHP 8.1 的统一账号组件，负责多端账号模型、登录通道注册、账号绑定关系、接口认证令牌和账号资料管理。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsAccount` 负责统一账号域模型，覆盖主账号、终端账号、短信验证码、登录通道绑定、接口认证令牌和账号资料管理。
- 组件主要解决“多端身份收口”问题，把 API 登录、公众号登录、小程序登录等入口统一到一套账号关系里。
- 后台侧负责账号资料维护与查询，真实登录入口主要位于 `src/controller/api/*`。
- 组件不负责后台 JWT 登录，不负责支付账务，也不负责商城订单域，这些能力分别由 `Admin`、`Payment`、`Wemall` 处理。

## 架构说明

- 入口层：`src/controller/api/*` 提供登录、绑定、资料读取接口，`src/controller/*` 提供后台管理页。
- 应用层：`src/service/*` 负责账号编排、登录适配、消息分发和访问契约。
- 领域层：主账号、终端账号、消息记录等模型维护账号状态与绑定关系。
- 协同层：通过插件方式复用 `WechatClient`、`Storage` 等能力，但不反向管理这些插件的业务配置。

## 组件边界

- 插件编码：`account`
- 访问前缀：`account`
- 负责终端账号、主账号、短信验证码、登录接口和账号绑定关系
- 负责输出账号接口使用的 JWT 认证令牌
- 不负责支付、商城、后台守护进程和 Session 登录态

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 必需：`zoujingli/think-plugs-storage`
- 推荐宿主：`zoujingli/think-plugs-system`

## 安装组件

```bash
composer require zoujingli/think-plugs-account

# 首次发布迁移脚本
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-account
```

组件卸载不会自动删除已执行的迁移和账号数据表。

## 后台入口与 API

后台节点：

- `account/master/index`
- `account/device/index`
- `account/message/index`

接口入口：

- `account/api.login/*`
- `account/api.auth/*`
- `account/api.auth.center/*`
- `account/api.wechat/*`
- `account/api.wxapp/*`

接口认证说明：

- 登录成功后返回 `account-auth` JWT 令牌
- 后续接口统一通过请求头 `Authorization: Bearer <token>` 认证
- 浏览器整页请求可额外读取认证 Cookie
- 认证链路不再依赖 Session
- 登录来源可以是手机、公众号、小程序或 App，但认证协议统一为账号 JWT
- 统一规范以 [../../docs/architecture/auth-token-session.md](../../docs/architecture/auth-token-session.md) 为准

## 命令说明

本组件没有独立 CLI 命令。

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000005_install_account20241010.php`

迁移内容包括：

- `plugin_account_auth`
- `plugin_account_bind`
- `plugin_account_msms`
- `plugin_account_user`

## 数据模型说明

标准账号模型：

- `usid`：终端账号编号
- `unid`：主账号编号
- 绑定关系：终端账号 `<->` 主账号

典型流程：

1. 终端登录并初始化终端账号。
2. 通过短信或其它可信方式绑定主账号。
3. 返回 JWT 令牌供接口层继续访问。

## 业务能力

- 多端账号统一管理
- 微信服务号登录
- 微信小程序登录
- 手机短信验证登录
- 主账号与终端账号绑定解绑
- 动态注册登录通道
- 终端资料与主账号资料同步
- Token 化接口认证

## 调用示例

```php
use plugin\account\service\Account;

$account = Account::mk(Account::WXAPP, TOKEN: '');
$user = $account->set(['openid' => 'OPENID', 'phone' => '13888888888']);

$account->set(['extra' => ['desc' => '用户描述', 'sex' => '男']]);
$profile = $account->get(true);

$bound = $account->bind(['phone' => '13999999999'], ['username' => '会员用户']);
$allBind = $account->allBind();
```

## 插件数据

- 授权配置：`plugin_account_auth`
- 终端账号：`plugin_account_bind`
- 短信记录：`plugin_account_msms`
- 主账号资料：`plugin_account_user`

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有进程能力

## 许可证

`ThinkPlugsAccount` 基于专有授权分发，未授权不可商用。
