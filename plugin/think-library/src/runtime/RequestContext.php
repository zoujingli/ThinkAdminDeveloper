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
     * 当前认证会话编号。
     */
    private string $currentSessionId = '';

    /**
     * 当前请求是否已经完成认证判定。
     */
    private bool $authReady = false;

    /**
     * 当前请求令牌是否已初始化。
     */
    private bool $requestTokensReady = false;

    /**
     * 当前请求 Authorization Bearer 令牌。
     */
    private string $authorizationToken = '';

    /**
     * 当前请求系统鉴权令牌。
     */
    private string $systemRequestToken = '';

    /**
     * 当前请求账号鉴权令牌。
     */
    private string $accountRequestToken = '';

    /**
     * 当前请求系统认证 Cookie 令牌。
     */
    private string $systemCookieToken = '';

    /**
     * 当前请求账号认证 Cookie 令牌。
     */
    private string $accountCookieToken = '';

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
     * 设置当前认证会话编号。
     */
    public function setSessionId(string $sessionId = ''): self
    {
        $this->currentSessionId = trim($sessionId);
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
        $this->setSessionId('');
        return $this->setAuth([], '', $ready);
    }

    /**
     * 更新当前请求令牌解析结果。
     */
    public function setRequestTokens(
        string $authorization = '',
        string $system = '',
        string $account = '',
        string $systemCookie = '',
        string $accountCookie = ''
    ): self {
        $this->requestTokensReady = true;
        $this->authorizationToken = trim($authorization);
        $this->systemRequestToken = trim($system);
        $this->accountRequestToken = trim($account);
        $this->systemCookieToken = trim($systemCookie);
        $this->accountCookieToken = trim($accountCookie);
        return $this;
    }

    /**
     * 清理当前请求令牌解析结果。
     */
    public function clearRequestTokens(): self
    {
        $this->requestTokensReady = false;
        $this->authorizationToken = '';
        $this->systemRequestToken = '';
        $this->accountRequestToken = '';
        $this->systemCookieToken = '';
        $this->accountCookieToken = '';
        return $this;
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
     * 获取当前认证会话编号。
     */
    public function sessionId(): string
    {
        return $this->currentSessionId;
    }

    /**
     * 当前请求是否已完成认证判定。
     */
    public function authReady(): bool
    {
        return $this->authReady;
    }

    /**
     * 当前请求令牌是否已初始化。
     */
    public function requestTokensReady(): bool
    {
        return $this->requestTokensReady;
    }

    /**
     * 获取当前请求 Authorization Bearer 令牌。
     */
    public function authorizationToken(): string
    {
        return $this->authorizationToken;
    }

    /**
     * 获取当前请求系统鉴权令牌。
     */
    public function systemRequestToken(): string
    {
        return $this->systemRequestToken;
    }

    /**
     * 获取当前请求账号鉴权令牌。
     */
    public function accountRequestToken(): string
    {
        return $this->accountRequestToken;
    }

    /**
     * 获取当前请求系统认证 Cookie 令牌。
     */
    public function systemCookieToken(): string
    {
        return $this->systemCookieToken;
    }

    /**
     * 获取当前请求账号认证 Cookie 令牌。
     */
    public function accountCookieToken(): string
    {
        return $this->accountCookieToken;
    }
}
