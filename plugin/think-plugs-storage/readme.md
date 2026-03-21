# ThinkPlugsStorage

存储中心插件，统一管理存储驱动、上传授权和配置元数据。

## 功能定位

- 统一管理多种存储驱动（本地、OSS、COS、七牛等）
- 提供上传授权和临时令牌
- 管理存储配置元数据
- 持有 `system_file` 数据表

## 安装

```bash
composer require zoujingli/think-plugs-storage
```

## 配置

在 `composer.json` 中注册:

```json
{
  "extra": {
    "think": {
      "services": ["plugin\\storage\\Service"]
    },
    "xadmin": {
      "app": {
        "code": "storage",
        "prefix": "storage"
      },
      "menu": {
        "items": [
          {
            "name": "系统配置",
            "subs": [
              {
                "name": "存储配置中心",
                "icon": "layui-icon layui-icon-upload-drag",
                "node": "storage/config/index"
              },
              {
                "name": "文件管理",
                "icon": "layui-icon layui-icon-carousel",
                "node": "storage/file/index"
              }
            ]
          }
        ]
      },
      "migrate": {
        "file": "20241010000002_install_storage20241010.php",
        "class": "InstallStorage20241010",
        "name": "StoragePlugin"
      }
    }
  }
}
```

## 数据表

`system_file` - 系统文件记录表

```sql
CREATE TABLE `system_file` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `hash` varchar(32) NOT NULL COMMENT '文件哈希',
  `name` varchar(100) NOT NULL COMMENT '文件名',
  `path` varchar(500) NOT NULL COMMENT '文件路径',
  `mime` varchar(100) NOT NULL COMMENT 'MIME 类型',
  `size` bigint(20) DEFAULT '0' COMMENT '文件大小',
  `driver` varchar(50) DEFAULT '' COMMENT '存储驱动',
  `created_at` int(11) DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `hash` (`hash`),
  KEY `driver` (`driver`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## 核心功能

### 1. 驱动管理

支持多种存储驱动:

- **本地存储**: LocalStorage
- **阿里云 OSS**: OssStorage
- **腾讯云 COS**: CosStorage
- **七牛云**: QiniuStorage
- **其他**: 可扩展自定义驱动

### 2. 配置中心

- 存储配置管理
- 驱动切换
- 配置元数据
- 多环境配置

### 3. 上传授权

- 临时授权令牌
- 上传令牌有效期控制
- 安全校验
- 跨域上传支持

### 4. 文件管理

- 文件列表查询
- 文件下载
- 文件删除
- 文件统计

## 目录结构

```
think-plugs-storage/
├── src/
│   ├── controller/
│   │   ├── Config.php        # 配置管理
│   │   └── File.php          # 文件管理
│   ├── model/
│   │   └── SystemFile.php    # 文件模型
│   └── service/
│       ├── StorageConfig.php     # 配置管理
│       ├── StorageManager.php    # 驱动管理
│       ├── StorageAuthorize.php  # 上传授权
│       ├── LocalStorage.php      # 本地存储
│       ├── OssStorage.php        # 阿里云 OSS
│       ├── CosStorage.php        # 腾讯云 COS
│       └── QiniuStorage.php      # 七牛云
├── stc/
│   └── database/
│       └── 20241010000002_install_storage20241010.php
└── tests/
```

## 使用示例

### 上传文件

```php
<?php
use plugin\storage\service\StorageManager;

// 获取存储管理器
$storage = StorageManager::instance();

// 上传本地文件
$file = $this->request->file('file');
$result = $storage->upload($file, 'local');

// 返回文件信息
[
    'url' => 'http://example.com/uploads/20240101/file.jpg',
    'hash' => 'abc123...',
    'name' => 'file.jpg',
    'size' => 102400,
    'mime' => 'image/jpeg'
]
```

### 获取上传授权

```php
<?php
use plugin\storage\service\StorageAuthorize;

// 获取上传授权令牌
$auth = StorageAuthorize::token(1800); // 30 分钟有效期

// 返回授权信息
[
    'token' => 'upload_token_xxx',
    'expire' => 1800,
    'api' => '/api/storage/upload/file'
]
```

### 配置存储驱动

```php
<?php
use plugin\storage\service\StorageConfig;

// 获取存储配置
$config = StorageConfig::get('local');

// 设置存储配置
StorageConfig::set('local', [
    'root' => runpath('public/upload'),
    'url' => '/upload'
]);

// 切换默认驱动
StorageConfig::setDefault('oss');
```

### 使用 OSS 存储

```php
<?php
use plugin\storage\service\OssStorage;

$oss = new OssStorage([
    'accessKeyId' => 'your_access_key',
    'accessKeySecret' => 'your_access_secret',
    'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
    'bucket' => 'your-bucket'
]);

// 上传文件
$result = $oss->upload($file, 'path/to/file.jpg');

// 获取文件 URL
$url = $oss->getUrl('path/to/file.jpg');
```

## API 接口

### 文件上传

```
POST /api/storage/upload/file
Content-Type: multipart/form-data

Parameters:
- file: 上传的文件
- token: 上传授权令牌（可选）

Response:
{
    "code": 1,
    "info": "上传成功",
    "data": {
        "url": "http://example.com/uploads/file.jpg",
        "hash": "abc123...",
        "name": "file.jpg",
        "size": 102400,
        "mime": "image/jpeg"
    }
}
```

### 配置管理

```
GET  /api/storage/config/index    # 获取配置列表
POST /api/storage/config/save     # 保存配置
POST /api/storage/config/switch   # 切换驱动
```

### 文件管理

```
GET  /api/storage/file/index      # 文件列表
POST /api/storage/file/del        # 删除文件
GET  /api/storage/file/download   # 下载文件
```

## 存储驱动配置

### 本地存储

```php
'local' => [
    'type' => 'local',
    'root' => runpath('public/upload'),  // 上传根目录
    'url' => '/upload',                   // 访问 URL 前缀
]
```

### 阿里云 OSS

```php
'oss' => [
    'type' => 'oss',
    'accessKeyId' => 'your_access_key',
    'accessKeySecret' => 'your_access_secret',
    'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
    'bucket' => 'your-bucket',
    'cdn' => 'https://cdn.example.com',  // CDN 域名（可选）
]
```

### 腾讯云 COS

```php
'cos' => [
    'type' => 'cos',
    'secretId' => 'your_secret_id',
    'secretKey' => 'your_secret_key',
    'region' => 'ap-guangzhou',
    'bucket' => 'your-bucket',
    'cdn' => 'https://cdn.example.com',  // CDN 域名（可选）
]
```

### 七牛云

```php
'qiniu' => [
    'type' => 'qiniu',
    'accessKey' => 'your_access_key',
    'secretKey' => 'your_secret_key',
    'bucket' => 'your-bucket',
    'domain' => 'https://cdn.example.com',  // 绑定域名
]
```

## 上传安全

### 文件类型限制

```php
// 允许上传的文件类型
$allowTypes = ['jpg', 'png', 'gif', 'jpeg'];

// 验证文件类型
if (!in_array($file->getExtension(), $allowTypes)) {
    throw new \Exception('不允许的文件类型');
}
```

### 文件大小限制

```php
// 最大文件大小（10MB）
$maxSize = 10 * 1024 * 1024;

if ($file->getSize() > $maxSize) {
    throw new \Exception('文件超出大小限制');
}
```

### 上传令牌验证

```php
use plugin\storage\service\StorageAuthorize;

// 验证上传令牌
$token = $this->request->post('token');
if (!StorageAuthorize::check($token)) {
    throw new \Exception('上传令牌无效');
}
```

## 自定义存储驱动

### 实现接口

```php
<?php
namespace app\storage;

use plugin\storage\service\StorageInterface;

class CustomStorage implements StorageInterface
{
    public function upload(array $file): array
    {
        // 实现上传逻辑
    }
    
    public function download(string $key): string
    {
        // 实现下载逻辑
    }
    
    public function delete(string $key): bool
    {
        // 实现删除逻辑
    }
    
    public function exists(string $key): bool
    {
        // 实现检查逻辑
    }
    
    public function getUrl(string $key): string
    {
        // 实现获取 URL 逻辑
    }
}
```

### 注册驱动

```php
use plugin\storage\service\StorageManager;

// 注册自定义驱动
StorageManager::register('custom', function($config) {
    return new \app\storage\CustomStorage($config);
});
```

## 性能优化

### 使用 CDN

```php
'oss' => [
    'type' => 'oss',
    // ...
    'cdn' => 'https://cdn.example.com',  // 使用 CDN 加速
]
```

### 文件哈希去重

```php
// 计算文件哈希
$hash = md5_file($file->getPathname());

// 检查是否已存在
$exists = SystemFile::where('hash', $hash)->find();
if ($exists) {
    // 返回已存在的文件信息
    return $exists;
}
```

### 批量删除

```php
// 批量删除文件
$ids = [1, 2, 3, 4, 5];
SystemFile::where('id', 'in', $ids)->chunk(100, function($files) {
    foreach ($files as $file) {
        StorageManager::delete($file->path);
    }
    $files->each->delete();
});
```

## 依赖

- PHP >= 8.1
- ThinkLibrary
- 扩展：curl, json

## 开发规范

### 命名空间

所有类使用 `plugin\storage\` 命名空间

### 代码风格

遵循 PSR-12 规范

## 测试

运行单元测试:

```bash
vendor/bin/phpunit --testsuite ThinkPlugsStorage
```

## 许可证

MIT License

## 相关链接

- 官网文档：https://thinkadmin.top/plugin/think-plugs-storage.html
- Gitee: https://gitee.com/zoujingli/ThinkAdmin
- Github: https://github.com/zoujingli/ThinkAdmin
