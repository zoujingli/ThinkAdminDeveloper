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

use think\admin\Library;

/**
 * 应用注册服务
 * @class AppService
 */
class AppService extends Service
{
    /**
     * 应用缓存键名.
     */
    private const CACHE_APPS = 'think.admin.apps';

    /**
     * 本地应用缓存键名.
     */
    private const CACHE_LOCALS = 'think.admin.apps.locals';

    /**
     * app 根目录下的共享目录，不参与本地多应用扫描.
     */
    private const IGNORE_LOCAL_APPS = ['common', 'config', 'controller', 'lang', 'middleware', 'model', 'route', 'view'];

    /**
     * 清理应用缓存.
     */
    public static function clear(): void
    {
        sysvar(self::CACHE_APPS, false);
        sysvar(self::CACHE_LOCALS, false);
        PluginService::clear();
    }

    /**
     * 判断应用是否存在.
     */
    public static function exists(string $code, bool $force = false): bool
    {
        return isset(self::all($force)[$code]);
    }

    /**
     * 获取全部应用定义.
     *
     * @param bool $force 强制刷新
     * @return array<string, array<string, string>>
     */
    public static function all(bool $force = false): array
    {
        if (!$force && is_array($apps = sysvar(self::CACHE_APPS))) {
            return $apps;
        }

        $apps = array_merge(self::local($force), self::plugins($force));
        ksort($apps);
        return sysvar(self::CACHE_APPS, $apps);
    }

    /**
     * 获取本地 app/* 应用定义.
     *
     * @return array<string, array<string, string>>
     */
    public static function local(bool $force = false): array
    {
        if (!$force && is_array($apps = sysvar(self::CACHE_LOCALS))) {
            return $apps;
        }

        $apps = self::discoverLocalApps();
        ksort($apps);
        return sysvar(self::CACHE_LOCALS, $apps);
    }

    /**
     * 获取插件应用定义.
     *
     * @return array<string, array<string, string>>
     */
    public static function plugins(bool $force = false): array
    {
        return PluginService::all(false, $force);
    }

    /**
     * 获取全部应用编号.
     *
     * @return string[]
     */
    public static function codes(bool $force = false): array
    {
        return array_keys(self::all($force));
    }

    /**
     * 获取默认本地应用编号.
     */
    public static function singleCode(): string
    {
        $apps = self::local();
        $code = strval(Library::$sapp->config->get('route.default_app') ?: Library::$sapp->config->get('app.single_app') ?: '');
        if ($code !== '' && !in_array($code, self::IGNORE_LOCAL_APPS, true) && isset($apps[$code])) {
            return $code;
        }
        if (isset($apps['index'])) {
            return 'index';
        }
        return strval(array_key_first($apps) ?: 'index');
    }

    /**
     * 获取指定应用定义.
     *
     * @param ?string $code 应用编号
     */
    public static function get(?string $code = null, bool $force = false): ?array
    {
        $apps = self::all($force);
        return is_null($code) ? $apps : ($apps[$code] ?? null);
    }

    /**
     * 按首段路径命中本地应用.
     *
     * @return null|array<string, mixed>
     */
    public static function matchPath(string $pathinfo, bool $force = false): ?array
    {
        $pathinfo = trim($pathinfo, '\/');
        if ($pathinfo === '') {
            return null;
        }

        [$prefix, $suffix] = array_pad(explode('/', $pathinfo, 2), 2, '');
        if (strpos($prefix, '.')) {
            $prefix = strstr($prefix, '.', true) ?: $prefix;
        }

        if ($prefix === '' || !($app = self::localApp($prefix, $force))) {
            return null;
        }

        $app['matched_prefix'] = $prefix;
        $app['pathinfo'] = $suffix;
        return $app;
    }

    /**
     * 获取指定本地应用定义.
     */
    public static function localApp(?string $code = null, bool $force = false): ?array
    {
        $apps = self::local($force);
        return is_null($code) ? null : ($apps[$code] ?? null);
    }

    /**
     * 扫描 app/* 本地应用目录.
     *
     * @return array<string, array<string, string>>
     */
    private static function discoverLocalApps(): array
    {
        $apps = [];
        $basePath = rtrim(Library::$sapp->getBasePath(), '\/') . DIRECTORY_SEPARATOR;
        foreach (scandir($basePath) ?: [] as $code) {
            if ($code === '.' || $code === '..' || in_array($code, self::IGNORE_LOCAL_APPS, true)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $code)) {
                continue;
            }

            $path = $basePath . $code . DIRECTORY_SEPARATOR;
            if (!is_dir($path) || !self::isLocalAppPath($path)) {
                continue;
            }

            $apps[$code] = self::normalize($code, [
                'type' => 'local',
                'name' => ucfirst($code),
                'path' => $path,
                'space' => NodeService::space($code),
            ]);
        }

        return $apps;
    }

    /**
     * 判断是否为本地应用目录.
     */
    private static function isLocalAppPath(string $path): bool
    {
        foreach (['controller', 'route', 'view', 'config'] as $name) {
            if (is_dir($path . $name)) {
                return true;
            }
        }

        return is_file($path . 'Service.php');
    }

    /**
     * 标准化应用定义.
     *
     * @param string $code 应用编号
     * @param array<string, mixed> $app 应用配置
     * @return array<string, string>
     */
    private static function normalize(string $code, array $app): array
    {
        $path = $app['path'] ?? '';
        $path = $path === '' ? '' : rtrim((string)$path, '\/') . DIRECTORY_SEPARATOR;

        return [
            'code' => $code,
            'type' => $app['type'] ?? 'local',
            'name' => $app['name'] ?? ucfirst($code),
            'path' => $path,
            'alias' => $app['alias'] ?? '',
            'space' => $app['space'] ?? NodeService::space($code),
            'package' => $app['package'] ?? '',
            'service' => $app['service'] ?? '',
        ];
    }
}
