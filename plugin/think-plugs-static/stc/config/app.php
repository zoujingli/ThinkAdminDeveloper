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
    // 应用命名空间
    'app_namespace' => '',
    // 本地单应用（未命中插件前缀时使用）
    'single_app' => 'index',
    // 插件机制配置
    'plugin' => [
        // 插件编码 => 前缀 或 前缀数组，配置后覆盖插件默认前缀
        'bindings' => [],
        // 动态插件切换默认关闭，仅在显式开启时作为调试或兼容入口
        'switch' => [
            'enabled' => false,
            'query' => '_plugin',
            'header' => 'X-Plugin-App',
        ],
    ],
    // 是否启用路由
    'with_route' => true,
    // 超级用户账号
    'super_user' => 'admin',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',
    // 后台 JWT 有效期（秒，0 表示不过期）
    'admin_token_expire' => 604800,
    // 上传令牌有效期（秒）
    'admin_upload_token_expire' => 1800,
    // CORS 启用状态（默认开启跨域）
    'cors_on' => true,
    // CORS 配置跨域域名（仅需填域名，留空则自动域名）
    'cors_host' => [],
    // CORS 授权请求方法
    'cors_methods' => 'GET,PUT,POST,PATCH,DELETE',
    // CORS 是否允许携带 Cookie 等凭证
    'cors_credentials' => false,
    // CORS 跨域头部字段
    'cors_headers' => 'Api-Type,Api-Name,Api-Uuid,Api-Token,User-Form-Token',
    // X-Frame-Options 配置
    'cors_frame' => 'sameorigin',
    // RBAC 登录页面（填写登录地址）
    'rbac_login' => '',
    // RBAC 忽略应用（填写应用名称）
    'rbac_ignore' => ['index'],
    // 显示错误消息内容，仅生产模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 异常状态模板配置，仅生产模式有效
    'http_exception_template' => [
        404 => syspath('public/static/theme/err/404.html'),
        500 => syspath('public/static/theme/err/500.html'),
    ],
];
