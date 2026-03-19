<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\storage\service;

use think\admin\contract\StorageInterface;
use think\admin\contract\StorageUsageTrait;

/**
 * 本地存储支持
 * @class LocalStorage
 */
class LocalStorage implements StorageInterface
{
    use StorageUsageTrait;

    /**
     * 上传文件内容.
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function set(string $name, string $file, bool $safe = false, ?string $attname = null): array
    {
        try {
            $path = $this->path($name, $safe);
            is_dir($dir = dirname($path)) || mkdir($dir, 0777, true);
            if (file_put_contents($path, $file)) {
                return $this->info($name, $safe, $attname);
            }
        } catch (\Exception $exception) {
        }
        return [];
    }

    /**
     * 读取文件内容.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function get(string $name, bool $safe = false): string
    {
        if (!$this->has($name, $safe)) {
            return '';
        }
        return file_get_contents($this->path($name, $safe));
    }

    /**
     * 删除存储文件.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function del(string $name, bool $safe = false): bool
    {
        if ($this->has($name, $safe)) {
            return @unlink($this->path($name, $safe));
        }
        return false;
    }

    /**
     * 判断是否存在.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function has(string $name, bool $safe = false): bool
    {
        return is_file($this->path($name, $safe));
    }

    /**
     * 获取访问地址
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function url(string $name, bool $safe = false, ?string $attname = null): string
    {
        return $safe ? $name : "{$this->domain}/upload/{$this->delSuffix($name)}{$this->getSuffix($attname, $name)}";
    }

    /**
     * 获取存储路径.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function path(string $name, bool $safe = false): string
    {
        $path = $safe ? 'safefile' : 'public/upload';
        return strtr(runpath("{$path}/{$this->delSuffix($name)}"), '\\', '/');
    }

    /**
     * 获取文件信息.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function info(string $name, bool $safe = false, ?string $attname = null): array
    {
        return $this->has($name, $safe) ? [
            'url' => $this->url($name, $safe, $attname),
            'key' => "upload/{$name}", 'file' => $this->path($name, $safe),
        ] : [];
    }

    /**
     * 获取上传地址
     */
    public function upload(): string
    {
        return apiuri('storage/upload/file', [], false, true);
    }

    /**
     * 获取存储区域
     */
    public static function region(): array
    {
        return [];
    }

    /**
     * 初始化入口.
     * @throws \think\admin\Exception
     */
    protected function init()
    {
        $type = (string)StorageConfig::driver('local', 'protocol', 'follow');
        if ($type === 'follow') {
            $type = $this->app->request->scheme();
        }
        $this->domain = trim(dirname($this->app->request->baseFile()), '\/');
        if ($type !== 'path') {
            $domain = (string)StorageConfig::driver('local', 'domain', $this->app->request->host());
            if ($type === 'auto') {
                $this->domain = "//{$domain}";
            } elseif (in_array($type, ['http', 'https'])) {
                $this->domain = "{$type}://{$domain}";
            }
        }
    }
}
