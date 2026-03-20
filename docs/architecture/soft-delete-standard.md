# Soft Delete Standard

## 当前结论

- ORM 软删除字段统一为 `delete_time`
- `delete_time` 是唯一允许继续新增到数据库脚本里的软删除物理字段
- `deleted`、`deleted_time`、`deleted_at` 不再作为物理字段或兼容迁移保留
- 不需要软删除的模型，不应再通过 `deleteTime = false` 反向关闭，而应直接使用不带 `SoftDelete` 的普通基类

## 当前实现

### 1. 模型默认时间字段

- 基类 `think\admin\Model` 默认使用 `create_time` 和 `update_time`
- 软删除模型统一使用 `SoftDelete` trait，并把目标字段固定为 `delete_time`

### 2. 删除执行路径

- 使用 `DeleteHelper` 删除模型时：
  - 模型启用了 `SoftDelete`，则写入 `delete_time`
  - 模型未启用 `SoftDelete`，则执行物理删除

### 3. 基类约束

- 软删除模型应继承带 `SoftDelete` 的业务基类
- 非软删除模型应继承普通业务基类
- 不再推荐在同一个基类体系里通过 `deleteTime = false` 做例外开关

## 禁止事项

以下写法不再允许继续引入：

- `deleted`
- `deleted_time`
- `deleted_at`
- `whereRaw('deleted=0')`
- `field(...deleted...)`
- `withoutField('deleted')`

这些写法都会把代码重新绑回旧字段语义。

## 新代码规范

1. 新表需要软删除时，只能使用 `delete_time datetime null`
2. 新模型需要软删除时，使用统一软删除基类或显式 `SoftDelete`
3. 新模型不需要软删除时，直接使用普通基类，不再写 `deleteTime = false`
4. 业务状态和软删除必须分开设计，不能混用“作废/取消/失效”字段代替软删除
5. 数据库脚本、模型注释、测试断言都必须统一使用 `delete_time`

## 清理结果

- 历史兼容软删除迁移已经退化为空迁移占位，不再执行任何字段兼容逻辑
- 软删除与物理删除模型已经按基类分流
- 后续如果再出现旧字段名或 `deleteTime = false`，应视为回归
