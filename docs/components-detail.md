# 组件详细说明文档

本文档详细描述 ThinkAdminDeveloper 各组件的功能、架构和使用说明。

## 核心组件详细说明

### 1. ThinkLibrary - 核心基础库

**包名**: `zoujingli/think-library`

**定位**: 核心基础库，为 ThinkAdmin 提供运行时基础设施

**核心功能**:

1. **运行时服务**
   - RuntimeService: 运行时环境配置同步，处理 PHAR 兼容、目录初始化
   - AppService: 应用管理、插件发现、服务注册、配置管理
   - NodeService: 节点管理、权限判断、菜单节点处理
   - QueueService: 队列门面服务（真实实现在 Worker 插件）

2. **认证会话**
   - JwtToken: JWT 令牌生成、验证、刷新
   - CacheSession: 基于 Token SID 的缓存会话管理
   - RequestTokenService: 请求级令牌识别服务
   - SystemContext: 系统上下文接口（运行时实现）
   - NullSystemContext: 空实现上下文（未认证状态）

3. **路由适配**
   - Route: 自定义路由对象，支持插件路由注册
   - Url: URL 构建工具，支持插件 URL 生成
   - MultAccess: 多应用访问中间件，处理插件前缀切换

4. **基础类型**
   - Controller: 标准控制器基类，提供通用控制器方法
   - Model: 标准模型基类，扩展软删除、时间戳等能力
   - Command: 标准命令基类，提供命令通用方法
   - Plugin: 插件管理类，处理插件元数据加载
   - Service: 服务基类，提供基础服务方法
   - Exception: 框架异常类，统一异常处理

5. **Helper 工具**
   - QueryHelper: 数据查询构建器，支持分页、筛选、排序
   - FormBuilder: 表单构建器，支持表单元素快速生成
   - PageBuilder: 列表页面构建器，支持表格、筛选、操作列
   - ValidateHelper: 数据验证器，支持规则验证、错误提示

6. **扩展工具**
   - CodeToolkit: 编码工具（加密/解密/Base64/Hash）
   - FileTools: 文件操作工具（目录创建、文件复制、权限管理）
   - HttpClient: HTTP 客户端（cURL 封装、请求构建）
   - ArrayTree: 数组树工具（树形结构构建、扁平化）
   - FaviconBuilder: 网站图标生成器
   - ImageSliderVerify: 图片滑块验证码
   - JsonRpcHttpClient: JSON-RPC HTTP 客户端
   - JsonRpcHttpServer: JSON-RPC HTTP 服务端

**全局函数**:

- 应用相关：`syspath()`, `runpath()`, `sysconf()`, `sysvar()`, `isOnline()`
- URL 相关：`sysuri()`, `apiuri()`, `plguri()`
- 认证相关：`auth()`, `admin_user()`, `tsession()`
- 工具函数：`xss_safe()`, `str2arr()`, `arr2str()`, `data_save()`, `normalize()`

**依赖要求**:
- PHP >= 8.1
- ThinkPHP >= 8.1
- 扩展：gd, curl, json, zlib, iconv, openssl, mbstring, fileinfo
- 推荐：redis (用于 CacheSession 和缓存驱动)

**许可证**: MIT License

---

### 2. ThinkPlugsSystem - 系统后台组件

**包名**: `zoujingli/think-plugs-system`

**定位**: 系统后台插件，提供后台壳层、认证权限、菜单用户和系统运维能力

**核心功能**:

1. **后台壳层**
   - 登录页面、首页框架、后台布局
   - 面包屑导航、页面模板

2. **认证授权**
   - JWT 令牌认证中间件
   - RBAC 权限控制
   - 菜单权限判断
   - 节点权限验证

3. **系统用户**
   - 用户管理（增删改查）
   - 角色管理
   - 权限分配
   - 密码修改

4. **系统配置**
   - 参数配置（分组管理）
   - 配置缓存
   - 配置导入导出
   - 字典管理

5. **操作日志**
   - 操作记录自动记录
   - 日志查询筛选
   - 日志导出
   - 日志清理

6. **任务管理**
   - 系统任务查看
   - 任务状态管理
   - 任务日志查看

**数据表**:
- `system_auth` - 系统权限角色
- `system_auth_node` - 权限节点绑定
- `system_menu` - 系统菜单管理
- `system_user` - 系统用户管理
- `system_base` - 系统基础配置（数据字典）
- `system_config` - 系统参数配置
- `system_data` - 系统扩展数据
- `system_oplog` - 系统操作日志

**目录结构**:
```
think-plugs-system/
├── src/
│   ├── controller/      # 控制器层
│   │   ├── Auth.php    # 权限管理
│   │   ├── Base.php    # 基础数据
│   │   ├── Config.php  # 系统配置
│   │   ├── Menu.php    # 菜单管理
│   │   ├── Oplog.php   # 操作日志
│   │   ├── User.php    # 用户管理
│   │   └── ...
│   ├── middleware/      # 中间件层
│   │   ├── JwtTokenAuth.php
│   │   └── RbacAccess.php
│   ├── model/          # 模型层
│   │   ├── SystemAuth.php
│   │   ├── SystemConfig.php
│   │   ├── SystemMenu.php
│   │   ├── SystemUser.php
│   │   └── ...
│   ├── service/        # 服务层
│   │   ├── SystemAuthService.php
│   │   ├── SystemContext.php
│   │   └── UserService.php
│   └── view/           # 视图层
├── stc/database/       # 迁移脚本
└── tests/              # 单元测试
```

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsStatic
- ThinkPlugsWorker

**许可证**: MIT License

---

### 存储中心功能 (已合并到 System)

Storage 组件已合并到 ThinkPlugsSystem，作为其核心功能之一。

**存储驱动管理**:
- LocalStorage: 本地存储驱动
- AliossStorage: 阿里云 OSS 存储驱动
- TxcosStorage: 腾讯云 COS 存储驱动
- QiniuStorage: 七牛云 Kodo 存储驱动
- UpyunStorage: 又牛云存储驱动
- AlistStorage: Alist 网络存储驱动

**核心文件**:
- `plugin/system/src/storage/StorageManager.php` - 存储管理器
- `plugin/system/src/storage/StorageAuthorize.php` - 上传授权管理
- `plugin/system/src/storage/StorageConfig.php` - 存储配置管理
- `plugin/system/src/controller/File.php` - 文件管理控制器
- `plugin/system/src/controller/api/Upload.php` - 上传 API 控制器
- `plugin/system/src/model/SystemFile.php` - 文件数据模型

**数据表**:
- `system_file` - 系统文件管理表

**功能说明**:
- 统一的 Storage 门面接口
- 多存储驱动支持和管理
- 上传授权和临时令牌生成
- 文件元数据管理
- 文件上传、删除、清理
- 文件类型统计和管理

**使用示例**:
```php
use plugin\system\storage\StorageManager;

// 获取存储管理器
$storage = StorageManager::instance();

// 上传文件
$result = $storage->upload($file, 'local');

// 获取上传授权
$auth = StorageAuthorize::mk()->buildToken([
    'type' => 'image',
    'xkey' => 'upload_key',
    'xname' => 'filename.jpg'
]);
```

---

### 3. ThinkPlugsWorker - Workerman 运行时组件

**包名**: `zoujingli/think-plugs-worker`

**定位**: Workerman 运行时插件，提供 HTTP 和队列常驻服务，支持跨平台进程管理

**核心功能**:

1. **HTTP 服务**
   - 基于 Workerman 的高性能 HTTP 服务器
   - 支持多进程并发（默认 4 进程）
   - 支持文件监控和自动重载（开发环境）
   - 内存监控和自动保护

2. **队列服务**
   - 长耗时任务调度
   - 延时任务处理
   - 任务锁机制
   - 失败重试
   - 历史记录保留

3. **进程管理**
   - **Linux/macOS**: Workerman 守护进程，信号控制（reload/stop），pidFile 辅助管理
   - **Windows**: console.exe 启动后台 PHP 进程，进程扫描 + 健康检查，不依赖 pidFile
   - 命令签名统一：`xadmin:worker serve <service>`

4. **状态监控**
   - 进程状态查看
   - 内存使用监控
   - 文件变更监控
   - 健康检查

**命令入口**:
```bash
# 启动 HTTP 服务
php think xadmin:worker start http -d

# 启动队列服务
php think xadmin:worker start queue -d

# 查看运行状态
php think xadmin:worker status all

# 停止服务
php think xadmin:worker stop http

# 重启所有服务
php think xadmin:worker restart all
```

**数据表**:
- `system_queue` - 系统队列任务

**配置示例** (`config/worker.php`):
```php
return [
    'services' => [
        'http' => [
            'enabled' => true,
            'label' => 'ThinkAdmin HTTP',
            'driver' => 'http',
            'server' => [
                'host' => '127.0.0.1',
                'port' => 2346,
            ],
            'process' => [
                'name' => 'ThinkAdminHttp',
                'count' => 4,
            ],
        ],
        'queue' => [
            'enabled' => true,
            'label' => 'ThinkAdmin Queue',
            'driver' => 'queue',
            'process' => [
                'name' => 'ThinkAdminQueue',
                'count' => 2,
            ],
            'queue' => [
                'scan_interval' => 1,      // 扫描间隔（秒）
                'batch_limit' => 20,       // 每批最多任务数
                'lock_timeout' => 3600,    // 任务锁超时（秒）
                'retain_days' => 7,        // 历史记录保留天数
            ],
        ],
    ],
];
```

**依赖**:
- PHP >= 8.1
- Workerman >= 5.1.9
- Symfony/Process >= 6.0
- ThinkLibrary

**许可证**: Apache-2.0 License

---

### 4. ThinkPlugsHelper - 开发辅助组件

**包名**: `zoujingli/think-plugs-helper`

**定位**: 开发期和交付期使用的辅助组件，负责迁移导出、安装包生成、发布命令、菜单校验和模型辅助工具

**主要职责**:
- 提供 `xadmin:publish`、`xadmin:package`、`xadmin:helper:*` 等命令
- 负责插件迁移主脚本识别与导出
- 负责菜单元数据校验与菜单迁移写入
- 提供模型注释生成和少量开发辅助工具

**常用命令**:
```bash
# 发布配置、静态资源和迁移脚本
php think xadmin:publish

# 发布并执行数据库迁移
php think xadmin:publish --migrate

# 生成安装包
php think xadmin:package

# 生成模型注释
php think xadmin:helper:model

# 生成迁移脚本
php think xadmin:helper:migrate
```

**元数据来源**:
- 插件服务：`composer.json > extra.think.services`
- 插件元数据：`composer.json > extra.xadmin.app`
- 菜单元数据：`composer.json > extra.xadmin.menu`
- 迁移元数据：`composer.json > extra.xadmin.migrate`

**组件边界**:
- 不负责运行时路由、认证和后台页面
- 不负责常驻进程、队列和存储驱动
- 依赖 ThinkLibrary 提供基础运行时与元数据读取能力

**依赖**:
- PHP >= 8.1
- ThinkLibrary

**许可证**: Apache-2.0 License

---

## 平台插件详细说明

### 5. ThinkPlugsStorage - 存储中心组件

**包名**: `zoujingli/think-plugs-storage`

**定位**: 存储中心组件，统一管理存储驱动、上传授权和配置元数据

**核心功能**:
- 存储驱动管理（本地存储、OSS、COS 等）
- 上传授权和临时令牌
- 文件配置元数据管理
- Storage 门面实现

**数据表**:
- `system_file` - 系统文件管理

**支持驱动**:
- 本地存储
- 阿里云 OSS
- 腾讯云 COS
- 七牛云 Kodo
- 其他兼容 Flysystem 的驱动

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsSystem

---

### 6. ThinkPlugsWechatClient - 微信公众号标准平台

**包名**: `zoujingli/think-plugs-wechat-client`

**定位**: 公众号标准平台组件，负责公众号配置、粉丝同步、素材图文、菜单、回复规则和微信支付后台能力

**核心功能**:
- 公众号参数配置
- 微信支付参数配置
- 粉丝同步与标签管理
- 素材与图文管理
- 自定义菜单管理
- 关键词回复与关注自动回复
- 微信支付行为管理
- 微信退款管理

**后台节点**:
- `wechat-client/config/options` - 公众号配置
- `wechat-client/config/payment` - 支付配置
- `wechat-client/fans/index` - 粉丝管理
- `wechat-client/news/index` - 素材管理
- `wechat-client/menu/index` - 菜单管理
- `wechat-client/keys/index` - 关键词回复
- `wechat-client/payment.record/index` - 支付记录

**接口节点**:
- `/api/wechat-client/push/*` - 微信推送
- `/api/wechat-client/view/*` - 预览页
- `/api/wechat-client/js/*` - JS SDK
- `/api/wechat-client/login/*` - 网页授权

**数据表**:
- `wechat_auto` - 自动回复
- `wechat_fans` - 粉丝资料
- `wechat_fans_tags` - 粉丝标签
- `wechat_keys` - 关键字规则
- `wechat_media` - 素材库
- `wechat_news` - 图文主表
- `wechat_news_article` - 图文文章
- `wechat_payment_record` - 支付记录
- `wechat_payment_refund` - 退款记录

**命令**:
```bash
# 同步所有粉丝
php think xadmin:fansall

# 粉丝消息推送
php think xadmin:fansmsg

# 粉丝支付清理
php think xadmin:fanspay
```

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsWorker (推荐)

**许可证**: MIT License

---

### 7. ThinkPlugsWechatService - 微信公众号开放平台

**包名**: `zoujingli/think-plugs-wechat-service`

**定位**: 公众号开放平台组件，负责第三方平台配置、公众号授权管理和远程 JSON-RPC 服务

**核心功能**:
- 微信开放平台参数配置
- 授权公众号管理
- 远程 JSON-RPC 服务
- 推送事件接收
- 标准平台远程能力代理

**后台节点**:
- `wechat-service/config/index` - 开放平台配置
- `wechat-service/wechat/index` - 授权账号管理

**接口节点**:
- `/api/wechat-service/client/jsonrpc` - JSON-RPC 远程调用
- `/api/wechat-service/push/*` - 推送事件接收

**JSON-RPC 地址示例**:
```
http://example.com/api/wechat-service/client/jsonrpc?token=TOKEN
```

**数据表**:
- `wechat_auth` - 微信授权

**命令**:
```bash
# 同步微信数据
php think xsync:wechat
```

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper

**许可证**: 专有授权（未授权不可商用）

---

## 业务插件详细说明

### 8. ThinkPlugsAccount - 多端账号体系

**包名**: `zoujingli/think-plugs-account`

**定位**: 统一账号组件，负责多端账号模型、登录通道注册、账号绑定关系、接口认证令牌和账号资料管理

**核心功能**:
- 多端账号统一管理
- 微信服务号登录
- 微信小程序登录
- 手机短信验证登录
- 主账号与终端账号绑定解绑
- 动态注册登录通道
- 终端资料与主账号资料同步
- Token 化接口认证

**后台节点**:
- `account/master/index` - 主账号管理
- `account/device/index` - 终端账号管理
- `account/message/index` - 消息记录管理

**接口入口**:
- `/api/account/login/*` - 登录接口
- `/api/account/auth/*` - 认证接口
- `/api/account/auth/center/*` - 账号中心
- `/api/account/wechat/*` - 微信登录
- `/api/account/wxapp/*` - 小程序登录

**数据表**:
- `plugin_account_auth` - 授权配置
- `plugin_account_bind` - 终端账号
- `plugin_account_msms` - 短信记录
- `plugin_account_user` - 主账号资料

**使用示例**:
```php
use plugin\account\service\Account;

$account = Account::mk(Account::WXAPP, TOKEN: '');
$user = $account->set(['openid' => 'OPENID', 'phone' => '13888888888']);

$account->set(['extra' => ['desc' => '用户描述', 'sex' => '男']]);
$profile = $account->get(true);

$bound = $account->bind(['phone' => '13999999999'], ['username' => '会员用户']);
$allBind = $account->allBind();
```

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsStorage

**许可证**: 专有授权（VIP 授权）

---

### 9. ThinkPlugsPayment - 支付中心

**包名**: `zoujingli/think-plugs-payment`

**定位**: 支付中心组件，负责支付配置、支付行为、退款处理、余额积分账本和支付事件分发

**核心功能**:
- 支付配置管理
- 混合支付能力
- 余额支付与流水
- 积分支付与流水
- 线下凭证支付审核
- 支付行为追踪
- 退款记录管理
- 高精度金额计算

**后台节点**:
- `payment/config/index` - 支付配置
- `payment/record/index` - 支付记录
- `payment/refund/index` - 退款记录
- `payment/balance/index` - 余额账本
- `payment/integral/index` - 积分账本

**接口节点**:
- `/api/payment/auth/address/*` - 收货地址
- `/api/payment/auth/balance/*` - 余额支付
- `/api/payment/auth/integral/*` - 积分支付

**支付通知路由**:
```
/plugin-payment-notify/:vars
```

**支付事件**:
- `PluginAccountBind` - 账号绑定
- `PluginPaymentAudit` - 支付审核
- `PluginPaymentRefuse` - 支付拒绝
- `PluginPaymentSuccess` - 支付成功
- `PluginPaymentCancel` - 支付取消
- `PluginPaymentConfirm` - 订单确认

**数据表**:
- `plugin_payment_address` - 收货地址
- `plugin_payment_balance` - 余额账本
- `plugin_payment_config` - 支付配置
- `plugin_payment_integral` - 积分账本
- `plugin_payment_record` - 支付记录
- `plugin_payment_refund` - 退款记录

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsAccount
- ThinkPlugsStorage

**许可证**: 专有授权（未授权不可商用）

---

### 10. ThinkPlugsWemall - 分销商城系统

**包名**: `zoujingli/think-plugs-wemall`

**定位**: 分销商城组件，负责商品、订单、售后、会员、分销、海报、报表和商城 API

**核心功能**:
- 商城参数和页面装修
- 商品、分类、规格、库存管理
- 订单、发货、退款、评论管理
- 会员等级、折扣、创建、充值
- 分销关系、返佣、提现
- 海报、通知、报表
- 帮助中心与意见反馈
- 面向客户端的商城 API
- 商城开放平台快递查询适配

**后台节点**:
- `wemall/base.*/*` - 商城配置
- `wemall/user.*/*` - 用户管理
- `wemall/shop.*/*` - 商城管理
- `wemall/help.*/*` - 帮助中心

**接口节点**:
- `/api/wemall/goods/*` - 商品接口
- `/api/wemall/data/*` - 数据接口
- `/api/wemall/auth/*` - 认证接口
- `/api/wemall/help/*` - 帮助接口

**命令**:
```bash
# 清理商城数据
php think xdata:mall:clear

# 商城数据转换
php think xdata:mall:trans

# 商城用户导入
php think xdata:mall:users
```

**事件联动**:
- `PluginAccountBind` - 账号绑定
- `PluginPaymentAudit/Refuse/Success/Cancel/Confirm` - 支付事件
- `PluginWemallOrderConfirm` - 订单确认

**数据表** (主要):
- 商城配置：`plugin_wemall_config_*`
- 快递与模板：`plugin_wemall_express_*`
- 商品：`plugin_wemall_goods*`
- 订单：`plugin_wemall_order*`
- 帮助中心：`plugin_wemall_help_*`
- 用户行为：`plugin_wemall_user_action_*`
- 会员与返佣：`plugin_wemall_user_*`

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsAccount
- ThinkPlugsPayment
- ThinkPlugsStorage

**许可证**: 专有授权（未授权不可商用）

---

### 11. ThinkPlugsWuma - 防伪溯源系统

**包名**: `zoujingli/think-plugs-wuma`

**定位**: 防伪溯源组件，负责一物一码、溯源模板、赋码批次、仓储流转、代理库存和扫码验证

**核心功能**:
- 一物一码规则管理
- 防伪验证与溯源展示
- 生产与赋码批次管理
- 区块链证书与内容管理
- 仓库、入库、出库、库存调度
- 代理库存与调货管理
- 消费者扫码查询与通知分析

**后台节点**:
- `wuma/code/index` - 物码管理
- `wuma/source.*/*` - 溯源管理
- `wuma/warehouse*/*` - 仓储管理
- `wuma/sales.*/*` - 代理销售
- `wuma/scaner.*/*` - 扫码监控

**接口节点**:
- `/api/wuma/base/*` - 基础接口
- `/api/wuma/coder/*` - 编码接口
- `/api/wuma/auth/*` - 认证接口
- `/api/wuma/login/*` - 登录接口

**接口请求头规范**:
```http
Authorization: Bearer <token>
X-Device-Code: <device-code>
X-Device-Type: <device-type>
```

**命令**:
```bash
# 创建物码
php think xdata:wuma:create
```

**数据表** (主要):
- 物码规则：`plugin_wuma_code_rule*`
- 溯源模块：`plugin_wuma_source_*`
- 仓储模块：`plugin_wuma_warehouse*`
- 代理销售：`plugin_wuma_sales_*`

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper
- ThinkPlugsStorage
- ThinkPlugsWemall (可联动)

**许可证**: 专有授权（未授权不可商用）

---

## 交付组件详细说明

### 12. ThinkPlugsBuilder - 打包插件

**包名**: `zoujingli/think-plugs-builder`

**定位**: 标准打包插件，用于将当前项目构建为可直接运行的 `admin.phar`

**核心功能**:
- 使用临时目录中转打包，减少直接扫描项目目录带来的耗时
- 构建时自动整合项目代码、Composer 依赖和运行入口
- 首次启动时自动创建 `public`、`runtime` 等运行目录
- 支持只保留 `.env + admin.phar` 的最小部署形态
- 支持通过 `php admin.phar xadmin:worker ...` 直接运行 Worker

**命令**:
```bash
# 使用推荐脚本构建
composer build:phar

# 或直接执行命令
php -d phar.readonly=0 think xadmin:builder --name=admin.phar
```

**参数说明**:
- `--name`: 输出的 PHAR 文件名（默认 `admin.phar`）
- `--main`: 包内主入口文件（默认 `think`）
- `--extract`: 首次启动时解压到外部目录的路径
- `--mount`: 运行时挂载到 PHAR 外部的文件或目录
- `--exclude`: 额外排除的文件或目录

**部署方式**:
最小部署产物只需要两个文件：
```
release/
├─ .env
└─ admin.phar
```

**运行时自动行为**:
PHAR 首次启动时会自动：
- 如果根目录没有 `.env`，优先用 `.env.example` 自动生成
- 自动创建 `public` 目录
- 自动创建 `runtime` 目录
- 自动同步 `.env` 到 `runtime/.env`
- 如果 `public`、`database` 不存在，则从 PHAR 内解压到外部目录
- 将 `.env`、`runtime`、`safefile`、`public`、`database` 挂载到 PHAR 外部

**路径约定** (PHAR 兼容关键点):
- `syspath()`: 用于读取 **PHAR 包内**资源（只读）
- `runpath()`: 用于读写 **PHAR 外部**文件系统（可写）
- 字体、SQLite、缓存等落盘路径必须使用 `runpath()`
- GD 的 `imagettftext()` 不支持 `phar://` 路径

**依赖**:
- PHP >= 8.1
- Phar 扩展启用

---

### 13. ThinkPlugsStatic - 静态资源与项目骨架

**包名**: `zoujingli/think-plugs-static`

**定位**: 静态资源和项目骨架组件，负责发布后台前端资源、默认入口文件、基础配置模板和项目初始化骨架

**核心功能**:
- 发布 `public/static` 前端资源
- 发布 `public/index.php`、`public/router.php` 等入口文件
- 发布基础配置模板与本地多应用骨架（默认 `index` 应用）
- 后台前端统一使用 `LayUI + $.module.use(...)`
- 不再使用 `RequireJS`

**发布内容**:
主要发布内容包括：
- `.env.example`
- 配置文件：`config/*.php`
- 应用骨架：`app/index/controller/Index.php`
- 路由：`route/.gitkeep`
- 入口文件：`public/index.php`, `public/router.php`
- 静态资源：`public/static/plugs`, `public/static/theme`
- 系统脚本：`public/static/system.js`, `public/static/login.js`
- 自定义扩展：`public/static/extra/style.css`, `public/static/extra/script.js`

**发布命令**:
```bash
# 初始化缺失文件
php think xadmin:publish

# 覆盖更新静态资源和骨架
php think xadmin:publish --force
```

**前端约定**:
- 后台只保留 `LayUI` 模块加载机制
- 统一入口脚本为 `public/static/system.js` 和 `public/static/login.js`
- 自定义脚本建议放到 `public/static/extra`

**说明**:
- 后台认证已改为 Token/JWT，不再依赖 Session 保存登录态
- 用户临时态统一使用基于 Token SID 的 `CacheSession`
- 语言切换默认仅支持 URL 参数与请求头，不再写语言 Cookie

**依赖**:
- PHP >= 8.1
- ThinkLibrary
- ThinkPlugsHelper

**许可证**: MIT License

---

## 架构约定

### 认证与会话

详细规范请参考：[认证与会话规范](./docs/architecture/auth-token-session.md)

**核心原则**:
- 登录态必须由显式 Token 表达
- 临时用户态必须绑定到 Token 对应的 `sid`
- 业务请求统一使用 `Authorization`
- 后台壳页首跳只允许一次性 `access_key` 引导

**认证协议分层**:
- `system-auth`: 后台人员（JWT）
- `account-auth`: 所有业务用户（JWT）
- `device token`: 封闭设备（wuma 独立实现）
- `capability token`: 短期能力授权（上传等）

**统一请求头**:
```http
Authorization: Bearer <token>
X-Device-Code: <device-code>
X-Device-Type: <device-type>
```

**禁止项**:
- 不再使用 `think\Session`
- 不再使用 `think\facade\Session`
- 不再使用 `SessionInit`
- 不再使用 `PHPSESSID`
- 不再使用标准 Session 环境变量作为系统登录态配置

### 路由与应用

- `app/*` 支持本地多应用，`app/index` 为默认本地应用
- 本地应用入口：`/{app}/{controller}/{action}`（默认应用可省略首段）
- 插件通过 URL 前缀注册访问入口
- 请求首段命中插件前缀时切换到插件
- 未命中插件前缀时回退到本地应用
- 动态插件切换默认关闭
- 页面入口统一使用 `/{plugin}/...`
- 接口入口统一使用 `/api/{plugin}/{controller}/{action}`
- 页面链接统一使用 `sysuri()`，接口链接统一使用 `apiuri()`
- 旧 `/{plugin}/api.xxx/...` 只保留兼容，不再作为新代码标准

### PHAR 路径约定

当使用 `ThinkPlugsBuilder` 构建并以 `admin.phar` 方式运行时：

- `syspath()`: 读取 **PHAR 包内**只读资源（如 `public/static`、内置模板/数据）
- `runpath()`: 读写 **PHAR 外部**可写目录（如 `runtime/`、`safefile/`、`database/`、`public/upload/`、Worker 的 pid/log/status/stdout 文件）

更多构建与运行说明请参考：[ThinkPlugsBuilder 文档](./plugin/think-plugs-builder/readme.md)

---

## 开发工具

### PHPStan 静态代码分析

项目已集成 PHPStan 2.0，用于代码质量检查和静态分析。

```bash
# 运行代码分析
composer analyse

# 分析结果无错误时输出：[OK] No errors
```

配置说明：
- 配置文件：`phpstan.neon`
- 检查级别：`level 3`（已开启更多真实异常检查）
- 检查范围：`app/`, `config/`, `plugin/`
- 通过 `scanFiles` 显式加载 ThinkPHP 与项目公共函数
- 排除目录：`runtime/`, `vendor/`

### 代码风格

使用 PHP-CS-Fixer 统一代码风格：

```bash
# 自动修复代码风格
composer sync
```

### 测试

运行所有测试：

```bash
# 运行所有测试
composer test

# 仅运行冒烟测试
composer test:smoke

# 仅运行单元测试
composer test:unit
```

---

## 相关文档

- 官网文档：[thinkadmin.top](https://thinkadmin.top)
- 接口文档：[ThinkAdminMobile](https://thinkadmin.apifox.cn)
- 架构文档：
  - [插件边界](./docs/architecture/plugin-boundaries.md)
  - [插件优先重构](./docs/architecture/plugin-first-refactor.md)
  - [认证与会话](./docs/architecture/auth-token-session.md)
  - [稳定性评估](./docs/architecture/stability-status.md)
  - [插件标准](./docs/architecture/plugin-standard.md)
  - [路由分发标准](./docs/architecture/route-dispatch-standard.md)
  - [软删除标准](./docs/architecture/soft-delete-standard.md)

---

## 注意事项

- 本仓库包含会员插件，未授权不得商用
- 插件卸载通常不会自动删除已执行迁移和历史数据
- 自定义前端脚本建议放在 `public/static/extra`
- PHAR 构建仅在调试模式下可用，生产模式不会暴露构建命令
- 打包前必须先执行 `composer install`，否则缺少 `vendor` 目录无法构建

---

## 许可证

除会员授权插件（Account/Payment/Wemall/Wuma）外，其余组件按各自许可证发布：
- MIT: ThinkLibrary, System, WechatClient, Storage, Static
- Apache-2.0: Worker, Helper, Builder
- 专有授权：Account, Payment, Wemall, Wuma, WechatService
