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
use think\admin\Plugin;
use think\admin\runtime\RequestContext;
use think\Request;

/**
 * 应用注册与插件管理中心服务
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
     * 插件缓存键名。
     */
    private const CACHE_PLUGINS = 'think.admin.plugins';

    /**
     * 插件别名缓存键名。
     */
    private const CACHE_ALIASES = 'think.admin.plugins.aliases';

    /**
     * 插件前缀绑定缓存键名。
     */
    private const CACHE_BINDINGS = 'think.admin.plugins.bindings';

    /**
     * API 入口缓存键名。
     */
    private const CACHE_ENTRY = 'think.admin.plugins.entry';

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
        sysvar(self::CACHE_PLUGINS, false);
        sysvar(self::CACHE_ALIASES, false);
        sysvar(self::CACHE_BINDINGS, false);
        sysvar(self::CACHE_ENTRY, false);
    }

    /**
     * 获取本地模块列表。
     * @param array $data 额外数据
     * @return array
     */
    public static function getModules(array $data = []): array
    {
        return array_values(array_unique(array_merge($data, array_keys(self::local()))));
    }

    /**
     * 获取全部应用列表。
     * @param array $data 额外数据
     * @return array
     */
    public static function getApps(array $data = []): array
    {
        return array_values(array_unique(array_merge($data, array_keys(self::all()))));
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
        return self::allPlugins(false, $force);
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
     * 判断插件是否存在。
     * @param string $code 插件编码或别名
     * @param bool $force 强制刷新
     */
    public static function pluginExists(string $code, bool $force = false): bool
    {
        return self::resolvePlugin($code, false, $force) !== null;
    }

    /**
     * 解析插件编码或别名。
     * @param ?string $name 插件编码或别名
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function resolvePlugin(?string $name, bool $append = false, bool $force = false): ?array
    {
        if ($name === null || $name === '') {
            return null;
        }

        $plugins = self::allPlugins($append, $force);
        if (isset($plugins[$name])) {
            return $plugins[$name];
        }

        $bind = self::pluginBindings($force)[$name] ?? null;
        if ($bind && isset($plugins[$bind])) {
            return $plugins[$bind];
        }

        $code = self::pluginAliases($force)[$name] ?? null;
        return $code ? ($plugins[$code] ?? null) : null;
    }

    /**
     * 获取全部插件定义。
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function allPlugins(bool $append = false, bool $force = false): array
    {
        if (!$append && !$force && is_array($plugins = sysvar(self::CACHE_PLUGINS))) {
            return $plugins;
        }

        $plugins = [];
        foreach ((array)Plugin::get() as $code => $plugin) {
            $plugins[$code] = self::normalizePlugin($code, $plugin);
        }

        ksort($plugins);
        if (!$append) {
            sysvar(self::CACHE_PLUGINS, $plugins);
            return $plugins;
        }

        return array_map(static function (array $plugin): array {
            return self::appendPluginInstall($plugin);
        }, $plugins);
    }

    /**
     * 获取指定插件定义。
     * @param ?string $code 插件编码
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function getPlugin(?string $code = null, bool $append = false, bool $force = false): ?array
    {
        $plugins = self::allPlugins($append, $force);
        return is_null($code) ? $plugins : ($plugins[$code] ?? null);
    }

    /**
     * 获取插件前缀绑定关系。
     * @param bool $force 强制刷新
     * @return array<string, string>
     */
    public static function pluginBindings(bool $force = false): array
    {
        if (!$force && is_array($bindings = sysvar(self::CACHE_BINDINGS))) {
            return $bindings;
        }

        $bindings = [];
        foreach (self::allPlugins(false, $force) as $code => $plugin) {
            foreach ($plugin['prefixes'] ?? [] as $prefix) {
                if ($prefix === '') {
                    continue;
                }
                if (isset($bindings[$prefix]) && $bindings[$prefix] !== $code) {
                    throw new \RuntimeException("Plugin prefix conflict [{$prefix}] between [{$bindings[$prefix]}] and [{$code}]");
                }
                $bindings[$prefix] = $code;
            }
        }

        ksort($bindings);
        return sysvar(self::CACHE_BINDINGS, $bindings);
    }

    /**
     * 获取插件别名映射。
     * @param bool $force 强制刷新
     * @return array<string, string>
     */
    public static function pluginAliases(bool $force = false): array
    {
        if (!$force && is_array($aliases = sysvar(self::CACHE_ALIASES))) {
            return $aliases;
        }

        $aliases = [];
        foreach (self::allPlugins(false, $force) as $code => $plugin) {
            if (!empty($plugin['alias'])) {
                $aliases[$plugin['alias']] = $code;
            }
        }

        ksort($aliases);
        return sysvar(self::CACHE_ALIASES, $aliases);
    }

    /**
     * 获取插件主访问前缀。
     * @param string $code 插件编码
     * @param bool $force 强制刷新
     */
    public static function pluginPrefix(string $code, bool $force = false): string
    {
        return self::activePluginPrefix($code, $force) ?: (self::pluginPrefixes($code, $force)[0] ?? '');
    }

    /**
     * 获取当前激活前缀。
     * @param ?string $code 插件编码
     * @param bool $force 强制刷新
     */
    public static function activePluginPrefix(?string $code = null, bool $force = false): string
    {
        $context = RequestContext::instance();
        $current = $context->pluginCode();
        $prefix = $context->pluginPrefix();
        if ($current === '' || $prefix === '') {
            return '';
        }
        if ($code !== null && $current !== $code) {
            return '';
        }
        return in_array($prefix, self::pluginPrefixes($current, $force), true) ? $prefix : '';
    }

    /**
     * 获取插件前缀集合。
     * @param ?string $code 插件编码
     * @param bool $force 强制刷新
     * @return array<string, array<string>>|array<string>
     */
    public static function pluginPrefixes(?string $code = null, bool $force = false): array
    {
        $plugins = self::allPlugins(false, $force);
        if ($code === null) {
            $items = [];
            foreach ($plugins as $name => $plugin) {
                $items[$name] = $plugin['prefixes'] ?? [];
            }
            return $items;
        }

        return $plugins[$code]['prefixes'] ?? [];
    }

    /**
     * 按首段路径命中插件。
     * @param string $pathinfo 请求路径
     * @param ?string $switch 动态插件切换
     */
    public static function matchPluginPath(string $pathinfo, ?string $switch = null): ?array
    {
        $pathinfo = trim($pathinfo, '\/');
        $apiPrefix = self::pluginApiPrefix();
        if ($pathinfo !== '' && $apiPrefix !== '') {
            $paths = explode('/', $pathinfo, 3);
            if (strval($paths[0] ?? '') === $apiPrefix) {
                $prefix = strval($paths[1] ?? '');
                if ($prefix !== '' && ($plugin = self::resolvePluginPrefix($prefix))) {
                    $plugin['entry'] = RequestContext::ENTRY_API;
                    $plugin['matched_prefix'] = $prefix;
                    $plugin['pathinfo'] = self::normalizeApiPathinfo(strval($paths[2] ?? 'index/index'));
                    return $plugin;
                }
            }
        }

        if ($pathinfo !== '') {
            $paths = explode('/', $pathinfo, 2);
            $prefix = strval($paths[0] ?? '');
            if (strpos($prefix, '.')) {
                $prefix = strstr($prefix, '.', true) ?: $prefix;
            }
            if ($prefix !== '' && ($plugin = self::resolvePluginPrefix($prefix))) {
                $plugin['entry'] = RequestContext::ENTRY_WEB;
                $plugin['matched_prefix'] = $prefix;
                $plugin['pathinfo'] = $paths[1] ?? '';
                return $plugin;
            }
        }

        if ($switch && ($plugin = self::resolvePlugin($switch))) {
            $plugin['entry'] = RequestContext::ENTRY_WEB;
            $plugin['matched_prefix'] = '';
            $plugin['pathinfo'] = $pathinfo;
            return $plugin;
        }

        return null;
    }

    /**
     * 获取 API 入口前缀。
     */
    public static function pluginApiPrefix(): string
    {
        if (is_string($entry = sysvar(self::CACHE_ENTRY)) && $entry !== '') {
            return $entry;
        }
        $entry = trim(strval(Library::$sapp->config->get('app.plugin.api_prefix', 'api')), '\/');
        $entry = $entry !== '' ? $entry : 'api';
        sysvar(self::CACHE_ENTRY, $entry);
        return $entry;
    }

    /**
     * 解析指定前缀绑定。
     * @param ?string $prefix 路由前缀
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function resolvePluginPrefix(?string $prefix, bool $append = false, bool $force = false): ?array
    {
        if ($prefix === null || $prefix === '') {
            return null;
        }

        $code = self::pluginBindings($force)[$prefix] ?? null;
        return $code ? self::getPlugin($code, $append, $force) : null;
    }

    /**
     * 检测动态插件切换参数。
     */
    public static function detectPluginSwitch(?Request $request = null): ?string
    {
        $config = self::pluginSwitchConfig();
        if (empty($config['enabled'])) {
            return null;
        }

        $request = $request ?: Library::$sapp->request;
        $header = trim(strval($config['header'] ?? ''));
        $query = trim(strval($config['query'] ?? ''));
        $value = $header === '' ? '' : trim(strval($request->header($header) ?: ''));
        if ($value === '' && $query !== '') {
            $value = trim(strval($request->get($query, '')));
        }

        return $value === '' ? null : $value;
    }

    /**
     * 激活当前请求插件。
     * @param null|array|string $plugin 插件编码或定义
     * @param string $prefix 当前请求前缀
     */
    public static function activatePlugin($plugin = null, string $prefix = ''): ?array
    {
        $context = RequestContext::instance();
        if (is_array($plugin)) {
            $code = strval($plugin['code'] ?? '');
            $current = $code === '' ? null : self::resolvePlugin($code);
        } else {
            $current = empty($plugin) ? null : self::resolvePlugin(strval($plugin));
        }

        if (empty($current)) {
            $context->clearPlugin()->setEntryType(RequestContext::ENTRY_WEB);
            return null;
        }

        $prefix = trim($prefix, '\/');
        if ($prefix === '' || !in_array($prefix, $current['prefixes'] ?? [], true)) {
            $prefix = $current['prefixes'][0] ?? '';
        }

        $context->setPlugin($current['code'], $prefix);
        return $current;
    }

    /**
     * 获取当前请求插件。
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function currentPlugin(bool $append = false, bool $force = false): ?array
    {
        $code = RequestContext::instance()->pluginCode();
        return $code === '' ? null : self::getPlugin($code, $append, $force);
    }

    /**
     * 获取当前请求插件前缀。
     */
    public static function currentPluginPrefix(): string
    {
        return RequestContext::instance()->pluginPrefix();
    }

    /**
     * 设置当前请求插件入口类型。
     */
    public static function activatePluginEntry(string $entryType = RequestContext::ENTRY_WEB): void
    {
        RequestContext::instance()->setEntryType($entryType);
    }

    /**
     * 获取当前请求插件入口类型。
     */
    public static function currentPluginEntry(): string
    {
        return RequestContext::instance()->entryType();
    }

    /**
     * 获取当前请求插件编码。
     */
    public static function currentPluginCode(): string
    {
        return RequestContext::instance()->pluginCode();
    }

    /**
     * 获取插件应用定义（兼容旧版 plugins 方法）。
     *
     * @return array<string, array<string, string>>
     */
    public static function plugins(bool $force = false): array
    {
        return self::allPlugins(false, $force);
    }

    /**
     * 获取当前请求插件编码（兼容旧版 currentCode）。
     */
    public static function currentCode(): string
    {
        return RequestContext::instance()->pluginCode();
    }

    /**
     * 获取当前请求入口类型（兼容旧版 currentEntry）。
     */
    public static function currentEntry(): string
    {
        return RequestContext::instance()->entryType();
    }

    /**
     * 设置当前请求入口类型（兼容旧版 activateEntry）。
     */
    public static function activateEntry(string $entryType = RequestContext::ENTRY_WEB): void
    {
        RequestContext::instance()->setEntryType($entryType);
    }

    /**
     * 激活插件（兼容旧版 activate 方法）。
     * @param null|array|string $plugin 插件编码或定义
     * @param string $prefix 当前请求前缀
     */
    public static function activate($plugin = null, string $prefix = ''): ?array
    {
        return self::activatePlugin($plugin, $prefix);
    }

    /**
     * 解析插件定义，支持插件编码、别名、前缀、服务类或定义数组（兼容旧版 resolve）。
     * @param null|array|string $plugin 插件编码、服务类或定义
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function resolve($plugin, bool $append = false, bool $force = false): ?array
    {
        if (is_array($plugin)) {
            return $plugin;
        }

        $name = trim(strval($plugin));
        if ($name === '') {
            return null;
        }

        if (($current = self::resolvePlugin($name, $append, $force)) !== null) {
            return $current;
        }

        foreach (self::allPlugins($append, $force) as $item) {
            if (($item['service'] ?? '') === $name) {
                return $item;
            }
        }

        return null;
    }

    /**
     * 获取插件定义（兼容旧版 get 方法）。
     * @param null|array|string $plugin 插件编码或定义
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function get($plugin = null, bool $append = false, bool $force = false): ?array
    {
        if (is_null($plugin)) {
            return self::allPlugins($append, $force);
        }
        
        if (is_array($plugin)) {
            return $plugin;
        }

        $name = trim(strval($plugin));
        if ($name === '') {
            return null;
        }

        if (($current = self::resolvePlugin($name, $append, $force)) !== null) {
            return $current;
        }

        foreach (self::allPlugins($append, $force) as $item) {
            if (($item['service'] ?? '') === $name) {
                return $item;
            }
        }

        return null;
    }

    /**
     * 获取插件菜单定义。
     * @param null|array|string $plugin 插件编码或定义
     * @param bool $check 检查权限
     * @param bool $normalize 标准化输出
     */
    public static function menus($plugin, bool $check = false, bool $normalize = false): array
    {
        $current = self::get($plugin, true);
        if (empty($current['service']) || !class_exists($current['service'])) {
            return [];
        }

        $menus = (array)$current['service']::menu();
        return ($check || $normalize) ? self::normalizeMenus($menus, $check) : $menus;
    }

    /**
     * 递归收集菜单节点。
     * @param array<int, array<string, mixed>> $menus
     * @param array<int, string> $nodes
     */
    private static function collectMenuNodes(array $menus, array &$nodes): void
    {
        foreach ($menus as $menu) {
            if (!empty($menu['node'])) {
                $nodes[] = strval($menu['node']);
            }
            if (!empty($menu['subs'])) {
                self::collectMenuNodes((array)$menu['subs'], $nodes);
            }
        }
    }

    /**
     * 标准化插件菜单并可选按权限过滤。
     * @param array<int, array<string, mixed>> $menus
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeMenus(array $menus, bool $check = false): array
    {
        foreach ($menus as $k1 => &$one) {
            $one['title'] = lang($one['title'] ?? ($one['name'] ?? ''));
            $one['url'] = $one['url'] ?? static::buildMenuUrl(strval($one['node'] ?? ''));
            if (!empty($one['subs'])) {
                foreach ($one['subs'] as $k2 => &$two) {
                    if ($check && isset($two['node']) && !auth($two['node'])) {
                        unset($one['subs'][$k2]);
                        continue;
                    }
                    $two['title'] = lang($two['title'] ?? ($two['name'] ?? ''));
                    $two['url'] = $two['url'] ?? static::buildMenuUrl(strval($two['node'] ?? ''));
                }
                $one['subs'] = array_values($one['subs']);
            }

            if ($check && isset($one['node']) && !auth($one['node'])) {
                unset($menus[$k1]);
                continue;
            }
            if ($one['url'] === '#' && empty($one['subs'])) {
                unset($menus[$k1]);
            }
        }

        return array_values($menus);
    }

    /**
     * 生成菜单 URL，缺少插件上下文时回退为系统 URL。
     */
    private static function buildMenuUrl(string $node): string
    {
        if ($node === '') {
            return '#';
        }

        if (function_exists('plguri') && self::currentCode() !== '') {
            return plguri($node);
        }

        return sysuri($node);
    }

    /**
     * 获取版本信息。
     * @return string
     */
    public static function getVersion(): string
    {
        $library = self::getLibrarys('zoujingli/think-library');
        return trim($library['version'] ?? 'v8.0.0', 'v');
    }

    /**
     * 获取 PHP 可执行文件路径。
     * @return string
     */
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

    /**
     * 获取运行时二进制文件。
     * @param string $field 字段名
     * @return string
     */
    public static function getRunVar(string $field): string
    {
        $file = syspath('vendor/binarys.php');
        if (is_file($file) && is_array($binarys = include $file)) {
            return $binarys[$field] ?? '';
        }
        return '';
    }

    /**
     * 获取插件包版本信息（兼容旧版 getLibrarys）。
     * @param ?string $package 包名
     * @param bool $force 强制刷新
     * @return array|mixed
     */
    public static function getLibrarys(?string $package = null, bool $force = false)
    {
        return self::getPluginLibrarys($package, $force);
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

    /**
     * 标准化插件定义。
     * @param string $code 插件编码
     * @param array $plugin 原始定义
     */
    private static function normalizePlugin(string $code, array $plugin): array
    {
        $path = $plugin['path'] ?? '';
        $path = $path === '' ? '' : rtrim((string)$path, '\/') . DIRECTORY_SEPARATOR;
        $prefixes = self::effectivePluginPrefixes($code, $plugin);

        return [
            'code' => $code,
            'type' => $plugin['type'] ?? 'plugin',
            'name' => $plugin['name'] ?? ucfirst($code),
            'path' => $path,
            'alias' => $plugin['alias'] ?? '',
            'prefix' => $prefixes[0] ?? '',
            'prefixes' => $prefixes,
            'space' => $plugin['space'] ?? '',
            'package' => $plugin['package'] ?? '',
            'service' => $plugin['service'] ?? '',
            'document' => $plugin['document'] ?? '',
            'description' => $plugin['description'] ?? '',
            'platforms' => (array)($plugin['platforms'] ?? []),
            'license' => (array)($plugin['license'] ?? []),
            'version' => strval($plugin['version'] ?? ''),
            'homepage' => strval($plugin['homepage'] ?? ''),
        ];
    }

    /**
     * 获取插件有效前缀。
     * @param string $code 插件编码
     * @param array $plugin 插件定义
     * @return string[]
     */
    private static function effectivePluginPrefixes(string $code, array $plugin): array
    {
        $prefixes = self::configuredPluginPrefixes($code);
        if ($prefixes === null) {
            $prefixes = self::normalizePluginPrefixes($plugin['prefixes'] ?? [], $plugin['prefix'] ?? '', $plugin['alias'] ?? '', $code);
        }
        if (empty($prefixes)) {
            $prefixes = [$code];
        }
        return $prefixes;
    }

    /**
     * 获取配置文件中的插件前缀定义。
     * @param string $code 插件编码
     * @return null|string[]
     */
    private static function configuredPluginPrefixes(string $code): ?array
    {
        $config = (array)Library::$sapp->config->get('app.plugin.bindings', []);
        if (array_key_exists($code, $config)) {
            return self::normalizePluginPrefixes($config[$code]);
        }

        foreach ($config as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = strval($item['code'] ?? $item['plugin'] ?? '');
            if ($name === $code) {
                return self::normalizePluginPrefixes($item['prefixes'] ?? ($item['prefix'] ?? []));
            }
        }

        return null;
    }

    /**
     * 标准化前缀集合。
     * @param mixed ...$values 原始前缀
     * @return string[]
     */
    private static function normalizePluginPrefixes(...$values): array
    {
        $items = [];
        foreach ($values as $value) {
            foreach ((array)$value as $prefix) {
                $prefix = trim((string)$prefix, " \t\n\r\0\x0B\\/");
                if ($prefix === '') {
                    continue;
                }
                if (strpos($prefix, '/')) {
                    $prefix = strstr($prefix, '/', true) ?: $prefix;
                }
                if (strpos($prefix, '.')) {
                    $prefix = strstr($prefix, '.', true) ?: $prefix;
                }
                if ($prefix !== '' && !in_array($prefix, $items, true)) {
                    $items[] = $prefix;
                }
            }
        }

        return $items;
    }

    /**
     * 附加插件安装信息。
     * @param array $plugin 插件定义
     */
    private static function appendPluginInstall(array $plugin): array
    {
        $versions = self::getPluginLibrarys();
        $plugin['install'] = $versions[$plugin['package']] ?? [];
        foreach (['type', 'name', 'document', 'description', 'homepage', 'version'] as $field) {
            if (empty($plugin[$field])) {
                $plugin[$field] = $plugin['install'][$field] ?? ($field === 'type' ? 'plugin' : '');
            }
        }
        $plugin['platforms'] = array_values(array_unique(array_filter(array_merge(
            (array)($plugin['platforms'] ?? []),
            (array)($plugin['install']['platforms'] ?? [])
        ))));
        $plugin['license'] = array_values(array_unique(array_filter(array_merge(
            (array)($plugin['license'] ?? []),
            (array)($plugin['install']['license'] ?? [])
        ))));
        return $plugin;
    }

    /**
     * 获取插件包版本信息。
     * @param ?string $package 包名
     * @param bool $force 强制刷新
     * @return array|mixed
     */
    private static function getPluginLibrarys(?string $package = null, bool $force = false)
    {
        $plugs = sysvar($keys = 'think.admin.version');
        if ((empty($plugs) || $force) && is_file($file = syspath('vendor/versions.php'))) {
            $plugs = sysvar($keys, include $file);
        }
        return empty($package) ? $plugs : ($plugs[$package] ?? null);
    }

    /**
     * 获取插件切换配置。
     * @return array{enabled:bool,query:string,header:string}
     */
    private static function pluginSwitchConfig(): array
    {
        $config = (array)Library::$sapp->config->get('app.plugin.switch', []);
        return [
            'enabled' => isset($config['enabled']) ? boolval($config['enabled']) : false,
            'query' => strval($config['query'] ?? '_plugin'),
            'header' => strval($config['header'] ?? 'X-Plugin-App'),
        ];
    }

    /**
     * 标准化 API 入口路径。
     * /api/{plugin}/upload/file -> api.upload/file.
     */
    private static function normalizeApiPathinfo(string $pathinfo): string
    {
        $pathinfo = trim($pathinfo, '\/');
        if ($pathinfo === '') {
            return 'api.index/index';
        }
        if (strpos($pathinfo, 'api.') === 0) {
            return $pathinfo;
        }
        [$controller, $action] = array_pad(explode('/', $pathinfo, 2), 2, 'index');
        $controller = trim(strtr($controller, '/', '.'), '.');
        return 'api.' . $controller . '/' . trim($action, '\/');
    }

    /**
     * 生成全部静态路径。
     * @param string $path 后缀路径
     * @return string[]
     */
    public static function uris(string $path = ''): array
    {
        return static::uri($path, null);
    }

    /**
     * 生成静态路径链接。
     * @param string $path 后缀路径
     * @param ?string $type 路径类型
     * @param mixed $default 默认数据
     * @return array|string
     */
    public static function uri(string $path = '', ?string $type = '__ROOT__', $default = '')
    {
        $plugin = Library::$sapp->http->getName();
        if (strlen($path)) {
            $path = '/' . ltrim($path, '/');
        }
        $prefix = rtrim(dirname(Library::$sapp->request->basefile()), '\/');
        $data = [
            '__APP__' => rtrim(url('@')->build(), '\/') . $path,
            '__ROOT__' => $prefix . $path,
            '__PLUG__' => "{$prefix}/static/extra/{$plugin}{$path}",
            '__FULL__' => Library::$sapp->request->domain() . $prefix . $path,
        ];
        return is_null($type) ? $data : ($data[$type] ?? $default);
    }

    /**
     * 打印调试数据到文件。
     * @param mixed $data 输出的数据
     * @param bool $new 强制替换文件
     * @param null|string $file 文件名称
     * @return false|int
     */
    public static function putDebug($data, bool $new = false, ?string $file = null)
    {
        ob_start();
        var_dump($data);
        $output = preg_replace('/]=>\n(\s+)/m', '] => ', ob_get_clean());
        if (is_null($file)) {
            $file = runpath('runtime/' . date('Ymd') . '.log');
        } elseif (!preg_match('#[/\\\]+#', $file)) {
            $file = runpath("runtime/{$file}.log");
        }
        is_dir($dir = dirname($file)) or mkdir($dir, 0777, true);
        return $new ? file_put_contents($file, $output) : file_put_contents($file, $output, FILE_APPEND);
    }

    /**
     * 批量更新保存数据。
     * @param \think\Model|\think\db\Query|string $query 数据查询对象
     * @param array $data 需要保存的数据
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return bool|int
     * @throws \think\admin\Exception
     */
    public static function update($query, array $data, string $key = 'id', $map = [])
    {
        try {
            $query = \think\admin\model\QueryFactory::build($query)->master()->where($map);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            return (clone $query)->count() > 1 ? $query->strict(false)->update($data) : $query->findOrEmpty()->save($data);
        } catch (\Exception|\Throwable $exception) {
            throw new \think\admin\Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 数据增量保存。
     * @param \think\Model|\think\db\Query|string $query 数据查询对象
     * @param array $data 需要保存的数据
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return bool|int
     * @throws \think\admin\Exception
     */
    public static function save($query, array &$data, string $key = 'id', $map = [])
    {
        try {
            $query = \think\admin\model\QueryFactory::build($query)->master()->strict(false);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            $model = $query->where($map)->findOrEmpty();
            $action = $model->isExists() ? 'onAdminUpdate' : 'onAdminInsert';
            if ($model->save($data) === false) {
                return false;
            }
            if ($model instanceof \think\admin\Model) {
                $model->{$action}(strval($model->getAttr($key)));
            }
            $data = $model->toArray();
            return $model[$key] ?? true;
        } catch (\Exception $exception) {
            throw new \think\admin\Exception($exception->getMessage(), $exception->getCode());
        }
    }
}
