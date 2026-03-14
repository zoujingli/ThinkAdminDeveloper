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
use think\admin\service\Service;
use think\admin\runtime\RequestContext;
use think\Request;

/**
 * 插件注册中心服务.
 * @class PluginService
 */
class PluginService extends Service
{
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
     * 清理插件缓存。
     */
    public static function clear(): void
    {
        sysvar(self::CACHE_PLUGINS, false);
        sysvar(self::CACHE_ALIASES, false);
        sysvar(self::CACHE_BINDINGS, false);
    }

    /**
     * 获取全部插件定义。
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function all(bool $append = false, bool $force = false): array
    {
        if (!$append && !$force && is_array($plugins = sysvar(self::CACHE_PLUGINS))) {
            return $plugins;
        }

        $plugins = [];
        foreach ((array)Plugin::get() as $code => $plugin) {
            $plugins[$code] = self::normalize($code, $plugin);
        }

        ksort($plugins);
        if (!$append) {
            sysvar(self::CACHE_PLUGINS, $plugins);
            return $plugins;
        }

        return array_map(static function (array $plugin): array {
            return self::appendInstall($plugin);
        }, $plugins);
    }

    /**
     * 获取指定插件定义。
     * @param ?string $code 插件编码
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function get(?string $code = null, bool $append = false, bool $force = false): ?array
    {
        $plugins = self::all($append, $force);
        return is_null($code) ? $plugins : ($plugins[$code] ?? null);
    }

    /**
     * 判断插件是否存在。
     * @param string $code 插件编码或别名
     * @param bool $force 强制刷新
     */
    public static function exists(string $code, bool $force = false): bool
    {
        return self::resolve($code, false, $force) !== null;
    }

    /**
     * 获取插件别名映射。
     * @param bool $force 强制刷新
     * @return array<string, string>
     */
    public static function aliases(bool $force = false): array
    {
        if (!$force && is_array($aliases = sysvar(self::CACHE_ALIASES))) {
            return $aliases;
        }

        $aliases = [];
        foreach (self::all(false, $force) as $code => $plugin) {
            if (!empty($plugin['alias'])) {
                $aliases[$plugin['alias']] = $code;
            }
        }

        ksort($aliases);
        return sysvar(self::CACHE_ALIASES, $aliases);
    }

    /**
     * 获取插件前缀绑定关系。
     * @param bool $force 强制刷新
     * @return array<string, string>
     */
    public static function bindings(bool $force = false): array
    {
        if (!$force && is_array($bindings = sysvar(self::CACHE_BINDINGS))) {
            return $bindings;
        }

        $bindings = [];
        foreach (self::all(false, $force) as $code => $plugin) {
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
     * 获取插件前缀集合。
     * @param ?string $code 插件编码
     * @param bool $force 强制刷新
     * @return array<string>|array<string, array<string>>
     */
    public static function prefixes(?string $code = null, bool $force = false): array
    {
        $plugins = self::all(false, $force);
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
     * 获取插件主访问前缀。
     * @param string $code 插件编码
     * @param bool $force 强制刷新
     */
    public static function prefix(string $code, bool $force = false): string
    {
        return self::activePrefix($code, $force) ?: (self::prefixes($code, $force)[0] ?? '');
    }

    /**
     * 获取当前激活前缀。
     * @param ?string $code 插件编码
     * @param bool $force 强制刷新
     */
    public static function activePrefix(?string $code = null, bool $force = false): string
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
        return in_array($prefix, self::prefixes($current, $force), true) ? $prefix : '';
    }

    /**
     * 解析插件编码或别名。
     * @param ?string $name 插件编码或别名
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function resolve(?string $name, bool $append = false, bool $force = false): ?array
    {
        if ($name === null || $name === '') {
            return null;
        }

        $plugins = self::all($append, $force);
        if (isset($plugins[$name])) {
            return $plugins[$name];
        }

        $bind = self::bindings($force)[$name] ?? null;
        if ($bind && isset($plugins[$bind])) {
            return $plugins[$bind];
        }

        $code = self::aliases($force)[$name] ?? null;
        return $code ? ($plugins[$code] ?? null) : null;
    }

    /**
     * 按首段路径命中插件。
     * @param string $pathinfo 请求路径
     * @param ?string $switch 动态插件切换
     */
    public static function matchPath(string $pathinfo, ?string $switch = null): ?array
    {
        $pathinfo = trim($pathinfo, '\/');
        if ($pathinfo !== '') {
            $paths = explode('/', $pathinfo, 2);
            $prefix = strval($paths[0] ?? '');
            if (strpos($prefix, '.')) {
                $prefix = strstr($prefix, '.', true) ?: $prefix;
            }
            if ($prefix !== '' && ($plugin = self::resolvePrefix($prefix))) {
                $plugin['matched_prefix'] = $prefix;
                $plugin['pathinfo'] = $paths[1] ?? '';
                return $plugin;
            }
        }

        if ($switch && ($plugin = self::resolve($switch))) {
            $plugin['matched_prefix'] = '';
            $plugin['pathinfo'] = $pathinfo;
            return $plugin;
        }

        return null;
    }

    /**
     * 解析指定前缀绑定。
     * @param ?string $prefix 路由前缀
     * @param bool $append 关联安装信息
     * @param bool $force 强制刷新
     */
    public static function resolvePrefix(?string $prefix, bool $append = false, bool $force = false): ?array
    {
        if ($prefix === null || $prefix === '') {
            return null;
        }

        $code = self::bindings($force)[$prefix] ?? null;
        return $code ? self::get($code, $append, $force) : null;
    }

    /**
     * 检测动态插件切换参数。
     */
    public static function detectSwitch(?Request $request = null): ?string
    {
        $config = self::switchConfig();
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
     * @param null|string|array $plugin 插件编码或定义
     * @param string $prefix 当前请求前缀
     */
    public static function activate($plugin = null, string $prefix = ''): ?array
    {
        $context = RequestContext::instance();
        if (is_array($plugin)) {
            $code = strval($plugin['code'] ?? '');
            $current = $code === '' ? null : self::resolve($code);
        } else {
            $current = empty($plugin) ? null : self::resolve(strval($plugin));
        }

        if (empty($current)) {
            $context->clearPlugin();
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
    public static function current(bool $append = false, bool $force = false): ?array
    {
        $code = RequestContext::instance()->pluginCode();
        return $code === '' ? null : self::get($code, $append, $force);
    }

    /**
     * 获取当前请求插件编码。
     */
    public static function currentCode(): string
    {
        return RequestContext::instance()->pluginCode();
    }

    /**
     * 获取当前请求插件前缀。
     */
    public static function currentPrefix(): string
    {
        return RequestContext::instance()->pluginPrefix();
    }

    /**
     * 获取插件菜单根节点配置。
     * @param null|string|array $plugin 插件编码或定义
     */
    public static function menuRoot($plugin): array
    {
        $current = self::plugin($plugin, true);
        if (empty($current['service']) || !class_exists($current['service'])) {
            return [];
        }

        return (array)$current['service']::getMenuRoot();
    }

    /**
     * 获取插件菜单存在检测条件。
     * @param null|string|array $plugin 插件编码或定义
     */
    public static function menuExists($plugin): array
    {
        $current = self::plugin($plugin, true);
        if (empty($current['service']) || !class_exists($current['service'])) {
            return [];
        }

        return (array)$current['service']::getMenuExists();
    }

    /**
     * 获取插件菜单定义。
     * @param null|string|array $plugin 插件编码或定义
     * @param bool $check 检查权限
     * @param bool $normalize 标准化输出
     */
    public static function menus($plugin, bool $check = false, bool $normalize = false): array
    {
        $current = self::plugin($plugin, true);
        if (empty($current['service']) || !class_exists($current['service'])) {
            return [];
        }

        $menus = (array)$current['service']::menu();
        return ($check || $normalize) ? self::normalizeMenus($menus, $check) : $menus;
    }

    /**
     * 获取插件声明的菜单节点。
     * @param null|string|array $plugin 插件编码、服务类或定义
     * @return string[]
     */
    public static function menuNodes($plugin): array
    {
        $nodes = [];
        static::collectMenuNodes(self::menus($plugin), $nodes);
        $nodes = array_values(array_unique(array_filter(array_map('strval', $nodes))));
        sort($nodes);
        return $nodes;
    }

    /**
     * 获取插件菜单节点与权限声明。
     * @param null|string|array $plugin 插件编码、服务类或定义
     * @return array<int, array<string, mixed>>
     */
    public static function menuBindings($plugin, bool $force = false): array
    {
        $methods = NodeService::getMethods($force);
        $bindings = [];
        foreach (self::menuNodes($plugin) as $node) {
            $meta = (array)($methods[$node] ?? []);
            $bindings[] = [
                'node' => $node,
                'title' => strval($meta['title'] ?? ''),
                'isauth' => intval($meta['isauth'] ?? 0),
                'ismenu' => intval($meta['ismenu'] ?? 0),
                'islogin' => intval($meta['islogin'] ?? 0),
                'exists' => !empty($meta),
            ];
        }
        return $bindings;
    }

    /**
     * 获取插件菜单声明异常项。
     * @param null|string|array $plugin 插件编码、服务类或定义
     * @return array<int, array<string, mixed>>
     */
    public static function invalidMenus($plugin, bool $force = false): array
    {
        return array_values(array_filter(self::menuBindings($plugin, $force), static function (array $item): bool {
            return empty($item['exists']) || empty($item['isauth']) || empty($item['ismenu']);
        }));
    }

    /**
     * 校验插件菜单声明。
     * @param null|string|array $plugin 插件编码、服务类或定义
     */
    public static function assertMenus($plugin, bool $force = false): void
    {
        if (empty($invalid = self::invalidMenus($plugin, $force))) {
            return;
        }

        $current = self::plugin($plugin, true, $force);
        $label = strval($current['code'] ?? $current['service'] ?? (is_string($plugin) ? $plugin : 'plugin'));
        $errors = [];
        foreach ($invalid as $item) {
            $reasons = [];
            empty($item['exists']) && $reasons[] = 'node not found';
            empty($item['isauth']) && $reasons[] = '@auth true required';
            empty($item['ismenu']) && $reasons[] = '@menu true required';
            $errors[] = sprintf('%s (%s)', $item['node'], join(', ', $reasons));
        }

        throw new \RuntimeException(sprintf('Plugin menu binding invalid [%s]: %s', $label, join('; ', $errors)));
    }

    /**
     * 标准化插件定义。
     * @param string $code 插件编码
     * @param array $plugin 原始定义
     */
    private static function normalize(string $code, array $plugin): array
    {
        $path = $plugin['path'] ?? '';
        $path = $path === '' ? '' : rtrim((string)$path, '\/') . DIRECTORY_SEPARATOR;
        $prefixes = self::effectivePrefixes($code, $plugin);

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
     * 附加安装信息。
     * @param array $plugin 插件定义
     */
    private static function appendInstall(array $plugin): array
    {
        $versions = ModuleService::getLibrarys();
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
     * 获取插件切换配置。
     * @return array{enabled:bool,query:string,header:string}
     */
    private static function switchConfig(): array
    {
        $config = (array)Library::$sapp->config->get('app.plugin.switch', []);
        return [
            'enabled' => isset($config['enabled']) ? boolval($config['enabled']) : false,
            'query' => strval($config['query'] ?? '_plugin'),
            'header' => strval($config['header'] ?? 'X-Plugin-App'),
        ];
    }

    /**
     * 获取插件有效前缀。
     * @param string $code 插件编码
     * @param array $plugin 插件定义
     * @return string[]
     */
    private static function effectivePrefixes(string $code, array $plugin): array
    {
        $prefixes = self::configuredPrefixes($code);
        if ($prefixes === null) {
            $prefixes = self::normalizePrefixes($plugin['prefixes'] ?? [], $plugin['prefix'] ?? '', $plugin['alias'] ?? '', $code);
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
    private static function configuredPrefixes(string $code): ?array
    {
        $config = (array)Library::$sapp->config->get('app.plugin.bindings', []);
        if (array_key_exists($code, $config)) {
            return self::normalizePrefixes($config[$code]);
        }

        foreach ($config as $item) {
            if (!is_array($item)) {
                continue;
            }
            $name = strval($item['code'] ?? $item['plugin'] ?? '');
            if ($name === $code) {
                return self::normalizePrefixes($item['prefixes'] ?? ($item['prefix'] ?? []));
            }
        }

        return null;
    }

    /**
     * 标准化前缀集合。
     * @param mixed ...$values 原始前缀
     * @return string[]
     */
    private static function normalizePrefixes(...$values): array
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
     * 解析插件定义，支持插件编码、别名、前缀、服务类或定义数组。
     * @param null|string|array $plugin 插件编码、服务类或定义
     */
    private static function plugin($plugin, bool $append = false, bool $force = false): ?array
    {
        if (is_array($plugin)) {
            return $plugin;
        }

        $name = trim(strval($plugin));
        if ($name === '') {
            return null;
        }

        if (($current = self::resolve($name, $append, $force)) !== null) {
            return $current;
        }

        foreach (self::all($append, $force) as $item) {
            if (($item['service'] ?? '') === $name) {
                return $item;
            }
        }

        return null;
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
}
