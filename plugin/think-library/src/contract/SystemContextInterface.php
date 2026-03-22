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

namespace think\admin\contract;

interface SystemContextInterface
{
    public function buildToken(): string;

    public function getTokenHeader(): string;

    public function getTokenCookie(): string;

    public function getTokenType(): string;

    public function syncTokenCookie(?string $token = null): string;

    public function check(?string $node = ''): bool;

    public function getUser(?string $field = null, $default = null);

    public function getUserId(): int;

    public function isSuper(): bool;

    public function isLogin(): bool;

    public function withUploadUnid(?string $uptoken = null): array;

    public function clearAuth(): bool;

    public function getData(string $name, $default = []);

    public function setData(string $name, $value): bool;

    public function setOplog(string $action, string $content): bool;

    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array;
}
