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

namespace plugin\system\service;

use plugin\system\model\SystemBase;
use think\admin\contract\SystemContextInterface;

class SystemContext implements SystemContextInterface
{
    public function buildToken(): string
    {
        return SystemAuthService::buildToken();
    }

    public function getTokenHeader(): string
    {
        return SystemAuthService::getTokenHeader();
    }

    public function getTokenCookie(): string
    {
        return SystemAuthService::getTokenCookie();
    }

    public function getTokenType(): string
    {
        return SystemAuthService::getTokenType();
    }

    public function syncTokenCookie(?string $token = null): string
    {
        return SystemAuthService::syncTokenCookie($token);
    }

    public function check(?string $node = ''): bool
    {
        return SystemAuthService::check($node);
    }

    public function getUser(?string $field = null, $default = null)
    {
        return SystemAuthService::getUser($field, $default);
    }

    public function getUserId(): int
    {
        return SystemAuthService::getUserId();
    }

    public function isSuper(): bool
    {
        return SystemAuthService::isSuper();
    }

    public function isLogin(): bool
    {
        return SystemAuthService::isLogin();
    }

    public function withUploadUnid(?string $uptoken = null): array
    {
        return SystemAuthService::withUploadUnid($uptoken);
    }

    public function clearAuth(): bool
    {
        return SystemAuthService::clear();
    }

    public function getData(string $name, $default = [])
    {
        return SystemService::getData($name, $default);
    }

    public function setData(string $name, $value): bool
    {
        return SystemService::setData($name, $value);
    }

    public function setOplog(string $action, string $content): bool
    {
        return SystemService::setOplog($action, $content);
    }

    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array
    {
        return SystemBase::items($type, $data, $field, $bind);
    }
}
