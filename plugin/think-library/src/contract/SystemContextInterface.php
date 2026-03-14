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

namespace think\admin\contract;

/**
 * 系统上下文能力契约。
 * 由 System 插件提供真实实现，library 只依赖该接口。
 * @class SystemContextInterface
 */
interface SystemContextInterface
{
    /**
     * 获取后台认证令牌.
     */
    public function buildToken(): string;

    /**
     * 获取后台认证请求头名称.
     */
    public function getTokenHeader(): string;

    /**
     * 获取后台认证 Cookie 名称.
     */
    public function getTokenCookie(): string;

    /**
     * 获取后台 JWT 类型。
     */
    public function getTokenType(): string;

    /**
     * 同步后台认证 Cookie。
     */
    public function syncTokenCookie(?string $token = null): string;

    /**
     * 校验节点权限.
     */
    public function check(?string $node = ''): bool;

    /**
     * 获取后台用户数据.
     * @param null|mixed $default
     * @return mixed
     */
    public function getUser(?string $field = null, $default = null);

    /**
     * 获取后台用户编号.
     */
    public function getUserId(): int;

    /**
     * 是否为超管账号.
     */
    public function isSuper(): bool;

    /**
     * 是否已登录.
     */
    public function isLogin(): bool;

    /**
     * 获取上传令牌上下文.
     * @return array [unid,exts]
     */
    public function withUploadUnid(?string $uptoken = null): array;

    /**
     * 清理后台节点缓存.
     */
    public function clearAuth(): bool;

    /**
     * 读取系统参数.
     * @return mixed
     */
    public function getConfig(string $name = '', string $default = '');

    /**
     * 写入系统参数.
     * @param mixed $value
     * @return mixed
     */
    public function setConfig(string $name, $value = '');

    /**
     * 读取系统数据.
     * @param mixed $default
     * @return mixed
     */
    public function getData(string $name, $default = []);

    /**
     * 写入系统数据.
     * @param mixed $value
     */
    public function setData(string $name, $value): bool;

    /**
     * 写入系统日志.
     */
    public function setOplog(string $action, string $content): bool;

    /**
     * 获取数据字典项.
     */
    public function baseItems(string $type, array &$data = [], string $field = 'base_code', string $bind = 'base_info'): array;
}
