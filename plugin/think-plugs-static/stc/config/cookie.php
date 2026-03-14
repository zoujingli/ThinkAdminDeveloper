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
return [
    // cookie 保存时间
    'expire' => 0,
    // cookie 保存路径
    'path' => '/',
    // cookie 有效域名
    'domain' => '',
    // httponly 访问设置
    'httponly' => true,
    // 是否允许服务端写回 Cookie（默认关闭，开启后可回写认证与语言 Cookie）
    'setcookie' => false,
    // cookie 安全传输，只支持 https 协议
    'secure' => request()->isSsl(),
    // samesite 安全设置，支持 'strict' 'lax' 'none'
    'samesite' => request()->isSsl() ? 'none' : 'lax',
];
