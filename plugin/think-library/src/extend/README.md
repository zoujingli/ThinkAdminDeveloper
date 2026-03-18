# Extend 标准说明

`think-library/src/extend` 是基础库的标准工具层，只保留无状态、可复用、跨组件通用的基础实现。

当前结构固定为单层类集合，不再继续拆子目录：

- `CodeToolkit`
- `ArrayTree`
- `FileTools`
- `HttpClient`
- `README`

放入 `extend` 的标准：

- 只放基础工具，不放业务实现
- 只放无状态能力，不依赖登录态、插件态、控制器流程
- 类职责单一，命名直接表达能力
- 命名空间统一为 `think\admin\extend\ClassName`

不满足这些条件的实现，应优先放到：

- `contract`: 定义标准契约
- `service`: 承载带协议语义、门面语义或状态组合的基础能力
- 具体插件：承载业务实现

## 旧类迁移表

| 已移除旧类           | 新实现                         | 说明                            |
|-----------------|-----------------------------|-------------------------------|
| `CodeExtend`    | `CodeToolkit`               | 编码、压缩、加解密、编号生成                |
| `DataExtend`    | `ArrayTree`                 | 树结构和树表转换                      |
| `ToolsExtend`   | `FileTools`                 | 文件扫描、复制、删除                    |
| `HttpExtend`    | `HttpClient`                | 基础 HTTP 请求                    |
| `ImageVerify`   | `service/ImageSliderVerify` | 拼图滑块验证                        |
| `JsonRpcClient` | `service/JsonRpcHttpClient` | JSON-RPC 客户端                  |
| `JsonRpcServer` | `service/JsonRpcHttpServer` | JSON-RPC 服务端                  |
| `FaviconExtend` | `service/FaviconBuilder`    | favicon 生成                    |
| `JwtExtend`     | `service/JwtToken`          | JWT 编码、签名、校验                  |
| `VirtualModel`  | `RuntimeModel`              | 运行时模型构建，统一由 `ModelFactory` 调用 |

## 新代码规则

- 新增代码直接使用单层标准类，不再依赖任何 `*Extend` 门面
- 新增基础工具直接放到 `extend` 根目录，不再拆子目录
- 带协议门面、状态组合、控制器输出能力的基础类优先放到 `service`
- 数据导出统一走前端 JavaScript 模块，不再保留后端 `ExcelExtend` / `CsvExporter`
