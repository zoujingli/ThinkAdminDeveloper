<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\runtime;

use think\admin\contract\SystemContextInterface;
use think\admin\Exception;

/**
 * 默认系统上下文实现。
 * 当 System 插件未注入实现时提供空行为与显式异常。
 * @class NullSystemContext
 */
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

    public function getConfig(string $name = '', string $default = '')
    {
        throw new Exception('System 上下文未初始化，系统配置服务不可用。');
    }

    public function setConfig(string $name, $value = '')
    {
        throw new Exception('System 上下文未初始化，系统配置服务不可用。');
    }

    public function getData(string $name, $default = [])
    {
        throw new Exception('System 上下文未初始化，系统数据服务不可用。');
    }

    public function setData(string $name, $value): bool
    {
        throw new Exception('System 上下文未初始化，系统数据服务不可用。');
    }

    public function setOplog(string $action, string $content): bool
    {
        throw new Exception('System 上下文未初始化，系统日志服务不可用。');
    }

    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array
    {
        return [];
    }
}
