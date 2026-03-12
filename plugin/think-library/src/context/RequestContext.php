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

namespace think\admin\context;

/**
 * 请求级运行时上下文。
 * 这里只保存“当前插件”和“当前认证态”这类强请求语义的数据，
 * 统一在 HttpRun/HttpEnd 时清理，避免继续散落在 sysvar() 字符串键里。
 * @class RequestContext
 */
final class RequestContext
{
    /**
     * 当前请求上下文实例。
     */
    private static ?self $instance = null;

    /**
     * 当前插件编码。
     */
    private string $pluginCode = '';

    /**
     * 当前插件前缀。
     */
    private string $pluginPrefix = '';

    /**
     * 当前后台用户。
     * @var array<string, mixed>
     */
    private array $currentUser = [];

    /**
     * 当前后台令牌。
     */
    private string $currentToken = '';

    /**
     * 当前请求是否已经完成认证判定。
     */
    private bool $authReady = false;

    /**
     * 获取请求上下文实例。
     */
    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    /**
     * 清理当前请求上下文。
     */
    public static function clear(): void
    {
        self::$instance = new self();
    }

    /**
     * 设置当前插件信息。
     */
    public function setPlugin(string $code = '', string $prefix = ''): self
    {
        $this->pluginCode = trim($code);
        $this->pluginPrefix = trim($prefix, '\/');
        return $this;
    }

    /**
     * 清理当前插件信息。
     */
    public function clearPlugin(): self
    {
        return $this->setPlugin();
    }

    /**
     * 获取当前插件编码。
     */
    public function pluginCode(): string
    {
        return $this->pluginCode;
    }

    /**
     * 获取当前插件前缀。
     */
    public function pluginPrefix(): string
    {
        return $this->pluginPrefix;
    }

    /**
     * 更新当前认证信息。
     * @param array<string, mixed> $user
     */
    public function setAuth(array $user = [], string $token = '', bool $ready = true): self
    {
        $this->currentUser = $user;
        $this->currentToken = $token;
        $this->authReady = $ready;
        return $this;
    }

    /**
     * 仅更新当前令牌。
     */
    public function setToken(string $token): self
    {
        $this->currentToken = $token;
        return $this;
    }

    /**
     * 清理当前认证信息。
     */
    public function clearAuth(bool $ready = false): self
    {
        return $this->setAuth([], '', $ready);
    }

    /**
     * 获取当前后台用户。
     * @return array<string, mixed>
     */
    public function user(): array
    {
        return $this->currentUser;
    }

    /**
     * 获取当前后台令牌。
     */
    public function token(): string
    {
        return $this->currentToken;
    }

    /**
     * 当前请求是否已完成认证判定。
     */
    public function authReady(): bool
    {
        return $this->authReady;
    }
}
