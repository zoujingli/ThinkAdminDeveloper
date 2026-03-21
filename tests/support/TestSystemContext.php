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

namespace think\admin\tests\Support;

use think\admin\contract\SystemContextInterface;

class TestSystemContext implements SystemContextInterface
{
    private array $config = [];

    private array $data = [];

    private array $user = [];

    private int $userId = 0;

    private bool $super = false;

    private bool $login = false;

    private array $nodes = [];

    public function buildToken(): string
    {
        return '';
    }

    public function getTokenHeader(): string
    {
        return 'Authorization';
    }

    public function getTokenCookie(): string
    {
        return 'system_access_token';
    }

    public function getTokenType(): string
    {
        return 'system-auth';
    }

    public function syncTokenCookie(?string $token = null): string
    {
        return strval($token);
    }

    public function check(?string $node = ''): bool
    {
        if ($this->super) {
            return true;
        }
        return $node !== null && in_array($node, $this->nodes, true);
    }

    public function getUser(?string $field = null, $default = null)
    {
        if ($field === null) {
            return $this->user;
        }

        return $this->user[$field] ?? $default;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function isSuper(): bool
    {
        return $this->super;
    }

    public function isLogin(): bool
    {
        return $this->login;
    }

    public function withUploadUnid(?string $uptoken = null): array
    {
        return [0, []];
    }

    public function clearAuth(): bool
    {
        return true;
    }

    public function getConfig(string $name = '', string $default = '')
    {
        return $this->config[$name] ?? $default;
    }

    public function setConfig(string $name, $value = '')
    {
        $this->config[$name] = $value;
        return $value;
    }

    public function getData(string $name, $default = [])
    {
        return $this->data[$name] ?? $default;
    }

    public function setData(string $name, $value): bool
    {
        $this->data[$name] = $value;
        return true;
    }

    public function setOplog(string $action, string $content): bool
    {
        return true;
    }

    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array
    {
        return [];
    }

    public function setUser(array $user = [], bool $login = false, bool $super = false): self
    {
        $this->user = $user;
        $this->userId = intval($user['id'] ?? 0);
        $this->login = $login;
        $this->super = $super;
        return $this;
    }

    public function setNodes(array $nodes = []): self
    {
        $this->nodes = array_values(array_unique(array_filter(array_map('strval', $nodes))));
        return $this;
    }
}
