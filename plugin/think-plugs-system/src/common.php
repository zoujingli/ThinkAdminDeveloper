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
use think\admin\Exception;
use think\admin\runtime\SystemContext;

if (!function_exists('auth')) {
    /**
     * 访问权限检查.
     */
    function auth(?string $node): bool
    {
        return SystemContext::instance()->check($node);
    }
}

if (!function_exists('system_user')) {
    /**
     * 获取当前后台用户数据.
     * @param null|string $field 指定字段
     * @param mixed $default 默认值
     * @return array|mixed
     */
    function system_user(?string $field = null, $default = null)
    {
        return SystemContext::instance()->getUser($field, $default);
    }
}

if (!function_exists('system_uri')) {
    /**
     * 生成后台 URL 地址
     * @param string $url 路由地址
     * @param array $vars PATH 变量
     * @param bool|string $suffix 后缀
     * @param bool|string $domain 域名
     */
    function system_uri(string $url = '', array $vars = [], $suffix = true, $domain = false): string
    {
        return sysuri('system/index/index', [], $suffix, $domain) . '#' . url($url, $vars)->build();
    }
}

if (!function_exists('sysconf')) {
    /**
     * 获取或配置系统参数.
     * @param string $name 参数名称
     * @param mixed $value 参数内容
     * @return mixed
     * @throws Exception
     */
    function sysconf(string $name = '', $value = null)
    {
        $context = SystemContext::instance();
        if (is_null($value) && is_string($name)) {
            return $context->getConfig($name);
        }
        return $context->setConfig($name, $value);
    }
}

if (!function_exists('sysdata')) {
    /**
     * JSON 数据读取与存储.
     * @param string $name 数据名称
     * @param mixed $value 数据内容
     * @return mixed
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

if (!function_exists('sysoplog')) {
    /**
     * 写入系统日志.
     * @param string $action 日志行为
     * @param string $content 日志内容
     */
    function sysoplog(string $action, string $content): bool
    {
        return SystemContext::instance()->setOplog($action, $content);
    }
}
