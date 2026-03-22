# 文档更新说明

本次更新根据代码实现同步更新了项目文档，确保文档与实际代码保持一致。

## 重要架构变更

### Storage 组件已合并到 System

**重要**: Storage 组件已不再作为独立插件存在，其功能已完全合并到 ThinkPlugsSystem 组件中。

#### 变更说明

- **原位置**: `plugin/think-plugs-storage/` (已删除)
- **现位置**: `plugin/think-plugs-system/src/storage/`
- **数据表**: `system_file` 现在归属 System 组件
- **依赖关系**: 所有原依赖 Storage 的插件改为依赖 System

#### 存储驱动列表

System 组件现在包含以下存储驱动：
- LocalStorage (本地存储)
- AliossStorage (阿里云 OSS)
- TxcosStorage (腾讯云 COS)
- QiniuStorage (七牛云 Kodo)
- UpyunStorage (又牛云)
- AlistStorage (Alist 网络存储)

#### 核心文件

- `StorageManager.php` - 存储管理器
- `StorageAuthorize.php` - 上传授权管理
- `StorageConfig.php` - 存储配置管理
- `controller/File.php` - 文件管理控制器
- `controller/api/Upload.php` - 上传 API 控制器
- `model/SystemFile.php` - 文件数据模型

#### 文档更新

详细变更说明请参考：[STORAGE_MERGE.md](./STORAGE_MERGE.md)

### 废弃模型清理

#### SystemConfig 模型已删除

**原因**: `SystemConfig` 模型对应的 `system_config` 表从未在生产环境中使用，仅存在于测试代码中。

**清理内容**:
- 删除文件：`plugin/think-plugs-system/src/model/SystemConfig.php`
- 该模型无任何生产代码引用
- 系统配置实际使用 `SystemData` 模型和 `system_data` 表存储
- 通过 `sysdata()` 和 `sysconf()` 函数访问配置

**实际使用的配置存储**:
- 数据表：`system_data`
- 模型：`SystemData`
- 访问方式：`sysdata('system.site')`, `sysconf('site_name')`

#### Builder 组件已删除

**原因**: 动态页面构建功能暂时不需要，移除相关控制器、模型、服务和菜单配置。

**清理内容**:
- 删除控制器：`plugin/think-plugs-system/src/controller/Builder.php` (873 行)
- 删除模型：`plugin/think-plugs-system/src/model/SystemBuilder.php`
- 删除服务：`plugin/think-plugs-system/src/service/BuilderService.php` (16.2KB)
- 删除测试：`plugin/think-plugs-system/tests/BuilderControllerTest.php`
- 删除菜单：从 `composer.json` 中移除"动态页面构建"菜单项

**影响的菜单**:
- 已移除：系统配置 > 动态页面构建 (`system/builder/index`)

**保留的功能**:
- `system_data` 表仍然保留，作为系统配置存储使用
- `sysdata()` 和 `sysconf()` 函数继续使用 `system_data` 表

---

## 更新内容

### 1. 新增组件详细文档

创建了 `docs/components-detail.md` 文件，包含：

- **13 个组件的详细说明**，每个组件包含：
  - 包名和定位
  - 核心功能列表
  - 数据表结构
  - 目录结构
  - 使用示例
  - 依赖关系
  - 许可证信息

- **架构约定说明**：
  - 认证与会话规范
  - 路由与应用约定
  - PHAR 路径约定

- **开发工具说明**：
  - PHPStan 配置
  - 代码风格工具
  - 测试套件

### 2. 更新根 readme.md

#### 仓库组成部分
- 为每个组件增加了详细的功能描述
- 补充了核心工具、扩展能力、全局函数等信息
- 明确了各组件的许可证类型
- 增加了组件详细文档的引用链接

#### 开发工具部分
- 增加了测试章节，说明如何运行各类测试
- 补充了测试覆盖范围说明
- 简化了 PHPStan 配置说明

#### 文档链接部分
- 修复了所有文档路径（从绝对路径改为相对路径）
- 增加了架构文档子项（插件标准、路由分发、软删除等）
- 新增了组件详细文档的引用

#### 许可证部分
- 明确列出各组件的许可证类型
- 分组说明：MIT、Apache-2.0、专有授权
- 增加了商业使用限制说明

### 3. 更新 ThinkLibrary readme.md

#### 核心功能部分
- 细化了 6 大功能模块的描述
- 增加了扩展工具的详细说明（FaviconBuilder、ImageSliderVerify、JsonRpc 等）
- 补充了全局函数的分类和说明

#### 依赖要求
- 增加了 fileinfo 扩展要求
- 增加了 redis 扩展推荐

#### 开发规范
- 增加了具体的命令示例
- 补充了静态分析说明
- 增加了测试覆盖范围说明

#### 新增章节
- 认证说明（JWT 令牌、Token Session）
- PHAR 兼容性说明
- 测试覆盖详细说明

## 文档改进点

### 描述详细度
- ✅ 每个组件都有清晰的功能定位
- ✅ 列出了核心功能清单
- ✅ 提供了使用示例代码
- ✅ 明确了数据表和目录结构

### 架构清晰度
- ✅ 分层架构描述清晰
- ✅ 组件边界明确
- ✅ 依赖关系清楚
- ✅ 认证协议统一

### 实用性
- ✅ 提供了完整的命令示例
- ✅ 包含配置示例代码
- ✅ 明确了许可证限制
- ✅ 补充了开发工具使用说明

### 一致性
- ✅ 所有组件使用统一的描述结构
- ✅ 术语使用一致
- ✅ 路径格式统一（相对路径）
- ✅ 代码风格统一

## 后续建议

### 需要补充的内容
1. **API 接口文档**：各插件的 API 接口详细说明
2. **数据库字典**：每个数据表的字段说明
3. **事件文档**：系统事件列表和监听器说明
4. **错误码文档**：统一错误码和异常说明

### 需要完善的示例
1. **完整业务流程示例**：从登录到业务操作的完整流程
2. **插件开发示例**：如何开发一个新插件
3. **自定义扩展示例**：如何扩展已有组件

## 验证清单

- [x] 所有组件描述与实际代码一致
- [x] 命令示例可以正常运行
- [x] 配置示例符合实际配置
- [x] 路径引用使用相对路径
- [x] 许可证信息准确
- [x] 依赖关系正确
- [x] 架构描述清晰

## 文档位置

- 根 readme：`/readme.md`
- 组件详细文档：`/docs/components-detail.md`
- ThinkLibrary 文档：`/plugin/think-library/readme.md`
- 架构文档：`/docs/architecture/`

## 更新日期

2026-03-22
