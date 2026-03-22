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

use think\admin\Service;
use think\admin\service\AppService;

/**
 * 系统内插件中心服务
 * @class PluginCenterService
 */
abstract class PluginCenterService extends Service
{
    public const CONFIG_KEY = 'system.plugin.center.config';

    /**
     * 获取插件中心配置
     */
    public static function getConfig(): array
    {
        $data = sysdata(static::CONFIG_KEY);
        $data = is_array($data) ? $data : [];
        return [
            'enabled' => array_key_exists('enabled', $data) ? (empty($data['enabled']) ? 0 : 1) : 1,
            'show_menu' => array_key_exists('show_menu', $data) ? (empty($data['show_menu']) ? 0 : 1) : 1,
            'default' => static::normalizeDefault(strval($data['default'] ?? '')),
        ];
    }

    /**
     * 保存插件中心配置
     */
    public static function setConfig(array $data): array
    {
        $config = array_merge(static::getConfig(), $data);
        $config['enabled'] = empty($config['enabled']) ? 0 : 1;
        $config['show_menu'] = empty($config['show_menu']) ? 0 : 1;
        $config['default'] = static::normalizeDefault(strval($config['default'] ?? ''));
        sysdata(static::CONFIG_KEY, $config);
        return $config;
    }

    /**
     * 判断插件中心是否启用
     */
    public static function isEnabled(): bool
    {
        return !empty(static::getConfig()['enabled']);
    }

    /**
     * 判断插件中心菜单是否显示
     */
    public static function isMenuVisible(): bool
    {
        $config = static::getConfig();
        return !empty($config['enabled']) && !empty($config['show_menu']);
    }

    /**
     * 获取默认插件编码
     */
    public static function getDefaultApp(): string
    {
        return strval(static::getConfig()['default'] ?? '');
    }

    /**
     * 判断插件是否可作为默认入口
     */
    public static function hasSelectableApp(string $code): bool
    {
        return array_key_exists($code, static::getSelectableApps());
    }

    /**
     * 获取默认入口可选插件
     */
    public static function getSelectableApps(): array
    {
        $items = [];
        foreach (static::getLocalPlugs(true) as $item) {
            $items[$item['code']] = sprintf('%s [%s]', $item['name'] ?: $item['code'], $item['code']);
        }

        $default = static::getDefaultApp();
        if ($default !== '' && !isset($items[$default])) {
            $items[$default] = sprintf('%s [%s]', $default, '当前配置');
        }

        asort($items, SORT_NATURAL);
        return $items;
    }

    /**
     * 获取可进入插件列表
     * @param bool $check 是否仅返回具备可见菜单的插件
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
            $data[$packer['package']] = [
                'type' => 'plugin',
                'code' => $code,
                'name' => $packer['name'] ?: ($install['name'] ?? ''),
                'cover' => $install['cover'] ?? '',
                'amount' => $install['amount'] ?? '0.00',
                'remark' => $install['remark'] ?? ($packer['description'] ?: ($install['description'] ?? '')),
                'version' => $packer['version'] ?: ($install['version'] ?? ''),
                'package' => $packer['package'],
                'service' => $packer['service'],
                'license' => empty($packer['license']) ? 'unknow' : $packer['license'][0],
                'licenses' => '',
                'platforms' => $packer['platforms'] ?? [],
                'plugmenus' => $menus,
                'encode' => $encode,
                'center' => sysuri('system/plugin/layout', ['encode' => $encode], false),
            ];
        }
        return $data;
    }

    /**
     * 归一化默认值
     */
    private static function normalizeDefault(string $default): string
    {
        $default = trim($default);
        return $default === '0' ? '' : $default;
    }
}
