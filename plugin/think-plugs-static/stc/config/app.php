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
return [
    // 应用命名空间
    'app_namespace' => '',
    // 默认本地应用兼容回退（建议优先使用 route.default_app）
    'single_app' => 'index',
    // 插件机制配置
    'plugin' => [
        // 插件编码 => 前缀 或 前缀数组，配置后覆盖插件默认前缀
        'bindings' => [],
        // 插件 API 统一入口前缀，例如 /api/{plugin}/...
        'api_prefix' => 'api',
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
    // 表现层模式：view 仅渲染页面，api 仅返回 JSON，mixed 根据控制器命名空间、Token 请求头与 Accept 自动切换
    'presentation' => [
        'mode' => 'mixed',
        'api_header' => 'Authorization',
    ],
    // 后台 JWT 有效期（秒，0 表示不过期）
    'system_token_expire' => 604800,
    // 后台 JWT 认证 Cookie 名称（Authorization 优先，其次读取此 Cookie）
    'system_token_cookie' => 'system_access_token',
    // 上传令牌有效期（秒）
    'system_upload_token_expire' => 1800,
    // Token 会话默认有效期（秒，0 表示不过期）
    'token_session_expire' => 7200,
    // Token 会话读取时是否自动续期
    'token_session_touch' => true,
    // Token 会话惰性清理间隔（秒）
    'token_session_gc_interval' => 300,
    // Token 会话指定缓存仓库，留空使用默认仓库
    'token_session_store' => '',
    // 认证 Cookie 中的 Token 是否加密存储（Header 中仍使用原始 Bearer Token）
    'token_cookie_encrypt' => true,
    // 认证 Cookie Token 加密密钥，留空默认复用 jwtkey
    'token_cookie_secret' => '',
    // 终端账号 JWT 认证 Cookie 名称（Authorization 优先，其次读取此 Cookie）
    'account_token_cookie' => 'account_access_token',
    // CORS 启用状态（默认开启跨域）
    'cors_on' => true,
    // CORS 配置跨域域名（仅需填域名，留空则自动域名）
    'cors_host' => [],
    // CORS 授权请求方法
    'cors_methods' => 'GET,PUT,POST,PATCH,DELETE',
    // CORS 是否允许携带 Cookie 等凭证
    'cors_credentials' => false,
    // CORS 跨域头部字段
    'cors_headers' => 'X-Device-Code,X-Device-Type',
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
