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
        return AuthService::buildToken();
    }

    public function getTokenHeader(): string
    {
        return AuthService::getTokenHeader();
    }

    public function getTokenCookie(): string
    {
        return AuthService::getTokenCookie();
    }

    public function getTokenType(): string
    {
        return AuthService::getTokenType();
    }

    public function syncTokenCookie(?string $token = null): string
    {
        return AuthService::syncTokenCookie($token);
    }

    public function check(?string $node = ''): bool
    {
        return AuthService::check($node);
    }

    public function getUser(?string $field = null, $default = null)
    {
        return AuthService::getUser($field, $default);
    }

    public function getUserId(): int
    {
        return AuthService::getUserId();
    }

    public function isSuper(): bool
    {
        return AuthService::isSuper();
    }

    public function isLogin(): bool
    {
        return AuthService::isLogin();
    }

    public function withUploadUnid(?string $uptoken = null): array
    {
        return AuthService::withUploadUnid($uptoken);
    }

    public function clearAuth(): bool
    {
        return AuthService::clear();
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
