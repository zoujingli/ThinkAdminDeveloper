# ThinkPlugsBuilder for ThinkAdmin

`ThinkPlugsBuilder` 是 ThinkAdmin 8 / ThinkPHP 8.1 的标准打包插件，用于将当前项目构建为可直接运行的 `admin.phar`。

它的目标不是简单压缩代码，而是把 ThinkAdmin 项目整理成可发布产物，支持：

- 使用临时目录中转打包，减少直接扫描项目目录带来的耗时
- 构建时自动整合项目代码、Composer 依赖和运行入口
- 首次启动时自动创建 `public`、`runtime` 等运行目录
- 支持只保留 `.env + admin.phar` 的最小部署形态
- 支持通过 `php admin.phar xadmin:worker ...` 直接运行 Worker

## 版本基线

- ThinkAdmin `8.x`
- ThinkPHP `8.1+`
- PHP `8.1+`
- 需要启用 `Phar` 扩展

## 安装

```bash
composer require zoujingli/think-plugs-builder
```

## 快速开始

推荐直接使用项目根目录已经配置好的构建脚本：

```bash
composer build:phar
```

上面的脚本会按下面的顺序执行：

1. 先执行 `composer database:publish` 发布配置、静态资源和迁移脚本
2. 再执行 `php -d phar.readonly=0 think xadmin:builder --name=admin.phar`

构建成功后，会在项目根目录生成 `admin.phar`。

## 命令说明

插件提供统一命令：

```bash
php -d phar.readonly=0 think xadmin:builder
```

如果需要显式指定输出文件名，可以这样执行：

```bash
php -d phar.readonly=0 think xadmin:builder --name=admin.phar
```

### 参数列表

| 参数 | 说明 | 默认值 |
| --- | --- | --- |
| `--name` | 输出的 PHAR 文件名 | `admin.phar` |
| `--main` | 包内主入口文件 | `think` |
| `--extract` | 首次启动时解压到外部目录的路径，可重复传入 | `public`、`database` |
| `--mount` | 运行时挂载到 PHAR 外部的文件或目录，可重复传入 | `.env`、`runtime`、`safefile`、`public`、`database` |
| `--exclude` | 额外排除的文件或目录，可重复传入 | 无 |

示例：

```bash
php -d phar.readonly=0 think xadmin:builder ^
  --name=admin.phar ^
  --extract=public ^
  --extract=database ^
  --mount=.env ^
  --mount=runtime ^
  --exclude=public/upload
```

## 打包机制

`ThinkPlugsBuilder` 会先把需要的文件复制到系统临时目录，再从临时目录生成 PHAR，流程如下：

1. 复制根目录下必要文件，如 `think`、`composer.json`、`composer.lock`、`.env.example`
2. 复制项目业务目录
3. 复制 `vendor` 里的自动加载文件、Composer 元数据和实际安装依赖
4. 对已知的 PHAR 兼容点进行临时补丁处理
5. 写入运行时引导脚本并生成最终的 `admin.phar`
6. 清理临时目录

默认不会把下面这些内容直接打进包内：

- `.env`
- `runtime`
- `safefile`
- `build`
- `tests`
- `public/upload`

这样做的目的是把运行时状态和发布产物分离，避免把环境文件和可变数据固化进包内。

## 部署方式

最小部署产物只需要两个文件：

- `.env`
- `admin.phar`

发布目录示例：

```text
release/
├─ .env
└─ admin.phar
```

把这两个文件放到一个空目录后，就可以直接运行：

```bash
php admin.phar
```

也可以执行 ThinkAdmin / ThinkPHP 命令：

```bash
php admin.phar list
php admin.phar xadmin:worker status http
php admin.phar xadmin:worker start http
```

## 运行时自动行为

PHAR 首次启动时会自动完成下面这些事情：

- 如果根目录没有 `.env`，优先用 `.env.example` 自动生成
- 自动创建 `public` 目录
- 自动创建 `runtime` 目录
- 自动同步 `.env` 到 `runtime/.env`
- 如果 `public`、`database` 不存在，则从 PHAR 内解压到外部目录
- 将 `.env`、`runtime`、`safefile`、`public`、`database` 挂载到 PHAR 外部

这意味着部署后即使目录里一开始只有 `.env + admin.phar`，第一次运行后也会自动补齐运行目录。

## 路径约定（PHAR 兼容关键点）

在 PHAR 运行模式下，必须严格区分“包内只读资源”和“外部可写运行目录”：

- `syspath()`：用于读取 **PHAR 包内**资源（只读），典型如 `public/static/...`、模板、内置数据文件等
- `runpath()`：用于读写 **PHAR 外部**文件系统（可写），典型如 `runtime/`、`safefile/`、`database/`、`public/upload/` 以及 Worker 的 pid/log/status/stdout 文件

常见坑：

- GD 的 `imagettftext()` 不支持 `phar://` 字体流：字体需复制到 `runpath()` 再使用
- SQLite / File cache 等落盘路径必须使用 `runpath()`，否则会尝试写入 PHAR 内导致失败

## Worker 使用说明

打包后的 PHAR 支持直接运行 Worker：

```bash
php admin.phar xadmin:worker start http
php admin.phar xadmin:worker start queue
php admin.phar xadmin:worker status http
php admin.phar xadmin:worker stop http
```

如果你的项目已经启用了 Worker 插件，那么发布后不需要再额外保留 `think` 入口文件，直接通过 `admin.phar` 即可完成进程管理。

## 推荐发布流程

建议按下面的顺序发布：

```bash
composer install --no-dev
composer build:phar
```

然后将下面两个文件复制到目标机器：

```text
.env
admin.phar
```

在目标机器运行：

```bash
php admin.phar list
php admin.phar xadmin:worker status http
```

确认命令可用后，再按实际需要启动 `http` 或 `queue` 服务。

## 注意事项

- `xadmin:builder` 仅在调试模式下可用，生产模式不会暴露该命令
- 打包时必须使用 `-d phar.readonly=0`
- 打包前必须先执行 `composer install`，否则缺少 `vendor` 目录无法构建
- 如果项目依赖迁移发布产物，建议优先执行 `composer build:phar`，不要绕过 `database:publish`
- 输出文件默认会覆盖已有的同名 `admin.phar`

## 验证建议

建议每次构建完成后，至少做下面两项检查：

```bash
php admin.phar list
php admin.phar xadmin:worker status http
```

如果这两个命令可以正常执行，通常说明入口脚本、依赖加载和 Worker 兼容层都已经生效。
