# 插件中心路由更新说明

## 更新概述

已将插件中心（ThinkPlugsCenter）的访问前缀从 `plugin-center` 更新为 `center`，以符合插件编码命名规范。

## 变更内容

### 1. 插件编码配置
- **文件**: `plugin/think-plugs-center/composer.json`
- **变更**: 
  - `code`: `plugin-center` → `center`
  - `prefix`: `plugin-center` → `center`

### 2. 菜单路由节点
- **文件**: `plugin/think-plugs-center/composer.json`
- **变更**:
  - 根节点：`plugin-center/index/index` → `center/index/index`
  - 检测条件：`plugin-center/index/index` → `center/index/index`

### 3. 代码引用
- **文件**: `plugin/think-plugs-center/src/controller/Layout.php`
- **变更**: 2 处 node 引用更新为 `center/index/index`

### 4. 文档更新
已更新以下文档中的路由说明：
- ✅ `plugin/think-plugs-center/readme.md`
- ✅ `readme.md` (主文档)
- ✅ `docs/architecture/plugins-detail.md`

### 5. 测试用例
- **文件**: `plugin/think-library/tests/CommonFunctionsTest.php`
- **变更**: 7 处测试断言更新

## 访问地址变更

### 新的访问地址
- **插件中心首页**: `#/center.html`
- **强制查看列表**: `#/center.html?from=force`
- **插件布局页**: `#/center/layout?encode=...`

### 旧的访问地址（已废弃）
- ~~`#/plugin-center.html`~~
- ~~`#/plugin-center.html?from=force`~~
- ~~`#/plugin-center/layout?encode=...`~~

## 对用户的影响

### 需要更新的行为
1. **浏览器书签**: 需要更新为新的地址 `#/center.html`
2. **直接访问**: 访问旧地址可能会显示 404 或无法访问
3. **API 调用**: 如有外部系统调用，需要更新路由地址

### 系统内部兼容性
- ✅ 系统内部跳转已自动使用新地址
- ✅ `sysuri()` 函数会生成正确的 `center` 前缀 URL
- ✅ 插件编码已统一为 `center`

## 迁移建议

### 如果用户访问旧地址
1. **清除浏览器缓存**: 某些浏览器可能缓存了旧的路由
2. **更新书签**: 手动更新浏览器书签为新地址
3. **重新登录**: 清除会话后重新登录系统

### 开发者注意事项
- 在代码中使用 `sysuri('center/index/index')` 而不是硬编码 URL
- 更新任何引用旧路由 `plugin-center` 的自定义代码
- 检查第三方集成是否使用了旧的路由地址

## 验证清单

- [x] composer.json 配置已更新
- [x] 菜单节点已更新
- [x] PHP 代码引用已更新
- [x] 文档已更新
- [x] 测试用例已更新
- [ ] 用户书签需要更新（用户自行处理）
- [ ] 浏览器缓存需要清除（用户自行处理）

## 相关插件

本次变更还涉及以下插件的路由同步：
- **wechat-client**: `plugin-wechat-client` → `wechat-client`
- **wechat-service**: `plugin-wechat-service` → `wechat-service`

所有插件的编码和路由现在都遵循统一的命名规范。
