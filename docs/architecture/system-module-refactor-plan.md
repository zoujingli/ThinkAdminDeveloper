# System Module Refactor Plan

## 目标

- 基于当前 `think-library` 的 `FormBuilder / PageBuilder` 重构 `System` 模块。
- 以控制器注释为 RBAC 权限源头，数据库只保存同步后的角色、节点、菜单和用户授权关系。
- 在重构阶段允许直接调整 `System` 主安装脚本字段，并通过新库重建验证结果。
- 为后续其他插件重构建立统一的数据库脚本命名、Builder 分层和字段命名规范。

## 当前结论

### 1. Builder 体系

- 不恢复旧的动态 Builder 模块。
- 当前 `System` 模块已经在使用新的 `Controller + Service + Builder` 结构。
- 现阶段应该继续复用：
  - `plugin/think-library/src/builder/form/*`
  - `plugin/think-library/src/builder/page/*`
  - `plugin/think-plugs-system/src/builder/*`

### 2. RBAC 体系

- 节点来源于控制器注释扫描。
- 当前约定已经存在：
  - `@auth true` 表示权限节点
  - `@menu true` 表示菜单节点
  - `@login true` 表示仅登录可访问
- 运行时节点解析当前由 `think\admin\service\NodeService` 完成。

### 3. 数据库脚本约束

- `plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php` 是 `System` 主维护源。
- 根目录 `database/migrations/*.php` 视为发布产物，不是主维护源。
- 每个插件只保留一个主安装脚本，不在重构期间增加多份临时 install 脚本。

## 重构范围

### 第一批

- `Auth`
- `User`
- `Menu`

### 第二批

- `Base`
- `Config`
- `File`

### 第三批

- `Queue`
- `Oplog`
- `Plugin`
- `Index`
- `Login`

## 统一设计原则

### 1. Source of Truth

- 权限节点来源：控制器注释
- 插件菜单来源：插件 `composer.json > extra.xadmin.menu`
- 系统菜单来源：数据库中的运行时配置和覆盖
- 角色授权来源：角色与节点关系表
- 用户授权来源：用户与角色关系表

### 2. 分层职责

- `Controller` 只负责输入输出、Builder 响应和异常转换
- `Service` 负责业务编排、同步逻辑、字段适配和约束校验
- `Builder` 负责页面结构、表单结构和前端交互装配
- `Model` 只保留领域对象、关联和必要的属性转换

### 3. 命名原则

- 外键统一使用 `*_id`
- 时间字段统一使用 `create_time / update_time / delete_time`
- 状态字段统一使用 `status`
- 备注字段统一使用 `remark`
- URL/路由字段区分：
  - `route_path` 表示站内路由
  - `link_url` 表示完整外链
- JSON 字段只存结构化数据，不再混存纯文本和结构对象

## 目标数据模型

### Phase 1 落地说明

第一批 `Auth / User / Menu` 当前采用兼容式字段重命名方案，优先保证现有页面、测试和安装脚本能平滑过渡。

第一批优先落地字段：

- `system_auth.code`
- `system_auth.remark`
- `system_user.base_code`
- `system_user.auth_ids`
- `system_user.remark`

第一批暂不强制引入新的用户角色关系表，避免一次性改动过大。`system_user_auth` 保留为第二阶段可选增强。

### 1. system_auth

定位：角色定义表

建议保留表名，调整字段语义。

建议字段：

- `id`
- `code`：角色编码，唯一
- `title`：角色名称
- `remark`：角色说明
- `sort`
- `status`
- `create_time`
- `update_time`

当前问题：

- `utype` 语义不清
- `desc` 命名不统一
- 缺少角色编码字段

处理建议：

- 删除或废弃 `utype`
- `desc` 统一为 `remark`
- 新增 `code`

### 2. system_auth_node

定位：角色与权限节点关系表

建议字段：

- `id`
- `auth_id`
- `node_code`
- `plugin_code`
- `create_time`

当前问题：

- `auth` 命名不清晰
- `node` 缺少领域表达
- 无法直接表达插件归属

处理建议：

- `auth -> auth_id`
- `node -> node_code`
- 增加 `plugin_code`

### 3. system_user

定位：系统后台用户表

建议字段：

- `id`
- `username`
- `password`
- `nickname`
- `avatar`
- `identity_code`
- `contact_phone`
- `contact_mail`
- `contact_qq`
- `login_ip`
- `login_time`
- `login_num`
- `remark`
- `sort`
- `status`
- `create_time`
- `update_time`
- `delete_time`

当前问题：

- `authorize` 用逗号串保存角色编号，扩展性差
- `headimg`、`describe` 为历史命名
- `login_at` 当前是字符串时间
- 缺少 `update_time`

处理建议：

- 删除 `authorize`，改为独立关联表
- `headimg -> avatar`
- `describe -> remark`
- `login_at -> login_time`
- 增加 `update_time`

### 4. system_user_auth

定位：用户与角色关系表

这是第二阶段可选增强表，不作为第一批强制落地点。

建议字段：

- `id`
- `user_id`
- `auth_id`
- `create_time`

价值：

- 替代 `system_user.authorize` 的逗号串模式
- 便于多角色扩展、审计、筛选和后续插件授权复用

### 5. system_menu

定位：系统后台菜单表

建议字段：

- `id`
- `parent_id`
- `plugin_code`
- `source_type`
- `source_code`
- `title`
- `icon`
- `route_path`
- `link_url`
- `auth_node`
- `query_params`
- `target`
- `sort`
- `status`
- `create_time`
- `update_time`

当前问题：

- `pid` 命名是历史风格
- `url` 同时承载站内路由和外链
- `node` 与 `url` 职责交叉
- `params` 只是字符串

处理建议：

- `pid -> parent_id`
- 拆分 `url` 为 `route_path / link_url`
- `node -> auth_node`
- `params -> query_params`
- 增加 `plugin_code / source_type / source_code`

### 6. system_base

定位：系统数据字典表

建议字段：

- `id`
- `type`
- `code`
- `name`
- `text_value`
- `meta_json`
- `sort`
- `status`
- `create_time`
- `update_time`
- `delete_time`
- `deleted_by`

当前问题：

- `content` 同时保存纯文本和 JSON 元数据
- 插件归属通过 `content` 间接表达，不利于约束

处理建议：

- 将内容拆分为文本值和元数据
- 插件归属等扩展信息只进入 `meta_json`

### 7. system_data

定位：系统分组配置表

建议字段：

- `id`
- `name`
- `value`
- `create_time`
- `update_time`

当前结论：

- 这张表结构可以暂时保留
- 重点是确保 `value` 只保存结构化 JSON

### 8. system_file

定位：系统文件与存储记录表

建议字段：

- `id`
- `storage_type`
- `file_hash`
- `tags`
- `file_name`
- `extension`
- `file_url`
- `storage_key`
- `mime_type`
- `file_size`
- `system_user_id`
- `biz_user_id`
- `is_fast_upload`
- `is_safe`
- `status`
- `create_time`
- `update_time`

当前问题：

- `xext / xurl / xkey` 为历史命名
- `uuid / unid` 不利于理解
- 字段语义和业务语义脱节

处理建议：

- 历史字段全部改成业务化命名
- 文件表作为第二批落地

### 9. system_oplog

定位：操作日志表

建议字段：

- `id`
- `node_code`
- `request_ip`
- `action`
- `content`
- `username`
- `create_time`

当前问题：

- `geoip` 实际存的是 IP，不是地理信息

处理建议：

- `geoip -> request_ip`

## RBAC 重构目标

### 1. 节点同步

- 扫描控制器注释
- 生成标准节点树
- 识别插件归属
- 写入角色节点授权关系
- 刷新节点缓存

### 2. 菜单同步

- 插件声明的菜单作为静态源
- `system_menu` 作为运行时覆盖层
- 后台手工新增菜单只作为 `custom` 来源处理

### 3. 权限判断顺序

- 超级管理员直接放行
- 判断登录态
- 判断节点是否需要授权
- 判断用户关联角色
- 判断角色是否命中节点

## Builder 重构目标

### 1. 公共 Builder 资产

- `SystemListPage`
- `SystemListTabs`
- `SystemTablePreset`
- 公共的模块化表单片段

### 2. 第一批要抽离的交互

- 权限树筛选与选择
- 用户角色分组选择
- 菜单路由/权限节点自动补全
- 系统配置中的主题选择和存储驱动选择

### 3. 输出要求

- 保持 HTML 渲染可用
- 保持 Builder JSON 渲染可用
- 避免在模板中继续回退到旧式条件模板和拼接式脚本

## 实施顺序

### Phase 1: 结构冻结

- 输出字段改造清单
- 输出目标表结构
- 输出脚本命名规范
- 确认第一批改造对象为 `Auth / User / Menu`

### Phase 2: 数据库脚本重写

- 直接修改 `System` 主安装脚本
- 第一批先完成兼容式字段替换
- 更新测试里的 SQLite 建表定义
- 使用新库重建验证安装结果

### Phase 3: 模型与 Service 适配

- 更新 `SystemAuth`
- 更新 `SystemUser`
- 更新 `SystemMenu`
- 新增 `SystemUserAuth`
- 把历史字段兼容逻辑收敛到 Service 层

### Phase 4: Builder 与控制器重构

- 先改 `Auth`
- 再改 `User`
- 再改 `Menu`
- 完成角色、用户、菜单三条链路联调

### Phase 5: 第二批模块落地

- 重构 `Base`
- 重构 `Config`
- 重构 `File`

### Phase 6: 收尾

- 清理旧字段兼容逻辑
- 更新文档和测试
- 重新发布 `database/migrations`

## 数据库脚本命名规范

### 主安装脚本

- 目录：`plugin/<plugin>/stc/database/`
- 模式：`{version}_install_{plugin_code}{date}.php`
- `plugin_code` 统一使用 `snake_case`

示例：

- `20241010000001_install_system20241010.php`
- `20241010000009_install_wechat_service20241010.php`

### 当前阶段规则

- `System` 模块允许直接修改主 install 脚本
- 不新增第二份 install 脚本
- 根目录迁移文件通过发布流程重建

## 验收标准

- 新库可直接安装 `System` 模块
- 注释扫描可以生成 RBAC 节点
- 用户、角色、菜单权限关系闭环可用
- `Auth / User / Menu` 页面同时支持 HTML 和 Builder JSON
- 第一批模块测试可通过
- 第二批模块不会继续引入历史字段命名

## 下一步

第一轮实施直接从下面三项开始：

1. 改造 `system_auth / system_auth_node / system_user / system_menu`
2. 更新 `System` 主安装脚本与 SQLite 测试表结构
3. 重做 `Auth / User / Menu` 的 Model、Service、Builder 和控制器适配

## 当前进度

截至当前工作区状态，第一批已经采用以下字段重命名方向推进：

- `system_auth.utype -> code`
- `system_auth.desc -> remark`
- `system_user.usertype -> base_code`
- `system_user.authorize -> auth_ids`
- `system_user.describe -> remark`

并且 `Auth / User / Menu` 相关测试已经可以通过。
