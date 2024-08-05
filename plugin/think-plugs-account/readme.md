# ThinkPlugsAccount for ThinkAdmin

[![Latest Stable Version](https://poser.pugx.org/zoujingli/think-plugs-account/v/stable)](https://packagist.org/packages/zoujingli/think-plugs-account)
[![Latest Unstable Version](https://poser.pugx.org/zoujingli/think-plugs-account/v/unstable)](https://packagist.org/packages/zoujingli/think-plugs-account)
[![Total Downloads](https://poser.pugx.org/zoujingli/think-plugs-account/downloads)](https://packagist.org/packages/zoujingli/think-plugs-account)
[![Monthly Downloads](https://poser.pugx.org/zoujingli/think-plugs-account/d/monthly)](https://packagist.org/packages/zoujingli/think-plugs-account)
[![Daily Downloads](https://poser.pugx.org/zoujingli/think-plugs-account/d/daily)](https://packagist.org/packages/zoujingli/think-plugs-account)
[![PHP Version](https://thinkadmin.top/static/icon/php-7.1.svg)](https://thinkadmin.top)
[![License](https://thinkadmin.top/static/icon/license-vip.svg)](https://thinkadmin.top/vip-introduce)

**ThinkPlugsAccount** 是 **ThinkAdmin** 的多端账号插件，作为一套通用基础用户数据管理解决方案，支持多客户端登录绑定功能。本插件属于[会员尊享插件](https://thinkadmin.top/vip-introduce)，未经授权不得用于商业用途。

目前，我们已提供丰富的数据接口，支持 **微信服务号**、**微信小程序**、**手机短信验证** 三种登录授权方式，以满足不同用户的登录需求。对于其他登录方式，您可以选择使用短信验证登录，确保用户账号的安全与便捷。

在账号逻辑数据方面，我们已全面支持**微信服务号**、**微信小程序**、**安卓APP程序**、**苹果IOS程序**、**手机网页端**、**电脑网页端**以及**自定义方式**。无论用户从哪个平台或设备登录，都能享受到流畅、统一的账号体验。

请注意，通过 **微信服务号** 和 **微信小程序** 等授权方式登录的用户，初始状态为临时用户。为了保障账号的正式性和安全性，我们要求用户通过手机号短信验证并绑定手机号，完成这一过程后，用户将升级为正式用户，享受更多会员权益和服务。

我们致力于为用户提供更加便捷、安全的账号管理体验，不断优化和完善多端账号中心的功能与服务。

**数据关联模型：**

`临时用户(usid)` `<->` `绑定手机(bind)` `<->` `正式用户(unid)`

### 话术解析

- **账号调度器 - Account**：这是一个用于创建账号管理实例对象的工具，同时也负责处理部分基础数据。它使得账号管理变得更加便捷和高效。
- **账号接口类型 - Account::TYPE**：这是终端账号请求的特定通道标识。在请求过程中，通常通过传递字段 **type** 作为参数来指定该接口类型，确保请求能够准确地被识别和处理。
- **账号实例接口 - AccountInterface**：这个接口涵盖了用户账号编号和终端账号编号的数据，以及与之相关的操作，如接口授权等。它提供了丰富的功能，使得开发者能够灵活地管理和操作账号数据。
- **用户账号编号 - unid**：这是用户的唯一账号标识，与数据表 **PlugsUser** 的 **id** 字段相对应。通过绑定和解绑操作，可以方便地将用户账号与不同的终端账号进行关联，实现跨平台登录和账号同步。
- **终端账号编号 - usid**：这代表了用户的其中一种登录账号，是用户在特定终端上的身份标识。它只能与一个用户账号进行绑定，确保了账号的唯一性和安全性。在数据表 **PlugsBind** 中，该编号与 **id** 字段相对应，方便进行数据存储和查询。

**注意事项：**

- 用户账号编号 `unid` 的获取流程如下：终端账号登录后，通过调用 `$account->bind()` 方法来创建或绑定用户账号。成功绑定后，系统将返回用户账号编号 `unid` 的值，作为该用户账号的唯一标识。

- 若需取消终端账号与用户账号的关联，可调用 `$account->unbind()` 方法。一旦关联被取消，该终端账号便可重新绑定其他用户账号，实现灵活的用户账号管理。

### 加入我们

我们的代码仓库已移至 **Github**，而 **Gitee** 则仅作为国内镜像仓库，方便广大开发者获取和使用。若想提交 **PR** 或 **ISSUE** 请在 [ThinkAdminDeveloper](https://github.com/zoujingli/ThinkAdminDeveloper) 仓库进行操作，如果在其他仓库操作或提交问题不会也不能处理。

### 开放接口

通过用户登录接口，换取 **JWT-TOKEN** 内容，之后接口需要在每次请求的头部 **header** 加上 **Api-Token** 字段并带上之后获取到的值。

**接口文档：** https://thinkadmin.apifox.cn

**特别注意：** 调用接口时后台接口未启动 `Session` 中间键，建议使用 `Cache & usid` 或 `Cache & unid` 作为`key`值来缓存数据。

### 接口状态

* `code`:`0` 操作失败，稍候重试
* `code`:`1` 操作成功，正常操作
* `code`:`401` 无效令牌，需要重新登录
* `code`:`402` 资料不全，需要补全资料
* `code`:`403` 认证超时，需要重新登录

### 安装插件

```shell
### 安装前建议尝试更新所有组件
composer update --optimize-autoloader

### 安装稳定版本 ( 插件仅支持在 ThinkAdmin v6.1 中使用 )
composer require zoujingli/think-plugs-account --optimize-autoloader

### 安装测试版本（ 插件仅支持在 ThinkAdmin v6.1 中使用 ）
composer require zoujingli/think-plugs-account dev-master --optimize-autoloader
```

### 卸载插件

```shell
### 注意，插件卸载不会删除数据表，需要手动删除
composer remove zoujingli/think-plugs-account
```

### 调用案例

```php
// 账号管理调度器
use plugin\account\service\Account;

// @ 注册一个新用户（ 微信小程序标识字段为 openid 字段 ）
//   不传 TOKEN 的情况下并存在 openid 时会主动通过 openid 查询用户信息
//   如果传 TOKEN 的情况下且 opneid 与原 openid 不匹配会报错，用 try 捕获异常
//   注意，每次调用 Account::mk() 都会创建新的调度器，设置 set 和 get 方法的 rejwt 参数可返回接口令牌 
$account = Account::mk(Account::WXAPP, TOKEN='');
$user = $account->set(['openid'=>'OPENID', 'phone'=>'13888888888']);
var_dump($user);

// 列如更新用户手机号，通过上面的操作已绑定账号，可以直接设置
$account->set(['phone'=>'1399999999']);

// 设置额外的扩展数据，数据库没有字段，不需要做为查询条件的字段
$account->set(['extra'=>['desc'=>'用户描述', 'sex'=>'男']]);

// 获取用户资料，无账号返回空数组
$user = $account->get();
var_dump($user);

// 以上插件仅仅是注册终端账号，也就是临时账号
// 下面我们通过 bind 操作，绑定或创建用户账号（ 主账号 ）
$user = $account->bind(['phone'=>'1399999999'],['uesrname'=>"会员用户"]);
var_dump($user); // $user['user'] 是主账号信息

// 解除该终端账号关联主账号
$user = $account->unBind();
var_dump($user); // 此处不会再有 $user['user'] 信息

// 判断终端账号是否为空，也就是还没有调用 set 访问或 init 失败
$user = $account->isNull();

// 获取接口 Token 信息
$user = $account->get(true);
var_dump($user); // $user['token'] 即为 JwtToken 值，接口 header 传 api-token 字段

// 判断终端账号是否已经关联主账号
$is = $account->isBind();
var_dump($is);

// 获取主账号关联的所有终端账号
$binds = $account->allBind();

// 通过终端USID取消其关联主账号
$binds = $account->delBind($usid);

// 动态注册接口通道，由插件服务类或模块 sys.php 执行注册
Account::add('diy', '自定义通道名称', '终端账号编号验证字段');

// 通道状态 - 禁用接口，将禁止该方式访问数据
Account::set('diy', 0);

// 通道状态 - 启用接口，将启用该方式访问数据
Account::set('diy', 1);

// 保存通道状态，下次访问也同样生效
Account::save();

// 获取接口认证字段以及检查接口是否有效
$field = Account::field('diy');
if($field)// 接口有效
else //接口无效

// 获取全部接口
$types = Account::types();
var_dump($types);
```

### 功能节点

可根据下面的功能节点配置菜单及访问权限，按钮操作级别的节点未展示！

* 用户账号管理：`plugin-account/master/index`
* 终端账号管理：`plugin-account/device/index`
* 手机短信管理：`plugin-account/message/index`

### 插件数据

本插件涉及数据表有：

* 插件-账号-授权 `plugin_account_auth`
* 插件-账号-终端 `plugin_account_bind`
* 插件-账号-短信 `plugin_account_msms`
* 插件-账号-资料 `plugin_account_user`

### 版权说明

**ThinkPlugsAccount** 为 **ThinkAdmin** 会员插件。

未获得此插件授权时仅供参考学习不可商用，了解商用授权请阅读 [《会员授权》](https://thinkadmin.top/vip-introduce)。

版权所有 Copyright © 2014-2024 by ThinkAdmin (https://thinkadmin.top) All rights reserved。