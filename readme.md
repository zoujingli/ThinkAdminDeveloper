# ThinkAdminDeveloper for ThinkAdmin

**ThinkAdminDeveloper** 是基于 **ThinkAdmin** 插件机制开发的微商城系统及其他扩展插件。
该仓库包含 `ThinkAdmin` 的同步组件及插件，如：**多端账号插件**、**插件中心管理**、**多端支付插件**、**多端微商系统**、**一物一码系统** 等，此库仅用于开发并自动分发代码，后期会以插件生态方式发布。

**注意：** 此库包含部分 **ThinkAdmin** 会员授权插件，其中 **ThinkPlugsWuma** 为收费授权插件，未获得授权仅可用于本地测试体验使用，不得刻意传播或 **fork** 此仓库保存代码。

## 关于项目

**ThinkAdmin** 是一款遵循 MIT 协议的开源快速开发框架，基于 **ThinkPHP6**（兼容 **ThinkPHP8**）构建。在使用前，请务必阅读《免责声明》并同意相关条款。

我们致力于构建高效的底层框架，简化项目开发流程，提供完整的基础组件和 API 支持，助力快速开发各类 WEB 应用。框架免费提供系统权限管理、存储配置、微信授权等基础功能，成为外包开发团队的得力助手，目前已有超过 5 万个项目基于此框架运行。

**ThinkAdmin** v6 是对 v1 至 v5 的重构之作，结合 **ThinkPHP** 6 和 8 的设计思路，彻底改造系统，保留原生生态支持。我们精简了非必需组件，构建了自定义存储层、服务层和高效队列机制，并新增用户友好的指令，提升操作体验。经过严格测试，v6.1 版本展现出卓越的稳定性，系统和微信模块已达到高稳定水平。

我们持续推出新模块和辅助功能，期待后续更新！使用 **ThinkAdmin** 需要具备一定开发技能，包括 ThinkPHP、jQuery、LayUI 和 RequireJs。后台 UI 基于最新 LayUI 前端框架，支持插件加载和管理。

请勿修改 app/admin 和 app/wechat 目录，以确保未来功能和安全更新通过 Composer 管理。ThinkLibrary 作为核心组件，封装了常用操作，兼容原有 ThinkPHP 生态，降低编码复杂性。

开发者可灵活集成 WechatDeveloper 组件，支持微信公众号、小程序及支付接口，并集成二维码生成工具。系统提供多种存储选项，包括本地、自建 Alist 及主流云服务，支持 CDN 加速，确保高效传输。

内置的异步任务处理机制可并行处理多个任务，响应延时低于 0.5 秒，确保在 Windows 和 Linux 平台上的兼容性。遇到问题请随时联系我们的支持团队。感谢您选择 ThinkAdmin，我们将持续改进框架功能，更好服务开发者社区。

### 安装系统

直接使用 **composer** 安装，可提前配置好数据库参数，安装脚本会自动完成安装！

```shell
# 安装依赖组件及插件
composer update --optimize-autoloader

# 运行本地测试环境，启用 8088 商品
php think run --host 127.0.0.1 --port 8088

# 打开浏览器访问网站 ( Windows ) 
start http://127.0.0.1:8088
```

### 开发文档

* 官方技术文档：[thinkadmin.top](http://thinkadmin.top)
* 前端接口文档：[ThinkAdminMobile](https://thinkadmin.apifox.cn)

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题将无法处理！。

### 版权说明

除免费开源部分外的功能，需要参照下列方式获取授权。
项目的 `./plugin/` 为插件目录，每个插件都有独立声明授权方式，使用前请认证阅读。

* 会员授权： [《会员尊享介绍》](https://thinkadmin.top/vip-introduce)
* 收费授权：请通过文档中微信二维码联系作者。

 <img alt="" src="https://thinkadmin.top/static/img/wx.png" width="250">