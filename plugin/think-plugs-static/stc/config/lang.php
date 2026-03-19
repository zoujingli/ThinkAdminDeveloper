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
    // 默认语言
    'default_lang' => 'zh-cn',
    // 允许的语言列表
    'allow_lang_list' => ['zh-cn'],
    // 转义为对应语言包名称
    'accept_language' => [
        'en' => 'en-us',
        'zh-hans-cn' => 'zh-cn',
    ],
    // 多语言自动侦测变量名
    'detect_var' => 'lang',
    // 多语言 Cookie 变量（禁用 Cookie 持久化）
    'cookie_var' => '__lang_disabled__',
    // 多语言 Header 变量
    'header_var' => 'lang',
    // 使用 Cookie 记录
    'use_cookie' => false,
    // 是否支持语言分组
    'allow_group' => false,
    // 扩展语言包
    'extend_list' => [],
];
