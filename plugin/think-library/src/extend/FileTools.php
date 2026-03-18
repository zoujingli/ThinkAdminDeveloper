<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\extend;

use SplFileInfo;
use think\admin\Exception;

/**
 * 标准文件系统工具。
 */
class FileTools
{
    /**
     * 兼容旧方式调用。
     *
     * @return array|bool
     * @throws Exception
     */
    public static function __callStatic(string $method, array $arguments)
    {
        $methods = [
            'copyfile' => 'copy',
            'scandirectory' => 'scan',
            'findfilesarray' => 'find',
            'removeemptydirectory' => 'remove',
        ];
        if ($real = $methods[strtolower($method)] ?? null) {
            return self::{$real}(...$arguments);
        }
        throw new Exception("method not exists: FileTools::{$method}()");
    }

    /**
     * 扫描目录下的文件列表。
     */
    public static function scan(string $path, ?int $depth = null, string $ext = '', bool $short = true): array
    {
        return static::find($path, $depth, function (\SplFileInfo $info) use ($ext) {
            return $info->isDir() || $ext === '' || strtolower($info->getExtension()) === strtolower($ext);
        }, $short);
    }

    /**
     * 扫描目录并返回文件路径数组。
     */
    public static function find(string $path, ?int $depth = null, ?\Closure $filter = null, bool $short = true): array
    {
        [$info, $files] = [new \SplFileInfo($path), []];
        if (!$info->isDir() && !$info->isFile()) {
            return $files;
        }
        if ($info->isFile() && ($filter === null || $filter($info) !== false)) {
            $files[] = $short ? $info->getBasename() : $info->getPathname();
        }
        if ($info->isDir()) {
            foreach (static::findFilesYield($info->getPathname(), $depth, $filter) as $file) {
                $files[] = $short ? static::relativePath($info->getPathname(), $file->getPathname()) : $file->getPathname();
            }
        }
        return $files;
    }

    /**
     * 递归扫描指定目录，返回文件或目录的 SplFileInfo 对象。
     */
    public static function findFilesYield(string $path, ?int $depth = null, ?\Closure $filter = null, bool $appendPath = false, int $currDepth = 1): \Generator
    {
        if (!file_exists($path) || !is_dir($path) || (!is_null($depth) && $currDepth > $depth)) {
            return;
        }
        foreach (new \FilesystemIterator($path, \FilesystemIterator::SKIP_DOTS) as $item) {
            if ($filter !== null && $filter($item) === false) {
                continue;
            }
            if ($item->isDir() && !$item->isLink()) {
                if ($appendPath) {
                    yield $item;
                }
                yield from static::findFilesYield($item->getPathname(), $depth, $filter, $appendPath, $currDepth + 1);
            } else {
                yield $item;
            }
        }
    }

    /**
     * 深度拷贝到指定目录。
     * `remove=true` 时会在复制后删除源文件，适合初始化发布场景。
     */
    public static function copy(string $frdir, string $todir, array $files = [], bool $force = true, bool $remove = true): bool
    {
        $frdir = static::normalizeDirectory($frdir);
        $todir = static::normalizeDirectory($todir);
        if (empty($files) && is_dir($frdir)) {
            $files = static::find($frdir, null, static function (\SplFileInfo $info) {
                return $info->getBasename()[0] !== '.';
            });
        }
        foreach ($files as $target) {
            [$fromPath, $destPath] = [$frdir . $target, $todir . $target];
            if ($force || !is_file($destPath)) {
                is_dir($dir = dirname($destPath)) || mkdir($dir, 0777, true);
                copy($fromPath, $destPath);
            }
            if ($remove && is_file($fromPath)) {
                unlink($fromPath);
            }
        }
        if ($remove) {
            static::remove($frdir);
        }
        return true;
    }

    /**
     * 移除文件或清空目录。
     */
    public static function remove(string $path): bool
    {
        if (!file_exists($path)) {
            return true;
        }
        if (is_file($path)) {
            return unlink($path);
        }
        $dirs = [$path];
        iterator_to_array(self::findFilesYield($path, null, function (\SplFileInfo $file) use (&$dirs) {
            $file->isDir() ? $dirs[] = $file->getPathname() : unlink($file->getPathname());
        }));
        usort($dirs, static function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });
        foreach ($dirs as $dir) {
            file_exists($dir) && is_dir($dir) && rmdir($dir);
        }
        return !file_exists($path);
    }

    /**
     * 计算相对于根目录的短路径。
     */
    private static function relativePath(string $root, string $pathname): string
    {
        return substr($pathname, strlen($root) + 1);
    }

    /**
     * 统一目录分隔符并补尾部分隔符。
     */
    private static function normalizeDirectory(string $path): string
    {
        return rtrim($path, '\/') . DIRECTORY_SEPARATOR;
    }
}
