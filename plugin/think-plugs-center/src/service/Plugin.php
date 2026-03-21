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

namespace plugin\center\service;

use plugin\center\Service as CenterService;
use think\admin\service\AppService;

/**
 * 插件数据服务
 * @class Plugin
 */
abstract class Plugin
{
    /**
     * 判断安装状态
     */
    public static function isInstall(string $code): bool
    {
        return !empty(AppService::get($code, true));
    }

    /**
     * 获取可进入插件列表。
     * @param bool $check 是否只返回已配置菜单且可见的插件
     */
    public static function getLocalPlugs(bool $check = false): array
    {
        $data = [];
        foreach (AppService::all(true) as $code => $packer) {
            $install = (array)($packer['install'] ?? []);
            // 插件菜单处理
            $menus = AppService::menus($packer, $check, false);
            if ($check && (empty($packer['show']) || empty($menus))) {
                continue;
            }
            // 组件应用插件
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
                'center' => sysuri('/' . CenterService::getAppCode() . '/layout', ['encode' => $encode], false),
            ];
        }
        return $data;
    }
}
