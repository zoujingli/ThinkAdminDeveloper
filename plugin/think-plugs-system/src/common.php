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
use plugin\system\service\PluginCenterService;
use think\admin\Exception;
use think\admin\Library;
use think\admin\route\Url;
use think\admin\runtime\RequestContext;
use think\admin\runtime\SystemContext;

if (!function_exists('auth')) {
    function auth(?string $node): bool
    {
        return SystemContext::instance()->check($node);
    }
}

if (!function_exists('system_user')) {
    function system_user(?string $field = null, $default = null)
    {
        return SystemContext::instance()->getUser($field, $default);
    }
}

if (!function_exists('system_uri')) {
    function system_uri(string $url = '', array $vars = [], $suffix = true, $domain = false): string
    {
        $target = Url::normalizeWebTarget($url);
        return sysuri('system/index/index', [], $suffix, $domain)
            . '#'
            . Library::$sapp->route->buildUrl($target, $vars)->suffix($suffix)->domain($domain)->build();
    }
}

if (!function_exists('plguri')) {
    function plguri(string $url = '', array $vars = [], $suffix = true, $domain = false): string
    {
        $encode = encode(RequestContext::instance()->pluginCode());
        $target = Url::normalizeWebTarget($url);
        return sysuri('/system/plugin/layout', ['encode' => $encode], false)
            . '#'
            . Library::$sapp->route->buildUrl($target, $vars)->suffix($suffix)->domain($domain)->build();
    }
}

if (!function_exists('sysdata')) {
    /**
     * @param null|mixed $value
     * @throws Exception
     */
    function sysdata(string $name, $value = null)
    {
        $context = SystemContext::instance();
        if (is_null($value)) {
            return $context->getData($name);
        }
        return $context->setData($name, $value);
    }
}

if (!function_exists('sysget')) {
    /**
     * 显式读取系统数据，避免 sysdata(name, value) 的读写语义混用。
     *
     * @param null|mixed $default
     * @throws Exception
     */
    function sysget(string $name, $default = null)
    {
        return SystemContext::instance()->getData($name, $default);
    }
}

if (!function_exists('sysoplog')) {
    function sysoplog(string $action, string $content): bool
    {
        return SystemContext::instance()->setOplog($action, $content);
    }
}

if (!function_exists('admin_menu_filter')) {
    function admin_menu_filter(array $menus): array
    {
        if (PluginCenterService::isMenuVisible()) {
            return $menus;
        }

        return array_values(array_filter($menus, static function (array $menu): bool {
            return strval($menu['node'] ?? '') !== 'system/plugin/index'
                && strval($menu['url'] ?? '') !== 'system/plugin/index';
        }));
    }
}
