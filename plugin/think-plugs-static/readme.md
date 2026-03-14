# ThinkPlugsStatic for ThinkAdmin

**ThinkPlugsStatic** 是 ThinkAdmin 8 / ThinkPHP 8.1 的静态资源与项目骨架组件，负责发布后台前端资源、默认入口文件、基础配置模板和项目初始化骨架。

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`

## 详细描述

- `ThinkPlugsStatic` 是发布型组件，负责项目骨架、前端静态资源、入口文件和默认配置模板。
- 它不提供业务路由和后台页面，主要通过 `xadmin:publish` 把需要的文件发布到项目根目录。
- 当前后台前端只保留 `LayUI + system.js + extra/script.js` 这一套加载机制，`RequireJS` 已完全移除。
- 组件定位是“前端资源与骨架源”，不参与数据库、权限和业务逻辑。

## 架构说明

- 骨架层：`stc/` 目录承载 `think`、`public/index.php`、配置模板和初始化文件。
- 资源层：`stc/public/static/*` 提供后台静态资源、JS 模块和样式资源。
- 发布层：由 `Helper` 的 `xadmin:publish` 读取 `extra.xadmin.publish` 清单完成同步。
- 运行关系：发布后资源由项目根目录直接使用，组件本身不参与请求处理。

## 组件边界

- 发布 `public/static` 前端资源
- 发布 `public/index.php`、`public/router.php` 等入口文件
- 发布基础配置模板与单应用骨架
- 后台前端统一使用 `LayUI + $.module.use(...)`
- 不再使用 `RequireJS`

## 安装组件

```bash
composer require zoujingli/think-plugs-static

# 首次初始化骨架与静态资源
php think xadmin:publish
```

## 卸载组件

```bash
composer remove zoujingli/think-plugs-static
```

卸载不会自动删除已发布到项目目录的文件。

## 发布机制

`ThinkPlugsStatic` 通过组件发布清单声明初始化文件和可覆盖资源：

- `init`：默认只补缺失文件
- `copy`：仅在 `--force` 时覆盖可更新资源

标准命令：

```bash
# 初始化缺失文件
php think xadmin:publish

# 覆盖更新静态资源和骨架
php think xadmin:publish --force
```

## 发布内容

主要发布内容包括：

- `.env.example`
- `config/app.php`
- `config/cache.php`
- `config/cookie.php`
- `config/database.php`
- `config/lang.php`
- `config/log.php`
- `config/phinx.php`
- `config/route.php`
- `config/view.php`
- `app/index/controller/Index.php`
- `route/.gitkeep`
- `public/index.php`
- `public/router.php`
- `public/robots.txt`
- `public/.htaccess`
- `public/static/plugs`
- `public/static/theme`
- `public/static/system.js`
- `public/static/login.js`
- `public/static/extra/style.css`
- `public/static/extra/script.js`

说明：

- 后台认证已改为 Token/JWT，不再依赖 Session 保存登录态
- 用户临时态统一使用基于 Token SID 的 `CacheSession`
- 语言切换默认仅支持 URL 参数与请求头，不再写语言 Cookie
- `public/static/extra` 作为自定义扩展目录，默认不会被强制覆盖
- 统一认证与会话规范见 [../../docs/architecture/auth-token-session.md](../../docs/architecture/auth-token-session.md)

## 前端约定

- 后台只保留 `LayUI` 模块加载机制
- 统一入口脚本为 `public/static/system.js` 和 `public/static/login.js`
- 自定义脚本建议放到 `public/static/extra`

## 路由与数据

- 本组件不提供独立业务路由
- 本组件不创建独立数据表

## 平台说明

- Windows 兼容
- Linux 兼容
- 纯文件发布，无平台专有进程依赖

## 许可证

`ThinkPlugsStatic` 基于 `MIT` 发布。
