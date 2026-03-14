# Auth And Token Session

## 目标

当前项目的认证体系只保留“显式 Token + Token Session”这一套主线，不再使用标准 PHP Session 承载登录态。

统一原则：

- 登录态必须由显式 Token 表达
- 临时用户态必须绑定到 Token 对应的 `sid`
- 业务请求统一使用 `Authorization`
- 后台壳页首跳只允许一次性 `access_key` 引导，不长期在 URL 或 Cookie 中保留认证态
- 登录来源可以有多种，但认证协议尽量收敛

## 标准认证协议

### 1. 后台认证

- 类型：`system-auth`
- 载体：`Authorization: Bearer <JWT>`
- 首屏壳页：允许一次性 `access_key`
- 会话：JWT 内的 `sid` 绑定 `CacheSession`

适用场景：

- 后台运营人员
- 管理员
- 需要 RBAC 的内部接口

### 2. 业务账号认证

- 类型：`account-auth`
- 载体：`Authorization: Bearer <JWT>`
- 会话：JWT 内的 `sid` 绑定 `CacheSession`

适用场景：

- 会员
- 分销员
- 商户
- 店员
- 任何“业务用户”身份

注意：

- `wap / web / wxapp / wechat / iosapp / android` 是登录来源或终端通道
- 它们不是独立的认证协议

### 3. 设备认证

- 当前仅 `wuma` 保留独立设备 token
- 载体：`Authorization: Bearer <token>`
- 附加头：`X-Device-Code`、`X-Device-Type`
- 这不是 JWT，也不是通用账号协议

适用场景：

- PDA
- 扫码枪
- 仓库终端
- 封闭设备接口

### 4. 能力令牌

- 当前后台上传使用短期能力 token
- 它不是登录态，不参与通用用户会话

适用场景：

- 上传授权
- 短时能力下放

## 请求识别规则

统一入口为请求级令牌解析服务：

- 有 `Authorization` 时，只按请求头判定
- 后台壳页首次进入时，可通过一次性 `access_key` 引导写入本地 Token
- 不再依赖认证 Cookie 回退
- 不再混用 `Api-Token`、`Api-Code`、`Api-Type`

统一请求头：

```http
Authorization: Bearer <token>
X-Device-Code: <device-code>
X-Device-Type: <device-type>
```

## Token Session 规则

### 核心定义

- Token 表达“你是谁”
- Token Session 表达“这次登录上下文里的临时状态”
- Token Session 的主键是 `sid`

### 存储实现

- 服务：`CacheSession`
- 统一入口：`tsession()`
- 存储后端：系统 `cache`
- 支持：`file`、`redis` 等缓存驱动

### 配置键

- `token_session_expire`
- `token_session_touch`
- `token_session_gc_interval`
- `token_session_store`

### 使用约束

- 默认作用域必须来自当前 Token 的 `sid`
- 未完成 Token 鉴权时，不能直接依赖默认作用域
- 如需离线或系统级缓存，必须显式传入 `scope`

### 推荐用法

```php
tsession()->write('draft', ['step' => 1]);
$draft = tsession()->read('draft', []);

tsession()->put(['from' => 'order']);
$all = tsession()->all();

tsession()->delete('draft');
tsession()->clear();
tsession()->destroy();
```

## 禁止项

- 不再使用 `think\Session`
- 不再使用 `think\\facade\\Session`
- 不再使用 `SessionInit`
- 不再使用 `PHPSESSID`
- 不再使用标准 Session 环境变量作为系统登录态配置

## 推荐分层

如果系统存在多类账号，建议按下面方式分层：

- `system-auth`：后台人员
- `account-auth`：所有业务用户
- `device token`：封闭设备
- `capability token`：短期能力授权

不要按每个终端来源都拆一套新的认证协议。
