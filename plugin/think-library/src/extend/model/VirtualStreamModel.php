<?php

declare(strict_types=1);

namespace think\admin\extend\model;

use think\admin\contract\StreamInterface;
use think\Model;

/**
 * 虚拟模型流实现。
 * 通过 stream wrapper 动态生成轻量模型类，适合 Helper 在未知模型名时兜底构建。
 */
class VirtualStreamModel extends \stdClass implements StreamInterface
{
    /**
     * 虚拟模型模板。
     */
    private string $template = '';

    /**
     * 当前读取偏移量。
     */
    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool
    {
        $attr = parse_url($path);
        $type = strtolower((string)($attr['fragment'] ?? 'default'));
        $host = (string)($attr['host'] ?? 'Model');
        $this->position = 0;
        $this->template = static::renderTemplate($host, $type, (string)($attr['fragment'] ?? ''));
        return true;
    }

    public function stream_read(int $count)
    {
        $content = substr($this->template, $this->position, $count);
        $this->position += strlen($content);
        return $content;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->template);
    }

    public function stream_cast(int $cast_as)
    {
    }

    public function stream_close(): void
    {
    }

    public function stream_flush(): bool
    {
        return true;
    }

    public function stream_lock(int $operation): bool
    {
        return true;
    }

    public function stream_set_option(int $option, int $arg1, int $arg2): bool
    {
        return true;
    }

    public function stream_metadata(string $path, int $option, $value): bool
    {
        return true;
    }

    public function stream_stat()
    {
        return stat(__FILE__);
    }

    public function stream_tell(): int
    {
        return $this->position;
    }

    public function stream_truncate(int $new_size): bool
    {
        $this->template = substr($this->template, 0, $new_size);
        $this->position = min($this->position, strlen($this->template));
        return true;
    }

    /**
     * 让流对象具备基本的 seek 能力，便于调试和文件函数兼容。
     */
    public function stream_seek(int $offset, int $whence = SEEK_SET): bool
    {
        $length = strlen($this->template);
        $position = match ($whence) {
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $length + $offset,
            default => $offset,
        };
        if ($position < 0 || $position > $length) {
            return false;
        }
        $this->position = $position;
        return true;
    }

    public function stream_write(string $data): int
    {
        return strlen($data);
    }

    public function dir_opendir(string $path, int $options): bool
    {
        return true;
    }

    public function dir_readdir(): string
    {
        return __DIR__;
    }

    public function dir_closedir(): bool
    {
        return true;
    }

    public function dir_rewinddir(): bool
    {
        return true;
    }

    public function mkdir(string $path, int $mode, int $options): bool
    {
        return true;
    }

    public function rmdir(string $path, int $options): bool
    {
        return true;
    }

    public function rename(string $path_from, string $path_to): bool
    {
        return true;
    }

    public function unlink(string $path): bool
    {
        return true;
    }

    public function url_stat(string $path, int $flags)
    {
        return stat(__FILE__);
    }

    /**
     * 创建虚拟模型。
     */
    public static function mk(string $name, array $data = [], string $conn = ''): Model
    {
        $type = strtolower($conn ?: 'default');
        if (!class_exists($class = "\\virtual\\model\\_{$type}\\{$name}")) {
            // 这里优先直接生成类定义，避免 CLI / Worker 环境下 stream include 链不稳定。
            eval(substr(static::renderTemplate($name, $type, $conn), 6));
        }
        return new $class($data);
    }

    /**
     * 生成虚拟模型代码模板。
     */
    private static function renderTemplate(string $host, string $type, string $connection): string
    {
        $template = '<?php ';
        $template .= "namespace virtual\\model\\_{$type}; ";
        $template .= "class {$host} extends \\think\\admin\\Model { ";
        if ($connection !== '') {
            $template .= "protected \$connection='{$connection}'; ";
        }
        $template .= '}';
        return $template;
    }
}
