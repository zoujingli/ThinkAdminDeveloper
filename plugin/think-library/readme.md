# ThinkLibrary

核心基础库，为 ThinkAdmin 提供运行时基础设施。

## 功能定位

- 提供运行时服务、认证会话、路由适配、队列契约等核心能力
- 定义标准控制器、模型、命令等基础类型
- 提供 Helper 工具集（查询、表单、页面构建器）
- 实现 JWT 令牌、CacheSession 等认证机制
- 提供 Storage 门面和标准契约

## 安装

```bash
composer require zoujingli/think-library
```

## 配置

在 `composer.json` 中注册服务:

```json
{
  "extra": {
    "think": {
      "services": ["think\\admin\\Library"]
    }
  }
}
```

## 核心功能

### 1. 运行时服务

- **RuntimeService**: 运行时环境配置同步，处理 PHAR 兼容、目录初始化
- **AppService**: 应用管理、插件发现、服务注册、配置管理
- **NodeService**: 节点管理、权限判断、菜单节点处理
- **QueueService**: 队列门面服务（真实实现在 Worker 插件）

### 2. 认证会话

- **JwtToken**: JWT 令牌生成、验证、刷新
- **CacheSession**: 基于 Token SID 的缓存会话管理
- **RequestTokenService**: 请求级令牌识别服务
- **SystemContext**: 系统上下文接口（运行时实现）
- **NullSystemContext**: 空实现上下文（未认证状态）

### 3. 路由适配

- **Route**: 自定义路由对象，支持插件路由注册
- **Url**: URL 构建工具，支持插件 URL 生成
- **MultAccess**: 多应用访问中间件，处理插件前缀切换

### 4. 基础类型

- **Controller**: 标准控制器基类，提供通用控制器方法
- **Model**: 标准模型基类，扩展软删除、时间戳等能力
- **Command**: 标准命令基类，提供命令通用方法
- **Plugin**: 插件管理类，处理插件元数据加载
- **Service**: 服务基类，提供基础服务方法
- **Exception**: 框架异常类，统一异常处理

### 5. Helper 工具

- **QueryHelper**: 数据查询构建器，支持分页、筛选、排序
- **FormBuilder**: 表单构建器，支持表单元素快速生成
- **PageBuilder**: 列表页面构建器，支持表格、筛选、操作列
- **ValidateHelper**: 数据验证器，支持规则验证、错误提示

### 6. 扩展工具

- **CodeToolkit**: 编码工具（加密/解密/Base64/Hash）
- **FileTools**: 文件操作工具（目录创建、文件复制、权限管理）
- **HttpClient**: HTTP 客户端（cURL 封装、请求构建）
- **ArrayTree**: 数组树工具（树形结构构建、扁平化）
- **FaviconBuilder**: 网站图标生成器
- **ImageSliderVerify**: 图片滑块验证码
- **JsonRpcHttpClient**: JSON-RPC HTTP 客户端
- **JsonRpcHttpServer**: JSON-RPC HTTP 服务端

## 使用示例

### JWT 令牌

```php
use think\admin\service\JwtToken;

// 生成令牌
$token = JwtToken::token([
    'user_id' => 1,
    'username' => 'admin',
    'exp' => time() + 7200
]);

// 验证令牌
try {
    $data = JwtToken::verify($token);
    // $data['user_id'], $data['username']
} catch (\think\admin\Exception $e) {
    // 验证失败
}
```

### CacheSession

```php
use think\admin\service\CacheSession;

// 写入会话
CacheSession::set('key', 'value', 3600);

// 读取会话
$value = CacheSession::get('key', 'default');

// 批量写入
CacheSession::put([
    'key1' => 'value1',
    'key2' => 'value2'
], 3600);

// 删除会话
CacheSession::delete('key');

// 清空会话
CacheSession::clear();
```

### 控制器基类

```php
<?php
namespace app\index\controller;

use think\admin\Controller;

class Index extends Controller
{
    public function index()
    {
        // 返回成功
        $this->success('操作成功', ['id' => 1]);
        
        // 返回失败
        $this->error('操作失败');
        
        // 返回视图
        $this->fetch('index/index', ['title' => '首页']);
        
        // 数据验证
        $data = $this->_vali([
            'username.require' => '用户名不能为空',
            'email.email' => '邮箱格式不正确'
        ], 'post');
        
        // 创建异步任务
        $this->_queue('导出数据', 'php think export:users');
    }
}
```

### 查询构建器

```php
use think\admin\helper\QueryHelper;

// 基础查询
$query = QueryHelper::init(User::class)
    ->where('status', 1)
    ->order('id', 'DESC')
    ->paginate(20);

// 分页查询
$page = $query->getPage();
$list = $query->getList();

// 条件筛选
$query->addWhere($request->get('keyword'), 'username', 'like');
$query->addFilter($request->get('status'), 'status');
```

### 表单构建器

```php
use think\admin\helper\FormBuilder;

$form = FormBuilder::create();

// 文本输入
$form->text('username', '用户名')
    ->required()
    ->placeholder('请输入用户名');

// 下拉选择
$form->select('status', '状态')
    ->options([1 => '正常', 0 => '禁用'])
    ->default(1);

// 日期选择
$form->date('birthday', '生日');

// 文件上传
$form->file('avatar', '头像')
    ->image()
    ->size(2); // 限制 2MB

// 保存数据
if ($form->isPost()) {
    $data = $form->getData();
    // 保存逻辑
}
```

## 目录结构

```
think-library/
├── src/
│   ├── contract/        # 标准契约接口
│   │   ├── SystemContextInterface.php
│   │   └── ...
│   ├── extend/          # 扩展工具类
│   │   ├── ArrayTree.php
│   │   ├── CodeToolkit.php
│   │   ├── FileTools.php
│   │   └── HttpClient.php
│   ├── helper/          # 构建器工具
│   │   ├── FormBuilder.php
│   │   ├── PageBuilder.php
│   │   ├── QueryHelper.php
│   │   └── ValidateHelper.php
│   ├── middleware/      # 中间件
│   │   └── MultAccess.php
│   ├── model/           # 模型扩展
│   │   └── QueryFactory.php
│   ├── route/           # 路由相关
│   │   ├── Route.php
│   │   └── Url.php
│   ├── runtime/         # 运行时上下文
│   │   ├── RequestContext.php
│   │   ├── RequestTokenService.php
│   │   ├── SystemContext.php
│   │   └── NullSystemContext.php
│   ├── service/         # 核心服务
│   │   ├── AppService.php
│   │   ├── CacheSession.php
│   │   ├── JwtToken.php
│   │   ├── NodeService.php
│   │   ├── QueueService.php
│   │   └── RuntimeService.php
│   ├── common.php       # 全局函数
│   ├── Controller.php   # 标准控制器
│   ├── Model.php        # 标准模型
│   ├── Command.php      # 标准命令
│   ├── Plugin.php       # 插件管理
│   ├── Service.php      # 服务基类
│   ├── Library.php      # 服务注册类
│   └── Exception.php    # 异常类
└── tests/               # 单元测试
```

## 全局函数

ThinkLibrary 提供以下全局函数:

### 全局函数

ThinkLibrary 提供以下全局函数:

#### 应用相关

- `syspath($path)`: 获取系统根目录路径（PHAR 环境下返回包内路径）
- `runpath($path)`: 获取运行时目录路径（PHAR 环境下返回外部可写目录）
- `sysconf($name, $default = null)`: 读取系统配置
- `sysvar($key, $value = null)`: 读写系统变量
- `isOnline()`: 判断是否生产环境（非调试模式）

#### URL 相关

- `sysuri($node, $vars = [], $suffix = true)`: 生成系统 URL（后台页面）
- `apiuri($node, $vars = [], $suffix = true)`: 生成 API URL（接口调用）
- `plguri($node, $vars = [], $suffix = true)`: 生成插件工作台 URL（由 ThinkPlugsSystem 提供）

#### 认证相关

- `auth($node)`: 检查权限（判断当前用户是否有指定节点权限）
- `admin_user()`: 获取当前管理员信息（已认证的系统用户）
- `tsession($name = null, $default = null)`: 读写 Token 会话（基于 CacheSession）

#### 工具函数

- `xss_safe($str)`: XSS 安全过滤（过滤危险 HTML/JS 标签）
- `str2arr($str)`: 字符串转数组（支持逗号、分号、换行分隔）
- `arr2str($arr)`: 数组转字符串（逗号连接）
- `data_save($dbQuery, $data, $key = 'id', $where = [])`: 数据保存（新增或更新）
- `normalize($text)`: 文本标准化（全角转半角、统一空格等）

## 依赖要求

- PHP >= 8.1
- ThinkPHP >= 8.1
- 扩展：gd, curl, json, zlib, iconv, openssl, mbstring, fileinfo
- 推荐：redis (用于 CacheSession 和缓存驱动)

## 开发规范

### 命名空间

所有类使用 `think\admin\` 命名空间

### 代码风格

遵循 PSR-12 规范，使用 PHP-CS-Fixer 统一代码风格

```bash
# 运行代码风格修复
vendor/bin/php-cs-fixer fix
```

### 测试规范

每个核心功能都应该有对应的单元测试

```bash
# 运行 ThinkLibrary 测试
vendor/bin/phpunit plugin/think-library/tests/
```

### 静态分析

使用 PHPStan 进行静态代码分析

```bash
# 运行代码分析
composer analyse
```

## 认证说明

### JWT 令牌

- 后台认证统一使用 `Authorization: Bearer <JWT>`
- JWT 有效期由 `config/app.php` 中的 `system_token_expire` 配置
- JWT 内包含 `sid` 用于绑定 CacheSession
- 不再使用标准 PHP Session 承载后台登录态

### Token Session

- 基于 `CacheSession` 实现
- 统一入口为 `tsession()` 函数
- 支持 file 和 redis 等多种缓存驱动
- 临时用户态绑定到 Token 的 `sid`

## PHAR 兼容性

当使用 PHAR 打包运行时：

- `syspath()` 返回 PHAR 包内路径（只读资源）
- `runpath()` 返回 PHAR 外部路径（可写目录）
- 字体、SQLite、缓存等落盘操作必须使用 `runpath()`
- GD 的 `imagettftext()` 不支持 `phar://` 路径

## 测试覆盖

当前测试覆盖包括：

- 代码加密解密工具测试
- JWT 令牌生成验证测试
- 通用函数测试
- 架构边界测试
- 插件依赖边界测试
- 迁移归属测试
- 表单/页面构建器测试
- 请求令牌服务测试
- 多应用访问调度测试

## 许可证

MIT License

## 相关链接

- 官网文档：https://thinkadmin.top
- Gitee: https://gitee.com/zoujingli/ThinkLibrary
- Github: https://github.com/zoujingli/ThinkLibrary
