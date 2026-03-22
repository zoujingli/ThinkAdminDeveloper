<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace think\admin\runtime;

use think\admin\contract\SystemContextInterface;
use think\admin\Exception;

class NullSystemContext implements SystemContextInterface
{
    private const TOKEN_HEADER = 'Authorization';

    private const TOKEN_COOKIE = 'system_access_token';

    private const TOKEN_TYPE = 'system-auth';

    public function buildToken(): string
    {
        return '';
    }

    public function getTokenHeader(): string
    {
        return self::TOKEN_HEADER;
    }

    public function getTokenCookie(): string
    {
        return self::TOKEN_COOKIE;
    }

    public function getTokenType(): string
    {
        return self::TOKEN_TYPE;
    }

    public function syncTokenCookie(?string $token = null): string
    {
        return '';
    }

    public function check(?string $node = ''): bool
    {
        return false;
    }

    public function getUser(?string $field = null, $default = null)
    {
        return is_null($field) ? [] : $default;
    }

    public function getUserId(): int
    {
        return 0;
    }

    public function isSuper(): bool
    {
        return false;
    }

    public function isLogin(): bool
    {
        return false;
    }

    public function withUploadUnid(?string $uptoken = null): array
    {
        return [0, []];
    }

    public function clearAuth(): bool
    {
        return true;
    }

    public function getData(string $name, $default = [])
    {
        throw new Exception('System context is not initialized, data service is unavailable.');
    }

    public function setData(string $name, $value): bool
    {
        throw new Exception('System context is not initialized, data service is unavailable.');
    }

    public function setOplog(string $action, string $content): bool
    {
        throw new Exception('System context is not initialized, oplog service is unavailable.');
    }

    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array
    {
        return [];
    }
}
