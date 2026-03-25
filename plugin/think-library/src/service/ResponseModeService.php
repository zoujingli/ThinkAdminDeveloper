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

namespace think\admin\service;

use think\admin\runtime\RequestTokenService;
use think\Request;

/**
 * 控制器表现层模式服务.
 * @class ResponseModeService
 */
final class ResponseModeService
{
    public const MODE_VIEW = 'view';

    public const MODE_API = 'api';

    public const MODE_MIXED = 'mixed';

    /**
     * 解析当前响应模式.
     */
    public static function resolve(?Request $request = null, string $controllerClass = ''): string
    {
        if (self::isApiController($controllerClass)) {
            return self::MODE_API;
        }

        $mode = self::configuredMode();
        if ($mode !== self::MODE_MIXED) {
            return $mode;
        }

        $request ??= request();
        $accept = strtolower(strval($request->header('accept', '')));
        if (str_contains($accept, 'application/json')) {
            return self::MODE_API;
        }

        $output = strtolower(trim(strval($request->param('output', ''))));
        if (in_array($output, ['json', 'layui.table'], true)) {
            return self::MODE_API;
        }

        return self::MODE_VIEW;
    }

    /**
     * 当前请求是否走 API 模式.
     */
    public static function prefersApi(?Request $request = null, string $controllerClass = ''): bool
    {
        return self::resolve($request, $controllerClass) === self::MODE_API;
    }

    /**
     * 获取 API Token 请求头名称.
     */
    public static function apiHeader(): string
    {
        $header = trim(strval(config('app.presentation.api_header', 'Authorization')));
        return $header !== '' ? $header : 'Authorization';
    }

    /**
     * 获取配置声明的模式.
     */
    public static function configuredMode(): string
    {
        return self::normalizeMode(strval(config('app.presentation.mode', self::MODE_MIXED)));
    }

    /**
     * 获取当前请求头 Token.
     */
    public static function headerToken(?Request $request = null): string
    {
        $request ??= request();
        return RequestTokenService::parseHeaderToken(strval($request->header(self::apiHeader(), '')));
    }

    /**
     * 规范化模式值.
     */
    private static function normalizeMode(string $mode): string
    {
        $mode = strtolower(trim($mode));
        return in_array($mode, [self::MODE_VIEW, self::MODE_API], true) ? $mode : self::MODE_MIXED;
    }

    /**
     * 是否为显式 API 控制器.
     */
    private static function isApiController(string $controllerClass): bool
    {
        return $controllerClass !== '' && str_contains(str_replace('/', '\\', $controllerClass), '\\controller\\api\\');
    }
}
