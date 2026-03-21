# ThinkPlugsSystem

系统后台插件，提供后台壳层、认证权限、菜单用户和系统运维能力。

## 功能定位

- 后台登录、首页、权限菜单、用户管理
- 系统配置、字典管理、扩展数据
- 操作日志、系统任务管理
- 持有 `system_*` 核心数据表

## 安装

```bash
composer require zoujingli/think-plugs-system
```

## 配置

在 `composer.json` 中注册:

```json
{
  "extra": {
    "think": {
      "services": ["plugin\\system\\Service"]
    },
    "xadmin": {
      "app": {
        "code": "system",
        "prefix": "system"
      },
      "menu": { ... },
      "migrate": { ... }
    }
  }
}
```

## 数据表

### 核心表

- `system_base` - 系统基础配置（数据字典）
- `system_config` - 系统参数配置
- `system_data` - 系统扩展数据
- `system_oplog` - 系统操作日志

### 权限表

- `system_auth` - 系统权限角色
- `system_auth_node` - 权限节点绑定
- `system_menu` - 系统菜单管理
- `system_user` - 系统用户管理

## 核心功能

### 1. 后台壳层

- 登录页面
- 首页框架
- 后台布局
- 面包屑导航

### 2. 认证授权

- JWT 令牌认证
- RBAC 权限控制
- 菜单权限判断
- 节点权限验证

### 3. 系统用户

- 用户管理（增删改查）
- 角色管理
- 权限分配
- 密码修改

### 4. 系统配置

- 参数配置（分组管理）
- 配置缓存
- 配置导入导出
- 字典管理

### 5. 操作日志

- 操作记录自动记录
- 日志查询筛选
- 日志导出
- 日志清理

### 6. 任务管理

- 系统任务查看
- 任务状态管理
- 任务日志查看

## 目录结构

```
think-plugs-system/
├── src/
│   ├── controller/
│   │   ├── Auth.php         # 权限管理
│   │   ├── Base.php         # 基础数据
│   │   ├── Builder.php      # 页面构建
│   │   ├── Config.php       # 系统配置
│   │   ├── Index.php        # 首页
│   │   ├── Login.php        # 登录
│   │   ├── Menu.php         # 菜单管理
│   │   ├── Oplog.php        # 操作日志
│   │   ├── Plugs.php        # 插件管理
│   │   ├── Queue.php        # 任务管理
│   │   ├── Upload.php       # 文件上传
│   │   └── User.php         # 用户管理
│   ├── lang/
│   │   └── zh-cn.php       # 中文语言包
│   ├── middleware/
│   │   ├── JwtTokenAuth.php # JWT 认证中间件
│   │   └── RbacAccess.php   # RBAC 权限中间件
│   ├── model/
│   │   ├── SystemAuth.php
│   │   ├── SystemBase.php
│   │   ├── SystemConfig.php
│   │   ├── SystemMenu.php
│   │   ├── SystemOplog.php
│   │   ├── SystemUser.php
│   │   └── ...
│   ├── route/
│   │   └── route.php       # 路由配置
│   ├── service/
│   │   ├── CaptchaService.php      # 验证码服务
│   │   ├── SystemAuthService.php   # 权限认证服务
│   │   ├── SystemContext.php       # 系统上下文
│   │   ├── SystemService.php       # 系统服务
│   │   └── UserService.php         # 用户服务
│   └── view/
│       ├── index/
│       │   └── index.html         # 首页模板
│       ├── login/
│       │   └── index.html         # 登录模板
│       └── public/
│           └── ...                # 公共模板
├── stc/
│   └── database/
│       └── 20241010000001_install_system20241010.php
└── tests/
```

## 使用示例

### 控制器示例

```php
<?php
namespace plugin\system\controller;

use think\admin\Controller;
use think\admin\helper\QueryHelper;

class Config extends Controller
{
    /**
     * 系统配置列表
     */
    public function index()
    {
        $this->title = '系统配置管理';
        
        // 构建查询
        QueryHelper::init(\plugin\system\model\SystemConfig::class)
            ->addWhere($this->request->get('group'), 'group')
            ->addFilter($this->request->get('status'), 'status')
            ->order('sort', 'ASC')
            ->page($this->request->get('page', 1), 20)
            ->withPage(true, true);
    }
    
    /**
     * 添加配置
     */
    public function add()
    {
        $this->_applyDataTip([]);
        
        if ($this->request->isPost()) {
            $data = $this->_vali([
                'name.require' => '配置名称不能为空',
                'unique' => '配置已存在'
            ], 'post');
            
            // 保存配置
            \plugin\system\model\SystemConfig::create($data);
            $this->success('添加成功！');
        }
        
        return $this->fetch();
    }
    
    /**
     * 编辑配置
     */
    public function edit($id)
    {
        $this->_applyDataTip([]);
        
        $data = \plugin\system\model\SystemConfig::findOrEmpty($id);
        if (!$data->isExists()) {
            $this->error('配置不存在！');
        }
        
        if ($this->request->isPost()) {
            $update = $this->_vali([
                'name.require' => '配置名称不能为空'
            ], 'post');
            
            $data->save($update);
            $this->success('修改成功！');
        }
        
        $this->assign('data', $data);
        return $this->fetch();
    }
}
```

### 服务示例

```php
<?php
namespace plugin\system\service;

use think\admin\Service;

class SystemService extends Service
{
    /**
     * 获取系统配置
     */
    public static function config(string $name, $default = null)
    {
        $config = \plugin\system\model\SystemConfig::where('name', $name)->findOrEmpty();
        return $config->isExists() ? $config->value : $default;
    }
    
    /**
     * 设置系统配置
     */
    public static function setConfig(string $name, $value): void
    {
        $config = \plugin\system\model\SystemConfig::where('name', $name)->findOrEmpty();
        if ($config->isExists()) {
            $config->value = $value;
            $config->save();
        } else {
            \plugin\system\model\SystemConfig::create([
                'name' => $name,
                'value' => $value
            ]);
        }
    }
    
    /**
     * 清理配置缓存
     */
    public static function clearCache(): void
    {
        cache('system_config', null);
    }
}
```

### 中间件示例

```php
<?php
namespace plugin\system\middleware;

use Closure;
use think\admin\service\JwtToken;
use think\Request;

class JwtTokenAuth
{
    /**
     * JWT 令牌认证中间件
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('Authorization');
        
        if (empty($token)) {
            return json(['code' => 0, 'info' => '请先登录'])->code(401);
        }
        
        // 移除 Bearer 前缀
        $token = str_replace('Bearer ', '', $token);
        
        try {
            $data = JwtToken::verify($token);
            // 将用户信息存入请求
            $request->user = $data;
        } catch (\Exception $e) {
            return json(['code' => 0, 'info' => '登录已过期'])->code(401);
        }
        
        return $next($request);
    }
}
```

## 菜单配置

菜单在 `composer.json` 中配置:

```json
{
  "menu": {
    "show": true,
    "root": {
      "name": "系统管理",
      "sort": 100
    },
    "items": [
      {
        "name": "系统配置",
        "subs": [
          {
            "name": "系统参数配置",
            "icon": "layui-icon layui-icon-set",
            "node": "system/config/index"
          },
          {
            "name": "动态页面构建",
            "icon": "layui-icon layui-icon-template-1",
            "node": "system/builder/index"
          },
          {
            "name": "系统菜单管理",
            "icon": "layui-icon layui-icon-layouts",
            "node": "system/menu/index"
          }
        ]
      },
      {
        "name": "系统数据",
        "subs": [
          {
            "name": "系统任务管理",
            "icon": "layui-icon layui-icon-log",
            "node": "system/queue/index"
          },
          {
            "name": "系统日志管理",
            "icon": "layui-icon layui-icon-form",
            "node": "system/oplog/index"
          },
          {
            "name": "数据字典管理",
            "icon": "layui-icon layui-icon-code-circle",
            "node": "system/base/index"
          }
        ]
      },
      {
        "name": "权限管理",
        "subs": [
          {
            "name": "系统权限管理",
            "icon": "layui-icon layui-icon-vercode",
            "node": "system/auth/index"
          },
          {
            "name": "系统用户管理",
            "icon": "layui-icon layui-icon-username",
            "node": "system/user/index"
          }
        ]
      }
    ]
  }
}
```

## API 接口

### 系统配置接口

```
GET  /api/system/config/index    # 获取配置列表
POST /api/system/config/add      # 添加配置
POST /api/system/config/edit     # 编辑配置
POST /api/system/config/del      # 删除配置
```

### 系统用户接口

```
GET  /api/system/user/index      # 获取用户列表
POST /api/system/user/add        # 添加用户
POST /api/system/user/edit       # 编辑用户
POST /api/system/user/del        # 删除用户
POST /api/system/user/passwd     # 修改密码
```

### 权限管理接口

```
GET  /api/system/auth/index      # 获取权限列表
POST /api/system/auth/add        # 添加权限
POST /api/system/auth/edit       # 编辑权限
POST /api/system/auth/del        # 删除权限
POST /api/system/auth/apply      # 应用权限
```

## 依赖

- PHP >= 8.1
- ThinkPHP >= 8.1
- ThinkLibrary
- ThinkPlugsStatic
- ThinkPlugsStorage
- ThinkPlugsWorker

## 开发规范

### 命名空间

所有类使用 `plugin\system\` 命名空间

### 目录规范

- 控制器：`controller/`
- 模型：`model/`
- 服务：`service/`
- 视图：`view/`
- 中间件：`middleware/`
- 路由：`route/`

### 代码风格

遵循 PSR-12 规范，使用 PHP-CS-Fixer 统一代码风格

## 测试

运行单元测试:

```bash
vendor/bin/phpunit --testsuite ThinkPlugsSystem
```

## 许可证

MIT License

## 相关链接

- 官网文档：https://thinkadmin.top/plugin/think-plugs-system.html
- Gitee: https://gitee.com/zoujingli/ThinkAdmin
- Github: https://github.com/zoujingli/ThinkAdmin
