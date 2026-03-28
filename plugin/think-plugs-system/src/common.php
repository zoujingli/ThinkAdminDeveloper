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
use plugin\system\service\PluginService;
use plugin\system\service\ConfigService;
use think\admin\Library;
use think\admin\route\Url;
use think\admin\runtime\RequestContext;
use think\admin\runtime\SystemContext;
use think\admin\service\AppService;

if (!function_exists('auth')) {
    /**
     * 权限检测.
     * @param ?string $node 权限节点
     */
    function auth(?string $node): bool
    {
        return SystemContext::instance()->check($node);
    }
}

if (!function_exists('system_user')) {
    /**
     * 系统用户信息.
     * @param ?string $field 用户字段
     * @param mixed $default 默认值
     */
    function system_user(?string $field = null, mixed $default = null): mixed
    {
        return SystemContext::instance()->getUser($field, $default);
    }
}

if (!function_exists('system_uri')) {
    /**
     * 系统URL生成.
     * @param string $url 路由地址
     * @param array $vars URL参数
     * @param bool|string $suffix 是否添加URL后缀
     * @param bool|string $domain 是否添加域名
     */
    function system_uri(string $url = '', array $vars = [], bool|string $suffix = true, bool|string $domain = false): string
    {
        $target = Url::normalizeWebTarget($url);
        $prefix = sysuri('system/index/index', [], $suffix, $domain);
        $suffix = Library::$sapp->route->buildUrl($target, $vars)->suffix($suffix)->domain($domain)->build();
        return $prefix . '#' . $suffix;
    }
}


if (!function_exists('plguri')) {
    /**
     * 插件URL生成.
     * @param string $url 路由地址
     * @param array $vars URL参数
     * @param bool|string $suffix 是否添加URL后缀
     * @param bool|string $domain 是否添加域名
     */
    function plguri(string $url = '', array $vars = [], bool|string $suffix = true, bool|string $domain = false): string
    {
        $target = Url::normalizeWebTarget($url);
        $encode = encode(RequestContext::instance()->pluginCode());
        $prefix = sysuri('/system/plugin/layout', ['encode' => $encode], false);
        $suffix = Library::$sapp->route->buildUrl($target, $vars)->suffix($suffix)->domain($domain)->build();
        return $prefix . '#' . $suffix;
    }
}

if (!function_exists('sysdata')) {
    /**
     * 系统数据读写.
     * @param string $name 数据名称
     * @param null|mixed $value 数据值
     * @return null|mixed
     */
    function sysdata(string $name, mixed $value = null): mixed
    {
        $context = SystemContext::instance();
        if (is_null($value)) {
            return $context->getData($name);
        }
        return $context->setData($name, $value);
    }
}

if (!function_exists('sysconf')) {
    /**
     * 读取系统配置项（默认从 system.site 读取）。
     * @param string $name 配置名称
     * @param mixed $default 默认值
     */
    function sysconf(string $name, mixed $default = null): mixed
    {
        $name = trim($name);
        if ($name === '') {
            return $default;
        }

        $key = str_contains($name, '.') ? $name : ('system.site.' . $name);
        return SystemContext::instance()->getData($key, $default);
    }
}

if (!function_exists('sysget')) {
    /**
     * 显式读取系统数据，避免 sysdata(name, value) 的读写语义混用。
     */
    function sysget(string $name, mixed $default = null): mixed
    {
        return SystemContext::instance()->getData($name, $default);
    }
}

if (!function_exists('sysoplog')) {
    /**
     * 系统操作日志.
     * @param string $action 日志标题
     * @param string $content 日志内容
     */
    function sysoplog(string $action, string $content): bool
    {
        return SystemContext::instance()->setOplog($action, $content);
    }
}

if (!function_exists('admin_menu_filter')) {
    /**
     * 管理员菜单过滤.
     */
    function admin_menu_filter(array $menus): array
    {
        if (PluginService::isMenuVisible()) {
            return $menus;
        }

        return array_values(array_filter($menus, static function (array $menu): bool {
            return strval($menu['node'] ?? '') !== 'system/plugin/index'
                && strval($menu['url'] ?? '') !== 'system/plugin/index';
        }));
    }
}

if (!function_exists('system_view_context')) {
    /**
     * 系统模板通用上下文。
     * 为传统 fetch 视图与壳模板提供统一品牌、登录与入口变量。
     *
     * @return array<string, mixed>
     */
    function system_view_context(array $overrides = []): array
    {
        $system = SystemContext::instance();
        try {
            $site = ConfigService::getSiteConfig();
        } catch (\Throwable) {
            $site = [];
        }

        try {
            $userId = $system->getUserId();
            $username = trim(strval($system->getUser('username', '')));
            $nickname = trim(strval($system->getUser('nickname', '')));
            $headimg = strval($system->getUser('headimg', ''));
            $isLogin = $system->isLogin();
            $isSuper = $system->isSuper();
            $theme = strval($system->getUser('site_theme', strval($site['theme'] ?? 'default')));
            $token = $system->buildToken();
        } catch (\Throwable) {
            $userId = 0;
            $username = '';
            $nickname = '';
            $headimg = '';
            $isLogin = false;
            $isSuper = false;
            $theme = strval($site['theme'] ?? 'default');
            $token = '';
        }

        $displayName = $nickname !== '' ? $nickname : $username;

        $context = [
            'pageTitle' => '',
            'staticRoot' => AppService::uri('static'),
            'websiteName' => strval($site['website_name'] ?? 'ThinkAdmin'),
            'applicationName' => strval($site['application_name'] ?? 'ThinkAdmin'),
            'applicationVersion' => strval($site['application_version'] ?? ''),
            'browserIcon' => strval($site['browser_icon'] ?? ''),
            'homeUrl' => function_exists('sysuri') ? sysuri('@') : '',
            'loginUrl' => function_exists('sysuri') ? sysuri('system/login/index') : '',
            'hasUser' => $isLogin && $username !== '',
            'currentUserId' => $userId,
            'currentUserName' => $username,
            'currentUserDisplayName' => $displayName,
            'currentUserHeadimg' => $headimg,
            'profileUrl' => $userId > 0 && function_exists('sysuri') ? sysuri('system/index/info', ['id' => $userId]) : '',
            'passwordUrl' => $userId > 0 && function_exists('sysuri') ? sysuri('system/index/pass', ['id' => $userId]) : '',
            'themeUrl' => function_exists('sysuri') ? sysuri('system/index/theme') : '',
            'logoutUrl' => function_exists('sysuri') ? sysuri('system/login/out') : '',
            'super' => $isSuper,
            'theme' => $theme !== '' ? $theme : 'default',
            'tokenValueJson' => json_encode($token, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        return array_merge($context, $overrides);
    }
}
