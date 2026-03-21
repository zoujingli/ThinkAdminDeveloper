# ThinkPlugsWechatService for ThinkAdmin

**ThinkPlugsWechatService** 是 ThinkAdmin 8 / ThinkPHP 8.1 的公众号开放平台组件，负责第三方平台配置、公众号授权管理和远程 JSON-RPC 服务。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsWechatService` 是公众号开放平台组件，面向第三方平台或代运营场景，负责公众号授权、开放平台配置和远程服务通信。
- 它和 `WechatClient` 的区别在于：`WechatClient` 面向单公众号直连，`WechatService` 面向开放平台代理和多公众号授权。
- 组件内部还承担 JSON-RPC 远程调用入口，用于开放平台与业务系统之间的服务通信。
- 组件不负责普通后台登录和存储，不直接承担商城等业务域。

## 架构说明

- 接入层：`src/controller/*` 与 `src/controller/api/*` 提供授权管理、推送接收和远程调用入口。
- 服务层：`Service` 与相关服务类负责开放平台配置装载、授权状态维护和调用编排。
- 集成层：通过 JSON-RPC 与远端服务通信，并对接微信开放平台回调与授权接口。
- 协同层：可与 `WechatClient`、`Account`、`Wemall` 等业务插件联动，但不替代它们的业务模型。

## 组件边界

- 插件编码：`plugin-wechat-service`
- 访问前缀：`plugin-wechat-service`
- 负责微信开放平台配置、授权账号管理和远程服务入口
- 负责为 `ThinkPlugsWechatClient` 等业务插件提供远程能力
- 不承载公众号标准平台的本地后台能力

## 依赖关系

- 必需：`zoujingli/think-library`
- 必需：`zoujingli/think-plugs-helper`
- 推荐宿主：`zoujingli/think-plugs-system`

## 安装组件

```bash
composer require zoujingli/think-plugs-wechat-service

# 首次发布迁移脚本
php think xadmin:publish --migrate
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-wechat-service
```

组件卸载不会自动删除已执行的迁移和授权数据。

## 后台入口与远程接口

后台节点：

- `plugin-wechat-service/config/index`
- `plugin-wechat-service/wechat/index`

接口节点：

- `/api/plugin-wechat-service/client/jsonrpc`
- `/api/plugin-wechat-service/push/*`

JSON-RPC 地址示例：

- `http://example.com/api/plugin-wechat-service/client/jsonrpc?token=TOKEN`

双入口约定：

- 后台页面统一走 `/plugin-wechat-service/...`
- 开放平台接口统一走 `/api/plugin-wechat-service/...`

典型示例：

- `/plugin-wechat-service/config/index`
- `/api/plugin-wechat-service/push/ticket`
- `/api/plugin-wechat-service/push/notify/appid/$APPID$`
- `/api/plugin-wechat-service/push/auth?source=SOURCE`
- `/api/plugin-wechat-service/client/jsonrpc?token=TOKEN`

## 命令说明

本组件注册命令：

- `php think xsync:wechat`

## 发布与迁移

本组件包含单一安装脚本：

- `stc/database/20241010000009_install_wechat_service20241010.php`

迁移内容包括：

- `wechat_auth`

## 业务能力

- 微信开放平台参数配置
- 授权公众号管理
- 远程 JSON-RPC 服务
- 推送事件接收
- 标准平台远程能力代理

## 对接说明

- `ThinkPlugsWechatClient` 可通过 `sysconf('wechat.service_jsonrpc')` 对接本组件
- 建议在远程地址中保留 `TOKEN` 占位并由运行时动态替换
- 远程接口安全由 Token 与后台权限共同控制

## 插件数据

- 微信授权：`wechat_auth`

## 平台说明

- Windows 兼容
- Linux 兼容
- 不依赖平台专有命令

## 许可证

`ThinkPlugsWechatService` 基于专有授权分发，未授权不可商用。
