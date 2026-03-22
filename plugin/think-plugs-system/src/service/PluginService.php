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
}
