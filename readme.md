# ThinkAdminDeveloper for ThinkAdmin

**ThinkAdminDeveloper** 是基于 **ThinkAdmin** 插件机制开发的微商城系统及其他扩展插件。

该仓库包含 `ThinkAdmin` 的同步组件及插件，如：**多端账号插件**、**插件中心管理**、**多端支付插件**、**多端微商系统**、**一物一码系统** 等，此库仅用于开发并自动分发代码，后期会以插件生态方式发布。

**注意：** 此库包含部分 **ThinkAdmin** 会员授权插件，其中 **ThinkPlugsWuma** 为收费授权插件，未获得授权仅可用于本地测试体验使用，不得刻意传播或 **fork** 此仓库保存代码。

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

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果基础仓库操作或提交问题不会也不能处理。

### 版权说明

除免费开源部分外的功能，需要参照下列方式获取授权。
项目的 `./plugin/` 为插件目录，每个插件都有独立声明授权方式，使用前请认证阅读。

* 会员授权： [《会员尊享介绍》](https://thinkadmin.top/vip-introduce)
* 收费授权：请通过文档中微信二维码联系作者。

 <img alt="" src="https://thinkadmin.top/static/img/wx.png" width="250">