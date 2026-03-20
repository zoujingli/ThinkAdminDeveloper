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

namespace plugin\system\service;

use plugin\system\model\SystemMenu;
use think\admin\extend\ArrayTree;
use think\admin\Service;
use think\admin\service\AppService;
use think\admin\service\NodeService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统菜单管理服务
 * @class MenuService
 */
class MenuService extends Service
{
    /**
     * 获取可选菜单节点.
     * @param bool $force 强制刷新
     */
    public static function getList(bool $force = false, ?string $plugin = null): array
    {
        $plugin = self::pluginCode($plugin);
        $keys = 'think.admin.menus' . ($plugin === '' ? '' : ".{$plugin}");
        $nodes = sysvar($keys) ?: [];
        if (empty($force) && count($nodes) > 0) {
            return $nodes;
        }
        $nodes = [];
        foreach (NodeService::getMethods($force) as $node => $method) {
            if ($method['ismenu'] && self::allowNode($node, $plugin)) {
                $nodes[] = ['node' => $node, 'title' => self::lang($method['title'])];
            }
        }
        return sysvar($keys, $nodes);
    }

    /**
     * 获取可选权限节点.
     * @param bool $force 强制刷新
     */
    public static function getAuths(bool $force = false, ?string $plugin = null): array
    {
        $plugin = self::pluginCode($plugin);
        $keys = 'think.admin.auths' . ($plugin === '' ? '' : ".{$plugin}");
        $nodes = sysvar($keys) ?: [];
        if (empty($force) && count($nodes) > 0) {
            return $nodes;
        }

        $nodes = [];
        foreach (NodeService::getMethods($force) as $node => $method) {
            if ($method['isauth'] && substr_count($node, '/') >= 2 && self::allowNode($node, $plugin)) {
                $nodes[] = ['node' => $node, 'title' => $method['title']];
            }
        }
        return sysvar($keys, $nodes);
    }

    /**
     * 获取有菜单声明的插件列表.
     */
    public static function getPlugins(bool $force = false): array
    {
        $items = [];
        foreach (AppService::all($force) as $code => $plugin) {
            if (empty(AppService::menus($plugin))) {
                continue;
            }
            $items[$code] = [
                'code' => $code,
                'name' => strval($plugin['name'] ?? $code),
                'type' => strval($plugin['type'] ?? 'plugin'),
                'prefix' => strval($plugin['prefix'] ?? ''),
            ];
        }
        ksort($items);
        return $items;
    }

    /**
     * 获取顶级菜单列表.
     */
    public static function getRoots(?string $plugin = null): array
    {
        $roots = [];
        foreach (self::filterTree(self::loadTree(), $plugin) as $menu) {
            $roots[$menu['id']] = [
                'id' => $menu['id'],
                'pid' => $menu['pid'],
                'title' => self::lang(strval($menu['title'] ?? '')),
            ];
        }
        return $roots;
    }

    /**
     * 获取插件上下文下的上级菜单选项.
     */
    public static function getParents(?string $plugin = null): array
    {
        $items = self::filterTree(self::loadTree(false), $plugin);
        $items = ArrayTree::arr2table(array_merge($items, [[
            'id' => '0', 'pid' => '-1', 'url' => '#', 'title' => '顶部菜单',
        ]]));

        foreach ($items as $key => $menu) {
            if ($menu['spt'] >= 3 || $menu['url'] !== '#') {
                unset($items[$key]);
            }
        }

        return array_values($items);
    }

    /**
     * 过滤指定插件菜单树.
     */
    public static function filterTree(array $menus, ?string $plugin = null): array
    {
        if (($plugin = self::pluginCode($plugin)) === '') {
            return $menus;
        }

        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = self::filterTree($menu['sub'], $plugin);
            }
            if (self::detectPlugin($menu) !== $plugin && empty($menu['sub'])) {
                unset($menus[$key]);
            }
        }

        return array_values($menus);
    }

    /**
     * 检测菜单所属插件编码.
     */
    public static function detectPlugin(array $menu): string
    {
        if (!empty($menu['node']) && ($code = self::pluginByNode(strval($menu['node'])))) {
            return $code;
        }
        if (!empty($menu['url']) && ($code = self::pluginByUrl(strval($menu['url'])))) {
            return $code;
        }
        foreach ((array)($menu['sub'] ?? []) as $sub) {
            if ($code = self::detectPlugin($sub)) {
                return $code;
            }
        }
        return '';
    }

    /**
     * 获取系统菜单树数据.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getTree(): array
    {
        $menus = SystemMenu::mk()->where(['status' => 1])->order('sort desc,id asc')->select()->toArray();
        if (function_exists('admin_menu_filter')) {
            $menus = call_user_func('admin_menu_filter', $menus);
        }
        foreach ($menus as &$menu) {
            $menu['title'] = self::lang($menu['title']);
        }
        return self::filter(ArrayTree::arr2tree($menus));
    }

    /**
     * 解析插件编码.
     */
    private static function pluginCode(?string $plugin): string
    {
        $current = AppService::resolvePlugin(trim(strval($plugin)), true);
        return strval($current['code'] ?? '');
    }

    /**
     * 判断节点是否允许出现在插件上下文中.
     */
    private static function allowNode(string $node, string $plugin = ''): bool
    {
        return $plugin === '' || self::pluginByNode($node) === $plugin;
    }

    /**
     * 通过节点解析插件编码.
     */
    private static function pluginByNode(string $node): string
    {
        $node = trim($node, '\/');
        if ($node === '') {
            return '';
        }
        $prefix = explode('/', $node, 2)[0];
        $plugin = AppService::resolvePluginPrefix($prefix, true) ?: AppService::resolvePlugin($prefix, true);
        return strval($plugin['code'] ?? '');
    }

    /**
     * 通过菜单链接解析插件编码.
     */
    private static function pluginByUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#' || preg_match('#^(https?:)?//#i', $url)) {
            return '';
        }

        $path = trim(strval(parse_url($url, PHP_URL_PATH) ?: $url), '\/');
        $path = preg_replace('/\.[a-zA-Z0-9]+$/', '', $path);
        return self::pluginByNode($path ?: '');
    }

    /**
     * 载入菜单树.
     */
    private static function loadTree(bool $active = true): array
    {
        $query = SystemMenu::mk()->order('sort desc,id asc');
        if ($active) {
            $query->where(['status' => 1]);
        }
        return ArrayTree::arr2tree($query->select()->toArray());
    }

    /**
     * 菜单分组语言包.
     */
    private static function lang(string $name): string
    {
        $lang = lang("menus_{$name}");
        if (stripos($lang, 'menus_') === 0) {
            return lang(substr($lang, 6));
        }
        return $lang;
    }

    /**
     * 后台主菜单权限过滤.
     * @param array $menus 当前菜单列表
     */
    private static function filter(array $menus): array
    {
        foreach ($menus as $key => &$menu) {
            if (!empty($menu['sub'])) {
                $menu['sub'] = self::filter($menu['sub']);
            }
            if (!empty($menu['sub'])) {
                $menu['url'] = '#';
            } elseif (empty($menu['url']) || $menu['url'] === '#' || !(empty($menu['node']) || SystemAuthService::check($menu['node']))) {
                unset($menus[$key]);
            } elseif (preg_match('#^(https?:)?//\w+#i', $menu['url'])) {
                if ($menu['params']) {
                    $menu['url'] .= (strpos($menu['url'], '?') === false ? '?' : '&') . $menu['params'];
                }
            } else {
                $node = join('/', array_slice(str2arr($menu['url'], '/'), 0, 3));
                $menu['url'] = system_uri($menu['url']) . ($menu['params'] ? '?' . $menu['params'] : '');
                if (!SystemAuthService::check($node)) {
                    unset($menus[$key]);
                }
            }
        }
        return $menus;
    }
}
