<?php

// +----------------------------------------------------------------------
// | Center Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 开源协议 ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-center
// | github 代码仓库：https://github.com/zoujingli/think-plugs-center
// +----------------------------------------------------------------------

declare (strict_types=1);

if (!function_exists('plguri')) {
    /**
     * 生成插件后台 URL 地址
     * @param string $url 路由地址
     * @param array $vars PATH 变量
     * @param boolean|string $suffix 后缀
     * @param boolean|string $domain 域名
     * @return string
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
