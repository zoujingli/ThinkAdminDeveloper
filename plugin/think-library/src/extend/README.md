# Extend 重组说明

`think-library/src/extend` 现在只保留按领域拆分后的真实实现，不再继续保留根目录兼容门面。

当前已经按领域下沉为这些子命名空间：

- `auth`: JWT 与认证相关实现
- `codec`: 编码、压缩、加解密
- `data`: 树结构与列表结构转换
- `filesystem`: 文件扫描、复制、删除
- `http`: 基础 HTTP 客户端
- `image`: favicon 等图像处理
- `model`: 虚拟模型构建
- `rpc`: JSON-RPC 客户端与服务端

重组原则：

- 高频工具先拆，低频专用工具后拆
- 每个实现类只保留一种主要能力，避免继续长成“万能工具箱”

当前 `ThinkLibrary` 内部已经优先使用这些新域实现，不再依赖根目录兼容门面：

- `codec/CodeToolkit`
- `data/ArrayTree`
- `filesystem/FileTools`
- `http/HttpClient`
- `auth/JwtToken`
- `image/FaviconBuilder`
- `model/VirtualStreamModel`

## 旧类迁移表

| 已移除旧类 | 新实现 | 说明 |
| --- | --- | --- |
| `CodeExtend` | `codec/CodeToolkit` | 编码、压缩、加解密、编号生成 |
| `DataExtend` | `data/ArrayTree` | 树结构和树表转换 |
| `ToolsExtend` | `filesystem/FileTools` | 文件扫描、复制、删除 |
| `HttpExtend` | `http/HttpClient` | 基础 HTTP 请求 |
| `ImageVerify` | `auth/ImageSliderVerify` | 拼图滑块验证 |
| `JsonRpcClient` | `rpc/JsonRpcHttpClient` | JSON-RPC 客户端 |
| `JsonRpcServer` | `rpc/JsonRpcHttpServer` | JSON-RPC 服务端 |
| `FaviconExtend` | `image/FaviconBuilder` | favicon 生成 |
| `VirtualModel` | `model/VirtualStreamModel` | 虚拟模型构建 |

## 新代码规则

- 新增代码直接使用对应领域类，不再依赖任何 `*Extend` 门面
- 新增实现统一放到对应领域目录，不再回填到根目录
- 数据导出统一走前端 JavaScript 模块，不再保留后端 `ExcelExtend`/`CsvExporter`
