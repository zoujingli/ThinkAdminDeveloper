# Storage 组件合并说明

## 更新概述

**Storage 组件已合并到 System 组件**，不再作为独立插件存在。

## 架构变更

### 变更前的架构
```
ThinkLibrary → Storage (独立组件)
             → System (独立组件)
```

### 变更后的架构
```
ThinkLibrary → System (包含 Storage 功能)
```

## 代码结构调整

### 原 Storage 组件位置
```
plugin/think-plugs-storage/  (已删除)
├── src/
│   ├── storage/
│   ├── controller/
│   └── ...
└── ...
```

### 现 Storage 功能位置
```
plugin/think-plugs-system/src/storage/
├── LocalStorage.php         # 本地存储驱动
├── AliossStorage.php        # 阿里云 OSS 驱动
├── TxcosStorage.php         # 腾讯云 COS 驱动
├── QiniuStorage.php         # 七牛云 Kodo 驱动
├── UpyunStorage.php         # 又牛云存储驱动
├── AlistStorage.php         # Alist 网络存储驱动
├── StorageManager.php       # 存储管理器
├── StorageAuthorize.php     # 上传授权管理
├── StorageConfig.php        # 存储配置管理
├── config.php               # 存储配置
├── mimes.php                # MIME 类型配置
└── upload.js                # 前端上传脚本
```

## 数据表归属

### system_file 表
- **原归属**: Storage 组件
- **现归属**: System 组件
- **迁移脚本**: `plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php`

## 控制器和功能

### 文件管理控制器
- `plugin/system/src/controller/File.php` - 文件管理后台页面
- `plugin/system/src/controller/api/Upload.php` - 上传 API 接口

### 数据模型
- `plugin/system/src/model/SystemFile.php` - 文件数据模型

## 依赖关系更新

### 更新前的依赖
```json
{
  "require": {
    "zoujingli/think-plugs-storage": "^8.0"
  }
}
```

### 更新后的依赖
```json
{
  "require": {
    "zoujingli/think-plugs-system": "^8.0"
  }
}
```

## 各插件依赖更新

### Account 组件
- **原依赖**: `ThinkPlugsStorage`
- **现依赖**: `ThinkPlugsSystem` (包含 Storage 功能)

### Payment 组件
- **原依赖**: `ThinkPlugsStorage`
- **现依赖**: `ThinkPlugsSystem` (包含 Storage 功能)

### Wemall 组件
- **原依赖**: `ThinkPlugsStorage`
- **现依赖**: `ThinkPlugsSystem` (包含 Storage 功能)

### Wuma 组件
- **原依赖**: `ThinkPlugsStorage`
- **现依赖**: `ThinkPlugsSystem` (包含 Storage 功能)

## 功能说明

### 存储驱动管理
Storage 功能合并后，System 组件现在支持以下存储驱动：

1. **LocalStorage** - 本地文件存储
2. **AliossStorage** - 阿里云对象存储 OSS
3. **TxcosStorage** - 腾讯云对象存储 COS
4. **QiniuStorage** - 七牛云对象存储 Kodo
5. **UpyunStorage** - 又牛云存储
6. **AlistStorage** - Alist 网络存储（支持多种网盘）

### 核心功能
- 统一的 Storage 门面接口
- 多存储驱动支持和管理
- 上传授权和临时令牌生成
- 文件元数据管理
- 文件上传、删除、清理
- 文件类型统计和管理

## 使用示例

### 存储管理器使用
```php
use plugin\system\storage\StorageManager;

// 获取存储管理器
$storage = StorageManager::instance();

// 上传文件
$result = $storage->upload($file, 'local');

// 获取文件 URL
$url = $storage->url('local', $filename);
```

### 上传授权
```php
use plugin\system\storage\StorageAuthorize;

// 生成上传授权令牌
$auth = StorageAuthorize::mk()->buildToken([
    'type' => 'image',
    'xkey' => 'upload_key',
    'xname' => 'filename.jpg'
]);

// 返回前端使用
return [
    'token' => $auth['token'],
    'url' => $auth['url']
];
```

### 存储配置管理
```php
use plugin\system\storage\StorageConfig;

// 获取存储配置
$config = StorageConfig::get('local');

// 保存存储配置
StorageConfig::save('local', [
    'root' => './public/upload'
]);
```

## 文档更新清单

### 已更新的文档

1. **readme.md**
   - ✅ 删除了独立的 Storage 组件描述
   - ✅ 在 System 组件中增加了存储功能说明
   - ✅ 更新了架构图（删除 Storage 节点）
   - ✅ 更新了各组件依赖关系
   - ✅ 更新了许可证列表

2. **docs/components-detail.md**
   - ✅ 在 System 组件中增加存储功能详细说明
   - ✅ 删除独立的 Storage 章节

3. **各插件 readme.md**
   - ✅ Account: 更新依赖为 System
   - ✅ Payment: 更新依赖为 System
   - ✅ Wemall: 更新依赖为 System
   - ✅ Wuma: 更新依赖为 System

## 迁移步骤

### 对于现有项目

1. **更新 composer.json**
```json
{
  "require": {
    "zoujingli/think-plugs-system": "^8.0"
    // 删除： "zoujingli/think-plugs-storage": "^8.0"
  }
}
```

2. **更新代码引用**
```php
// 旧代码
use plugin\storage\storage\StorageManager;

// 新代码
use plugin\system\storage\StorageManager;
```

3. **重新安装依赖**
```bash
composer update
```

4. **验证功能**
```bash
# 测试上传功能
php think list | grep upload

# 检查存储配置
php think config:get storage
```

## 优势分析

### 架构简化
- ✅ 减少一个独立组件，降低维护成本
- ✅ 减少组件间依赖关系
- ✅ 统一系统核心功能管理

### 开发效率
- ✅ 文件管理和系统管理在同一代码库
- ✅ 减少跨组件调用
- ✅ 更清晰的代码组织结构

### 性能优化
- ✅ 减少自动加载文件数量
- ✅ 减少服务注册数量
- ✅ 降低系统启动时间

## 注意事项

1. **命名空间变更**
   - 原：`plugin\storage\*`
   - 现：`plugin\system\storage\*`

2. **数据表归属**
   - `system_file` 表现在明确归属 System 组件
   - 迁移脚本在 System 的 stc/database 目录

3. **配置路径**
   - 存储配置仍在 `config/storage.php`
   - 但由 System 组件统一管理

4. **向后兼容**
   - 建议尽快更新代码引用
   - 旧命名空间可能在未来版本移除

## 验证清单

- [x] Storage 组件目录已删除
- [x] Storage 功能已合并到 System
- [x] system_file 表归属已更新
- [x] 所有控制器已迁移到 System
- [x] 所有存储驱动已迁移到 System
- [x] 各插件依赖已更新为 System
- [x] 根 readme 已更新
- [x] 组件详细文档已更新
- [x] 架构图已更新
- [x] 许可证列表已更新

## 更新日期

2026-03-22
