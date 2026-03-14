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

namespace think\admin\service;

use think\admin\service\ProcessService;
use think\admin\service\Service;

/**
 * 模块与包信息服务。
 * @class ModuleService
 */
class ModuleService extends Service
{
    public static function getVersion(): string
    {
        $library = self::getLibrarys('zoujingli/think-library');
        return trim($library['version'] ?? 'v8.0.0', 'v');
    }

    public static function getRunVar(string $field): string
    {
        $file = syspath('vendor/binarys.php');
        if (is_file($file) && is_array($binarys = include $file)) {
            return $binarys[$field] ?? '';
        }
        return '';
    }

    public static function getPhpExec(): string
    {
        if ($phpExec = sysvar($keys = 'phpBinary')) {
            return $phpExec;
        }
        if (ProcessService::isFile($phpExec = self::getRunVar('php'))) {
            return sysvar($keys, $phpExec);
        }
        $phpExec = str_replace('/sbin/php-fpm', '/bin/php', PHP_BINARY);
        $phpExec = preg_replace('#-(cgi|fpm)(\.exe)?$#', '$2', $phpExec);
        return sysvar($keys, ProcessService::isFile($phpExec) ? $phpExec : 'php');
    }

    public static function getModules(array $data = []): array
    {
        return array_values(array_unique(array_merge($data, array_keys(AppService::local()))));
    }

    public static function getApps(array $data = []): array
    {
        return array_values(array_unique(array_merge($data, array_keys(AppService::all()))));
    }

    public static function getLibrarys(?string $package = null, bool $force = false)
    {
        $plugs = sysvar($keys = 'think.admin.version');
        if ((empty($plugs) || $force) && is_file($file = syspath('vendor/versions.php'))) {
            $plugs = sysvar($keys, include $file);
        }
        return empty($package) ? $plugs : ($plugs[$package] ?? null);
    }
}
