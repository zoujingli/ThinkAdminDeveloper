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

namespace plugin\helper\service;

use think\admin\Exception;
use think\admin\Plugin;
use think\admin\service\NodeService;

/**
 * 插件菜单校验服务。
 */
final class PluginMenuService
{
    /**
     * 获取插件菜单定义。
     *
     * @param class-string $service
     * @return array<int, array<string, mixed>>
     * @throws Exception
     */
    public static function menus(string $service): array
    {
        $service = self::resolveService($service);
        return (array)$service::menu();
    }

    /**
     * 获取插件菜单根节点配置。
     *
     * @param class-string $service
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function menuRoot(string $service): array
    {
        $service = self::resolveService($service);
        return method_exists($service, 'getMenuRoot') ? (array)$service::getMenuRoot() : [];
    }

    /**
     * 获取插件菜单存在性检测条件。
     *
     * @param class-string $service
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function menuExists(string $service): array
    {
        $service = self::resolveService($service);
        return method_exists($service, 'getMenuExists') ? (array)$service::getMenuExists() : [];
    }

    /**
     * 校验插件菜单与控制器注释节点的一致性。
     *
     * @param class-string $service
     * @throws Exception
     */
    public static function assertMenus(string $service): void
    {
        $menus = self::menus($service);
        if ($menus === []) {
            return;
        }

        $nodes = NodeService::getMethods(true);
        self::assertMenuNodes($service, $menus, $nodes);
    }

    /**
     * 递归校验菜单节点。
     *
     * @param class-string $service
     * @param array<int, array<string, mixed>> $menus
     * @param array<string, array<string, mixed>> $nodes
     * @throws Exception
     */
    private static function assertMenuNodes(string $service, array $menus, array $nodes, string $parent = ''): void
    {
        foreach ($menus as $index => $menu) {
            $label = trim(strval($menu['title'] ?? ($menu['name'] ?? ($menu['node'] ?? "menu#{$index}"))));
            $path = $parent === '' ? $label : "{$parent} > {$label}";
            $node = trim(strtolower(str_replace('\\', '/', strval($menu['node'] ?? ''))), '/');
            if ($node !== '') {
                if (!isset($nodes[$node])) {
                    throw new Exception("插件菜单节点 {$node} 不存在，来源 {$service} ({$path})");
                }
                if (empty($nodes[$node]['isauth']) || empty($nodes[$node]['ismenu'])) {
                    throw new Exception("插件菜单节点 {$node} 需要同时声明 @auth true 与 @menu true，来源 {$service} ({$path})");
                }
            }
            if (!empty($menu['subs'])) {
                self::assertMenuNodes($service, (array)$menu['subs'], $nodes, $path);
            }
        }
    }

    /**
     * 确认插件服务类有效并实例化元数据。
     *
     * @param class-string $service
     * @return class-string
     * @throws Exception
     */
    private static function resolveService(string $service): string
    {
        if ($service === '' || !class_exists($service)) {
            throw new Exception("插件服务 {$service} 不存在");
        }
        if (!is_subclass_of($service, Plugin::class)) {
            throw new Exception("插件服务 {$service} 必须继承 think\\admin\\Plugin");
        }

        app($service);
        return $service;
    }
}
