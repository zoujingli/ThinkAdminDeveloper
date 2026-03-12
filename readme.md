# ThinkAdminDeveloper for ThinkAdmin

**ThinkAdminDeveloper** 是基于 ThinkAdmin 8 / ThinkPHP 8.1 的组件化开发仓库，用于维护核心基础库、运行时组件、后台平台插件和业务插件。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- 这个仓库不是单一应用，而是围绕 ThinkAdmin 8 / ThinkPHP 8.1 组织的一组标准组件，覆盖核心库、运行时、后台平台和业务插件。
- 目标不是维护旧版多应用项目，而是把系统重构成“插件优先、单应用兜底、服务注册标准化、组件边界清晰”的结构。
- 当前所有核心能力都已经按组件拆分：`ThinkLibrary` 提供核心层，`Worker` 提供常驻运行时，`Storage` 提供存储中心，`Helper` 提供开发与交付工具，业务插件只承载各自业务域。
- 因此这个仓库更适合作为组件化开发基线和业务插件宿主，而不是传统的单仓单应用模板。

## 架构说明

- 核心层：`ThinkLibrary` 提供运行时、认证、任务协议、菜单节点、模型查询和基础工具。
- 运行层：`ThinkPlugsWorker` 用 Workerman 托管 `http` 和 `queue` 两类常驻服务。
- 平台层：`ThinkPlugsAdmin`、`ThinkPlugsCenter`、`ThinkPlugsStorage`、`ThinkPlugsWechatClient`、`ThinkPlugsWechatService` 提供后台平台和标准能力入口。
- 业务层：`ThinkPlugsAccount`、`ThinkPlugsPayment`、`ThinkPlugsWemall`、`ThinkPlugsWuma` 负责各自业务域。
- 交付层：`ThinkPlugsHelper` 和 `ThinkPlugsStatic` 负责发布、迁移、安装包、静态资源和项目骨架。

## 仓库组成

### 核心组件

- **ThinkLibrary**
  核心基础库，负责运行时、JWT、任务协议、控制器和公共工具。
- **ThinkPlugsWorker**
  Workerman 运行时组件，负责 `http` 和 `queue` 常驻服务。
- **ThinkPlugsHelper**
  开发辅助组件，负责迁移导出、发布、安装包和注释生成。
- **ThinkPlugsStorage**
  存储中心组件，负责驱动注册、上传授权和标准化配置。
- **ThinkPlugsStatic**
  静态资源和项目骨架组件。

### 后台与平台插件

- **ThinkPlugsAdmin**
  后台管理中心。
- **ThinkPlugsCenter**
  插件应用中心。
- **ThinkPlugsWechatClient**
  公众号标准平台。
- **ThinkPlugsWechatService**
  公众号开放平台。

### 业务插件

- **ThinkPlugsAccount**
  多端账号体系。
- **ThinkPlugsPayment**
  支付中心。
- **ThinkPlugsWemall**
  分销商城。
- **ThinkPlugsWuma**
  一物一码与防伪溯源。

## 当前架构约定

### 路由与应用

- `app` 只保留一个 `single_app`
- 插件通过 URL 前缀注册访问入口
- 请求首段命中插件前缀时切换到插件
- 未命中插件前缀时回退到单应用
- 动态插件切换默认关闭

### 服务注册

- ThinkPHP 服务注册统一使用 `composer.json > extra.think.services`
- 插件运行时元数据统一使用 `composer.json > extra.xadmin.service`
- 菜单元数据统一使用 `composer.json > extra.xadmin.menu`
- 迁移元数据统一使用 `composer.json > extra.xadmin.migrate`

### 认证

- 后台统一使用 `Authorization: Bearer <JWT>`
- API 统一使用 Token 模式识别身份
- 不再使用 Session/Cookie 承载后台登录态

### 运行时

- 统一命令入口：`php think xadmin:worker`
- `http` 负责托管系统 HTTP 服务
- `queue` 负责长耗时任务和延时任务调度
- 队列记录和执行协议留在 `ThinkLibrary`

### 前端

- 后台统一使用 `LayUI + $.module.use(...)`
- `RequireJS` 已彻底移除
- 数据导出统一使用前端 JavaScript 模块

### 目录

- `app/admin` 和 `app/wechat` 已退役为兼容占位目录
- 实际源码只维护在 `plugin/*/src`

## 安装与初始化

```bash
# 安装依赖
composer update --optimize-autoloader

# 发布配置、静态资源和迁移脚本
php think xadmin:publish --migrate

# 启动 HTTP 服务
php think xadmin:worker start http -d
```

默认访问地址：

- `http://127.0.0.1:2346`

## 常用命令

```bash
# 查看全部命令
php think list

# 启动 HTTP 服务
php think xadmin:worker start http -d

# 启动队列服务
php think xadmin:worker start queue -d

# 查看运行状态
php think xadmin:worker status all

# 生成插件迁移脚本
php think xadmin:helper:migrate

# 生成安装包
php think xadmin:package
```

## 发布与迁移

`xadmin:publish` 由 `ThinkPlugsHelper` 提供，负责：

- 发布插件 `stc/config`
- 发布 `ThinkPlugsStatic` 的骨架与静态资源
- 发布 `ThinkPlugsWorker` 的 `config/worker.php`
- 同步各插件 `stc/database` 到根目录 `database/migrations`
- 通过 `database/migrations/.xadmin-published.json` 清理历史失效或冲突迁移

当前约定为：

- 每个插件只保留一份最终安装脚本
- 根目录 `database/migrations` 视为发布产物

## 平台说明

- Windows 兼容
- Linux 兼容
- `Worker reload` 仅适用于 Linux / macOS

## 开发文档

- 官网文档：[thinkadmin.top](https://thinkadmin.top)
- 接口文档：[ThinkAdminMobile](https://thinkadmin.apifox.cn)
- 架构说明：[plugin-first-refactor.md](/Users/anyon/Runtime/ThinkAdminDeveloper/docs/architecture/plugin-first-refactor.md)

## 注意事项

- 本仓库包含会员插件，未授权不得商用
- 插件卸载通常不会自动删除已执行迁移和历史数据
- 自定义前端脚本建议放在 `public/static/extra`

## 许可证

除会员授权插件外，其余开源部分按各组件自身许可证发布。
