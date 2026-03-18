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

namespace plugin\center\service;

use think\admin\service\PluginService;

/**
 * 插件数据服务
 * @class Plugin
 */
abstract class Plugin
{
    public const TYPE_MODULE = 'module';

    public const TYPE_PLUGIN = 'plugin';

    public const TYPE_SERVICE = 'service';

    public const TYPE_LIBRARY = 'library';

    public const types = [
        self::TYPE_MODULE => '系统应用',
        self::TYPE_PLUGIN => '功能插件',
        self::TYPE_SERVICE => '基础服务',
        self::TYPE_LIBRARY => '开发组件',
    ];

    /**
     * 判断安装状态
     */
    public static function isInstall(string $code): bool
    {
        return !empty(AppService::get($code, true));
    }

    /**
     * 获取本地插件.
     * @param ?string $type 插件类型
     * @param bool $check 检查权限
     */
    public static function getLocalPlugs(?string $type = null, bool $check = false): array
    {
        $data = [];
        foreach (AppService::all(true) as $code => $packer) {
            $install = (array)($packer['install'] ?? []);
            $ptype = strval($packer['type'] ?? ($install['type'] ?? ''));
            if (is_string($type) && $ptype !== $type) {
                continue;
            }
            // 插件菜单处理
            $menus = AppService::menus($packer, $check, false);
            if ($check) {
                if (empty($menus)) {
                    continue;
                }
            }
            // 组件应用插件
            $encode = encode($code);
            $data[$packer['package']] = [
                'type' => $ptype,
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
                'center' => sysuri("layout/{$encode}", [], false),
            ];
        }
        return $data;
    }
}
