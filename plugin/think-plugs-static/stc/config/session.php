<?php

// +----------------------------------------------------------------------
// | Static Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-static
// | github 代码仓库：https://github.com/zoujingli/think-plugs-static
// +----------------------------------------------------------------------

return [
    // 字段名称
    'name'   => env('SESSION_NAME', 'ssid'),
    // 驱动方式
    'type'   => env('SESSION_TYPE', 'file'),
    // 存储连接
    'store'  => env('SESSION_STORE', ''),
    // 过期时间
    'expire' => env('SESSION_EXPIRE', 7200),
    // 文件前缀
    'prefix' => env('SESSION_PREFIX', ''),
];