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

namespace think\admin\contract;

/**
 * 文件存储标准接口.
 * @class StorageInterface
 */
interface StorageInterface
{
    /**
     * 获取存储区域
     */
    public static function region(): array;

    /**
     * 上传文件内容.
     * @param string $name 文件名称
     * @param string $file 文件内容
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function set(string $name, string $file, bool $safe = false, ?string $attname = null): array;

    /**
     * 读取文件内容.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function get(string $name, bool $safe = false): string;

    /**
     * 删除存储文件.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function del(string $name, bool $safe = false): bool;

    /**
     * 判断是否存在.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function has(string $name, bool $safe = false): bool;

    /**
     * 获取访问地址
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function url(string $name, bool $safe = false, ?string $attname = null): string;

    /**
     * 获取存储路径.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     */
    public function path(string $name, bool $safe = false): string;

    /**
     * 获取文件信息.
     * @param string $name 文件名称
     * @param bool $safe 安全模式
     * @param ?string $attname 下载名称
     */
    public function info(string $name, bool $safe = false, ?string $attname = null): array;

    /**
     * 获取上传地址
     */
    public function upload(): string;
}
