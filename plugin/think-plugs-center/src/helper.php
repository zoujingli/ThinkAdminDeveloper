<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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
if (!function_exists('plguri')) {
    /**
     * 生成插件后台 URL 地址
     * @param string $url 路由地址
     * @param array $vars PATH 变量
     * @param bool|string $suffix 后缀
     * @param bool|string $domain 域名
     */
    function plguri(string $url = '', array $vars = [], $suffix = true, $domain = false): string
    {
        $encode = encode(sysvar('CurrentPluginCode'));
        return sysuri("layout/{$encode}", [], false) . '#' . url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('random_bgc')) {
    function random_bgc(?int $idx = null): string
    {
        $colors = ['red', 'blue', 'orig', 'green', 'violet', 'purple', 'brown'];
        $color = is_null($idx) ? $colors[array_rand($colors)] : $colors[$idx % count($colors)];
        return "think-bg-{$color}";
    }
}
