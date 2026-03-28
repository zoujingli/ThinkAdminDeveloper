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

use think\admin\Exception;
use think\admin\Service;
use think\admin\service\AppService;

/**
 * 系统内插件中心服务
 * @class PluginCenterService
 */
abstract class PluginService extends Service
{
    public const CONFIG_KEY = 'system.plugin_center';

    /**
     * 获取插件中心配置.
     *
     * @return array{enabled:int,show_menu:int}
     */
    public static function getConfig(): array
    {
        $data = sysdata(static::CONFIG_KEY);
        $data = is_array($data) ? $data : [];
        return [
            'enabled' => array_key_exists('enabled', $data) ? (empty($data['enabled']) ? 0 : 1) : 1,
            'show_menu' => array_key_exists('show_menu', $data) ? (empty($data['show_menu']) ? 0 : 1) : 1,
        ];
    }

    /**
     * 构建插件中心首页上下文。
     * @return array<string, mixed>
     */
    public static function buildIndexContext(): array
    {
        $items = array_values(self::getLocalPlugs(true));
        $allItems = array_values(self::getLocalPlugs(false));
        $config = self::getConfig();

        return [
            'items' => $items,
            'summary' => self::buildIndexSummary($allItems, $items),
            'overview' => self::buildOverviewRows($config, $allItems, $items),
            'config' => $config,
        ];
    }

    /**
     * 构建插件工作台布局上下文。
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function buildLayoutContext(string $encode): array
    {
        $code = self::decodeCenterCode($encode);
        $plugin = AppService::resolvePlugin($code, true);
        if (empty($plugin)) {
            throw new Exception(lang('插件未安装或未启用。'));
        }
        AppService::activatePlugin($plugin);

        $rawMenus = AppService::menus($plugin, false, true);
        if ($rawMenus === []) {
            throw new Exception(lang('插件未配置菜单，无法进入工作台。'));
        }

        $menus = self::normalizeWorkbenchMenus(AppService::menus($plugin, true, true));
        if ($menus === []) {
            throw new Exception(lang('当前账号没有可用菜单，请联系管理员授权后再试。'));
        }

        $title = strval($plugin['name'] ?? $code);
        $shellMenus = [[
            'id' => 9999998,
            'url' => '#',
            'sub' => $menus,
            'node' => 'system/plugin/layout',
            'title' => $title,
        ]];
        if (self::isMenuVisible()) {
            $shellMenus[] = [
                'id' => 9999999,
                'url' => system_uri('system/plugin/index'),
                'node' => 'system/plugin/index',
                'title' => strval(lang('返回插件中心')),
            ];
        }

        return [
            'plugin' => $plugin,
            'menus' => $shellMenus,
            'title' => $title,
            'pageTitle' => $title,
        ];
    }

    /**
     * 构建插件工作台错误页上下文。
     * @param array<string, mixed> $plugin
     * @return array<string, mixed>
     */
    public static function buildLayoutErrorContext(string $content, array $plugin = []): array
    {
        $title = strval($plugin['name'] ?? lang('插件中心'));

        return [
            'returnUrl' => system_uri('system/plugin/index'),
            'menus' => [[
                'id' => 9999999,
                'url' => system_uri('system/plugin/index'),
                'node' => 'system/plugin/index',
                'title' => strval(lang('返回插件中心')),
            ]],
            'title' => strval(lang('%s - 打开失败', [$title])),
            'pageTitle' => strval(lang('%s - 打开失败', [$title])),
            'content' => $content,
        ];
    }

    /**
     * 保存插件中心配置.
     *
     * @return array{enabled:int,show_menu:int}
     */
    public static function setConfig(array $data): array
    {
        $config = [
            'enabled' => empty($data['enabled']) ? 0 : 1,
            'show_menu' => empty($data['show_menu']) ? 0 : 1,
        ];
        sysdata(static::CONFIG_KEY, $config);
        return $config;
    }

    /**
     * 判断插件中心是否启用.
     */
    public static function isEnabled(): bool
    {
        return !empty(static::getConfig()['enabled']);
    }

    /**
     * 判断插件中心菜单是否显示.
     */
    public static function isMenuVisible(): bool
    {
        $config = static::getConfig();
        return !empty($config['enabled']) && !empty($config['show_menu']);
    }

    /**
     * 获取可进入的插件列表.
     *
     * @param bool $check 是否仅返回具备可见菜单的插件
     * @return array<string, array<string, mixed>>
     */
    public static function getLocalPlugs(bool $check = false): array
    {
        $data = [];
        foreach (AppService::all(true) as $code => $packer) {
            if ($code === '') {
                continue;
            }
            $install = (array)($packer['install'] ?? []);
            $menus = AppService::menus($packer, $check, false);
            if ($check && (empty($packer['show']) || empty($menus))) {
                continue;
            }

            $encode = encode($code);
            $package = strval($packer['package'] ?? '');
            $key = $package !== '' ? $package : $code;
            $item = [
                'type' => 'plugin',
                'code' => $code,
                'name' => $packer['name'] ?: ($install['name'] ?? ''),
                'cover' => $install['cover'] ?? '',
                'amount' => $install['amount'] ?? '0.00',
                'remark' => $install['remark'] ?? (strval($packer['description'] ?? '') !== '' ? strval($packer['description']) : strval($install['description'] ?? '')),
                'version' => strval($packer['version'] ?? '') !== '' ? strval($packer['version']) : strval($install['version'] ?? ''),
                'package' => $packer['package'],
                'service' => $packer['service'],
                'license' => empty($packer['license']) ? 'unknow' : $packer['license'][0],
                'licenses' => '',
                'platforms' => $packer['platforms'] ?? [],
                'plugmenus' => $menus,
                'encode' => $encode,
                'center' => sysuri('system/plugin/layout', ['encode' => $encode], false),
            ];
            $data[$key] = self::normalizeCenterItem($item, $packer);
        }
        uasort($data, static function (array $left, array $right): int {
            return [$right['is_available'] ? 1 : 0, $left['menu_count'], $left['name'], $left['code']]
                <=> [$left['is_available'] ? 1 : 0, $right['menu_count'], $right['name'], $right['code']];
        });
        return $data;
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $packer
     * @return array<string, mixed>
     */
    private static function normalizeCenterItem(array $item, array $packer): array
    {
        $menus = is_array($item['plugmenus'] ?? null) ? $item['plugmenus'] : [];
        $platforms = array_values(array_filter(array_map('strval', is_array($item['platforms'] ?? null) ? $item['platforms'] : [])));
        $licenses = array_values(array_filter(array_map('strval', is_array($item['license'] ?? null) ? $item['license'] : [])));
        $isVisible = !empty($packer['show']);
        $isAvailable = $isVisible && count($menus) > 0;
        $kind = strval($packer['type'] ?? 'plugin');

        $item['menu_count'] = count($menus);
        $item['kind_label'] = $kind === 'local' ? '本地应用' : '扩展插件';
        $item['is_visible'] = $isVisible;
        $item['is_available'] = $isAvailable;
        $item['has_cover'] = trim(strval($item['cover'] ?? '')) !== '';
        $item['platform_text'] = $platforms === [] ? '通用后台' : join(' / ', $platforms);
        $item['license_text'] = $licenses === [] ? '未声明' : strtoupper($licenses[0]);
        $item['remark_text'] = trim(strval($item['remark'] ?? '')) !== '' ? trim(strval($item['remark'])) : '暂无插件说明，当前页面仅展示插件入口与管理能力。';
        if ($isAvailable) {
            $item['status_label'] = '可进入工作台';
            $item['status_hint'] = '当前插件入口与系统菜单保持同一套注释式 RBAC 权限模型。';
            $item['action_text'] = '进入插件';
        } elseif ($isVisible) {
            $item['status_label'] = '待配置菜单';
            $item['status_hint'] = '当前应用尚未暴露可见菜单，暂时不能进入工作台。';
            $item['action_text'] = '未配置菜单';
        } else {
            $item['status_label'] = '已隐藏入口';
            $item['status_hint'] = '当前应用已从插件中心入口隐藏，可在系统参数中重新开放。';
            $item['action_text'] = '入口已隐藏';
        }

        return $item;
    }

    /**
     * @param array<int, array<string, mixed>> $allItems
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, string>>
     */
    private static function buildIndexSummary(array $allItems, array $items): array
    {
        $local = 0;
        $plugin = 0;
        $hidden = 0;
        foreach ($allItems as $item) {
            if (strval($item['kind_label'] ?? '') === '本地应用') {
                $local++;
            } else {
                $plugin++;
            }
            if (empty($item['is_visible'])) {
                $hidden++;
            }
        }

        return [
            ['label' => '应用总数', 'value' => strval(count($allItems))],
            ['label' => '本地应用', 'value' => strval($local)],
            ['label' => '扩展插件', 'value' => strval($plugin)],
            ['label' => '可进入工作台', 'value' => strval(count($items))],
            ['label' => '已隐藏入口', 'value' => strval($hidden)],
            ['label' => '菜单入口总数', 'value' => strval(array_sum(array_map(static fn(array $item): int => intval($item['menu_count'] ?? 0), $allItems)))],
        ];
    }

    /**
     * @param array<string, int> $config
     * @param array<int, array<string, mixed>> $allItems
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, string>>
     */
    private static function buildOverviewRows(array $config, array $allItems, array $items): array
    {
        return [
            ['label' => '插件中心状态', 'value' => !empty($config['enabled']) ? '已启用' : '已禁用'],
            ['label' => '菜单展示策略', 'value' => !empty($config['show_menu']) ? '显示插件中心菜单入口' : '隐藏插件中心菜单入口'],
            ['label' => '工作台模式', 'value' => '统一使用插件布局页承接菜单与页面跳转'],
            ['label' => '鉴权模型', 'value' => '沿用注释式 RBAC 节点与系统权限树'],
            ['label' => '脚本维护策略', 'value' => '每插件单主安装脚本，统一使用版本_install_插件编码日期'],
            ['label' => '当前可进入', 'value' => sprintf('%d / %d', count($items), count($allItems))],
        ];
    }

    /**
     * @throws Exception
     */
    private static function decodeCenterCode(string $encode): string
    {
        $code = trim(strval(decode($encode)));
        if ($code === '') {
            throw new Exception(lang('插件编码不能为空。'));
        }

        return $code;
    }

    /**
     * @param array<int, array<string, mixed>> $menus
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeWorkbenchMenus(array $menus): array
    {
        foreach ($menus as $k1 => &$one) {
            $one['id'] = $k1 + 1;
            if (!empty($one['subs'])) {
                foreach ($one['subs'] as $k2 => &$two) {
                    $two['id'] = $k2 + 1;
                    $two['pid'] = $one['id'];
                }
                $one['sub'] = $one['subs'];
                unset($one['subs']);
            }
        }
        unset($one, $two);

        return array_values($menus);
    }
}
