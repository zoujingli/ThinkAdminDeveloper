# Plugin Standard

## 当前结论
- `app/*` 负责本地多应用与过渡代码。
- `plugin/*` 负责插件业务实现。
- 插件分发优先于本地应用分发。
- 默认本地应用为 `app/index`。

插件标准由以下部分共同约束：
- `think\admin\Plugin`
- `think\admin\service\AppService`
- `plugin\helper\plugin\PluginMenuService`
- `PhinxExtend`
- `composer.json > extra.xadmin`
- 架构边界与安装边界测试

## 插件包标准

### 1. Composer 类型
- 统一使用 `think-admin-plugin`。

### 2. 服务发现
- 必须通过 `extra.think.services` 注册插件服务类。
- 标准服务类命名为 `plugin\\{name}\\Service`。

### 3. 元数据
- `extra.xadmin.app` 只描述应用级元数据。
  必填：`code`、`name`
  可选：`prefix / prefixes / alias / space / document / description / platforms / license / icon / cover / super`
- `version`、`homepage` 等标准包字段继续使用 Composer 顶层定义，不再放到 `extra.xadmin.app`。
- `extra.xadmin.menu` 描述菜单显示、根节点、菜单项与存在性检测。
- `extra.xadmin.migrate` 描述主迁移脚本信息。

最小 `composer.json` 骨架：

```json
{
  "type": "think-admin-plugin",
  "name": "vendor/demo-plugin",
  "description": "Demo Plugin for ThinkAdmin",
  "autoload": {
    "psr-4": {
      "plugin\\\\demo\\\\": "src"
    }
  },
  "extra": {
    "think": {
      "services": [
        "plugin\\\\demo\\\\Service"
      ]
    },
    "xadmin": {
      "app": {
        "code": "demo",
        "name": "演示插件",
        "prefix": "demo"
      }
    }
  }
}
```

## 目录标准

### 1. 插件根目录
- `composer.json`
- `src`
- `stc`
- `tests`

### 2. `src` 根目录
- 必须保留 `Service.php`。
- 只有确实需要全局函数时才保留 `common.php`。
- 不再新增职责模糊的根级文件。

### 3. 常用子目录
- `controller`
- `model`
- `service`
- `view`
- `command`
- `lang`
- `worker`
- `tests`

## 运行时标准

### 1. 路由优先级
1. 先按插件前缀匹配。
2. 再按本地应用首段匹配。
3. 再按根目录全局路由目标声明匹配。
4. 再按动态插件切换匹配。
5. 最后回退到默认本地应用。

### 2. 标准入口
- 本地应用页面：`/{app}/{controller}/{action}`
- 插件页面：`/{plugin}/...`
- 插件接口：`/api/{plugin}/{controller}/{action}`

### 3. 根路由声明
- 优先使用 `Route::bindApp()` 与 `Route::bindPlugin()`。
- 分组场景使用 `Route::appGroup()` 与 `Route::pluginGroup()`。
- 根路由里的目标地址必须写成目标应用内部的相对节点。

### 4. 动态切换
- `_plugin` 与 `X-Plugin-App` 仅作为调试入口。
- 默认关闭，不作为正式业务路由依赖。

## 插件服务标准

### 1. 基类
- 所有插件服务统一继承 `think\admin\Plugin`。

### 2. 职责
- 注册插件运行时能力。
- 暴露插件元数据。
- 提供菜单定义。
- 处理必要的中间件或路由挂载。

### 3. 非职责
- 不在服务类中堆放业务逻辑。
- 不在服务类中维护大量兼容桥接代码。

## 菜单与权限标准
- 菜单数据统一来自 `composer.json > extra.xadmin.menu.items`。
- 菜单引用的控制器节点必须真实存在。
- 叶子节点必须同时声明 `@auth true` 与 `@menu true`。
- 菜单写入前必须经过 `PluginMenuService::assertMenus()` 校验。

## 数据库与发布标准
- 每个插件只保留一份最终安装迁移脚本。
- 主迁移脚本以插件内 `stc/database` 为准。
- 根目录 `database/migrations` 视为发布产物，不是主维护源。
- 共享表必须遵守固定归属，不允许多插件重复持有。

## 依赖标准
- 本地插件依赖统一走 Composer `path` repository。
- 依赖图必须尽量保持无环。
- `ThinkPlugsSystem` 可以依赖 `library / static / storage / worker`。
- 业务插件不应重新把共享基础能力耦回 `system` 或 `library`。

## 测试要求
- 新增插件必须通过安装边界、路由边界、目录边界和迁移边界测试。
- 菜单、入口、迁移和 URL 规则必须有最小回归覆盖。

## 新插件准入清单
1. 提供独立 `composer.json`。
2. 提供 `src/Service.php`。
3. 在 `extra.xadmin.app` 中声明 `code / prefix / name`。
4. 在 `extra.xadmin.menu` 中声明菜单元数据。
5. 在 `stc/database` 中保留唯一主迁移脚本。
6. 补齐最小安装与路由测试。
