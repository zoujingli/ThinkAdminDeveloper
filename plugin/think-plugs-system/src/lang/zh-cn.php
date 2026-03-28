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

$loader = require __DIR__ . '/_loader.php';

$menus = [
    'menus_系统管理' => '系统管理',
    'menus_系统配置' => '系统配置',
    'menus_系统参数配置' => '系统参数配置',
    'menus_系统任务管理' => '系统任务管理',
    'menus_系统日志管理' => '系统日志管理',
    'menus_数据字典管理' => '数据字典管理',
    'menus_系统文件管理' => '系统文件管理',
    'menus_系统菜单管理' => '系统菜单管理',
    'menus_权限管理' => '权限管理',
    'menus_访问权限管理' => '访问权限管理',
    'menus_系统用户管理' => '系统用户管理',
    'menus_微信管理' => '微信管理',
    'menus_微信接口配置' => '微信接口配置',
    'menus_微信支付配置' => '微信支付配置',
    'menus_微信粉丝管理' => '微信粉丝管理',
    'menus_微信定制' => '微信定制',
    'menus_微信图文管理' => '微信图文管理',
    'menus_微信菜单配置' => '微信菜单配置',
    'menus_回复规则管理' => '回复规则管理',
    'menus_关注自动回复' => '关注自动回复',
    'menus_微信支付' => '微信支付',
    'menus_支付行为管理' => '支付行为管理',
    'menus_支付退款管理' => '支付退款管理',
    'menus_插件中心' => '插件中心',
];

return $loader('zh-cn', '简体中文', '简体菜单', $menus);
