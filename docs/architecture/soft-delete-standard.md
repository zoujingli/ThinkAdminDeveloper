# Soft Delete Standard

## 当前结论

- ORM 标准软删除字段已经统一为 `delete_time`
- `delete_time` 是唯一允许继续新增到数据库脚本里的软删除物理字段
- `deleted` 不再是标准物理字段，只保留为兼容层里的虚拟语义
- `deleted_time` 和 `deleted_at` 不再允许出现在新表结构中，只保留兼容映射
- 不需要软删除的模型，必须显式声明 `protected $deleteTime = false`

## 当前实现

### 1. 模型默认时间字段

- 基类 `think\admin\Model` 默认使用 `create_time` 和 `update_time`
- 软删除启用后，默认目标字段是 `delete_time`

### 2. 软删除生效条件

- 模型使用 `SoftDelete` trait
- 且模型的 `deleteTime` 没有被显式关闭
- 满足这两个条件时，删除应走 ORM 软删除，不再把“删除状态”当成独立业务字段维护

### 3. 当前兼容层

当前基类仍然保留了一层过渡兼容，目的是在重构阶段兜住旧代码：

- 查询兼容：
  - `where(['deleted' => 0])`
  - `where(['deleted' => 1])`
  - `whereNull('deleted_time')`
  - `whereNotNull('deleted_at')`
- 写入兼容：
  - `save(['deleted' => 1])`
  - `save(['deleted' => 0])`
  - `save(['deleted_time' => '2026-01-01 00:00:00'])`
- 输出兼容：
  - 仍会附加虚拟属性 `deleted`
  - `deleted` 的语义是“`delete_time` 是否为空”

这层兼容是为了迁移旧代码，不代表 `deleted` 仍然是标准设计。

### 4. 删除执行路径

- 使用 `DeleteHelper` 删除模型时：
  - 如果模型启用了软删除，则调用模型删除，实际写入 `delete_time`
  - 如果模型显式 `deleteTime = false`，则执行物理删除

## 现在还保留的机制

### 1. 兼容旧字段名

当前代码仍兼容以下旧命名：

- `deleted`
- `deleted_time`
- `deleted_at`

这表示系统仍处于“运行兼容旧写法，但数据库标准已经切到 `delete_time`”的阶段。

### 2. 显式关闭软删除

一部分模型仍然显式使用 `protected $deleteTime = false`，这不是异常，而是当前设计的一部分。

典型场景：

- 纯日志或关系表，不需要回收站
- 业务就是物理覆盖或物理删除
- 表结构根本没有 `delete_time`

因此不能把“所有模型都要有软删除”当成规则，正确规则是：

- 需要软删除：用 `delete_time`
- 不需要软删除：显式关闭

### 3. 业务状态字段

以下字段不是 ORM 软删除字段，不应混淆：

- `deleted_status`
- `deleted_by`
- 任何仅表示业务流程的“作废/取消/失效”状态

这些字段即使名字里带 `deleted`，也不是软删除标准的一部分。

## 当前剩余风险

兼容层已经能覆盖大部分 ORM 调用，但还没有覆盖所有旧写法。

### P1：原始 SQL 条件

下面这类写法不会被 ORM 兼容层自动改写：

```php
whereRaw('deleted=0')
whereRaw("status=0 and deleted=0")
```

这类代码仍然会直接依赖旧物理字段，属于高优先级清理对象。

### P1：显式选择旧字段

下面这类写法同样会直接依赖旧物理字段：

```php
field('code,name,status,deleted')
```

如果表里已经只有 `delete_time`，这类字段选择会直接触发 SQL 报错。

### P2：旧属性名和文档注释

当前仓库里还残留一些旧表述：

- `protected $deleteTime = 'deleted_time'`
- 模型注释里的 `@property string $deleted_time`
- 测试断言直接检查 `deleted_time`

这些不一定马上造成运行错误，但已经和当前数据库标准不一致，应该继续收口。

### P3：无意义的兼容性排除字段

例如：

```php
withoutField('deleted')
```

这类代码在重构后通常只是兼容噪音，不再具有真实结构意义。

## 新代码规范

从现在开始，新增或重构代码应遵守以下规则：

1. 新表软删除字段只能使用 `delete_time datetime null`
2. 新模型如启用软删除，只声明 `deleteTime = 'delete_time'` 或直接使用基类默认值
3. 新代码禁止继续写 `deleted`、`deleted_time`、`deleted_at` 作为物理字段
4. 禁止新增 `whereRaw('deleted=0')` 这类原始条件
5. 禁止新增 `field(...deleted...)` 这类旧字段选择
6. 业务状态和软删除必须分开设计，不能再用 `deleted` 充当双重语义
7. 不需要软删除的模型必须显式 `deleteTime = false`

## 推荐清理顺序

### 第一阶段

- 先清理 `whereRaw('deleted=0')`
- 再清理 `field(...deleted...)`
- 最后清理 `withoutField('deleted')`

### 第二阶段

- 把模型里的 `deleted_time` 注释和属性配置改成 `delete_time`
- 把测试断言从 `deleted_time` 统一到 `delete_time`

### 第三阶段

- 当旧写法全部移除后，再考虑缩减基类兼容逻辑

在此之前，兼容层仍然保留，用来保护重构中的存量代码。
