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

use plugin\account\service\Account;
use think\admin\extend\CodeToolkit;
use think\admin\Library;
use think\admin\service\JwtToken;
use think\Request;

/**
 * 请求级认证令牌解析服务。
 * 统一处理 Authorization Bearer 与认证 Cookie，
 * 并把结果记录到 RequestContext，避免重复解析与相互覆盖。
 * @class RequestTokenService
 */
class RequestTokenService
{
    /**
     * 加密 Cookie 令牌前缀。
     */
    private const COOKIE_TOKEN_PREFIX = 'enc:';

    /**
     * 账号默认认证 Cookie 名称。
     */
    private const ACCOUNT_TOKEN_COOKIE = 'account_access_token';

    /**
     * 获取当前请求系统鉴权令牌。
     */
    public static function systemToken(?Request $request = null): string
    {
        return static::capture($request)->systemRequestToken();
    }

    /**
     * 初始化并返回当前请求令牌上下文。
     */
    public static function capture(?Request $request = null): RequestContext
    {
        $context = RequestContext::instance();
        if ($context->requestTokensReady()) {
            return $context;
        }

        $request = $request ?: Library::$sapp->request;
        $authorization = static::parseHeaderToken(strval($request->header(SystemContext::getTokenHeader(), '')));
        $systemCookie = static::decodeCookieToken(strval($request->cookie(SystemContext::getTokenCookie(), '')));
        $accountCookie = static::decodeCookieToken(strval($request->cookie(static::getAccountTokenCookie(), '')));
        $system = '';
        $account = '';

        if ($authorization !== '') {
            if (static::isSystemToken($authorization)) {
                $system = $authorization;
            } elseif (static::isAccountToken($authorization)) {
                $account = $authorization;
            }
        } else {
            $system = $systemCookie;
            $account = $accountCookie;
        }

        return $context->setRequestTokens($authorization, $system, $account, $systemCookie, $accountCookie);
    }

    /**
     * 解析标准认证头。
     */
    public static function parseHeaderToken(string $authorization): string
    {
        $authorization = trim($authorization);
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            return '';
        }

        return static::normalizeToken($matches[1]);
    }

    public static function normalizeToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $token, $matches)) {
            $token = trim($matches[1]);
        }

        return preg_replace('/\s+/', '', $token) ?: '';
    }

    /**
     * 解码 Cookie 中的认证令牌。
     * 新版本优先解析加密前缀，旧明文 Cookie 继续兼容。
     */
    public static function decodeCookieToken(string $token): string
    {
        $token = trim($token);
        if ($token === '') {
            return '';
        }

        if (str_contains($token, '%')) {
            $token = rawurldecode($token);
        }

        if (stripos($token, self::COOKIE_TOKEN_PREFIX) === 0) {
            try {
                return static::normalizeToken(strval(CodeToolkit::decrypt(substr($token, strlen(self::COOKIE_TOKEN_PREFIX)), static::cookieTokenSecret())));
            } catch (\Throwable) {
                return '';
            }
        }

        return static::normalizeToken($token);
    }

    /**
     * 获取账号认证 Cookie 名称。
     */
    public static function getAccountTokenCookie(): string
    {
        $cookie = trim(strval(config('app.account_token_cookie') ?: self::ACCOUNT_TOKEN_COOKIE));
        return $cookie !== '' ? $cookie : self::ACCOUNT_TOKEN_COOKIE;
    }

    /**
     * 获取当前请求账号鉴权令牌。
     */
    public static function accountToken(?Request $request = null): string
    {
        return static::capture($request)->accountRequestToken();
    }

    /**
     * 清理当前请求中的系统鉴权令牌。
     */
    public static function forgetSystem(?Request $request = null): void
    {
        $request = $request ?: Library::$sapp->request;
        $context = static::capture($request);
        $authorization = $context->authorizationToken();
        $account = $context->accountRequestToken();
        $systemCookie = $context->systemCookieToken();
        $accountCookie = $context->accountCookieToken();
        $system = $context->systemRequestToken();

        if ($system !== '' && $system === $authorization) {
            $headers = $request->header();
            unset($headers['authorization'], $headers['Authorization']);
            $request->withHeader($headers);

            $server = $_SERVER;
            unset($server['HTTP_AUTHORIZATION'], $server['REDIRECT_HTTP_AUTHORIZATION']);
            $request->withServer($server);
            $authorization = '';
        }

        if ($system !== '' && $system === $systemCookie) {
            $cookies = $request->cookie();
            unset($cookies[SystemContext::getTokenCookie()]);
            $request->withCookie($cookies);
            $systemCookie = '';
        }

        $context->setRequestTokens($authorization, '', $account, $systemCookie, $accountCookie);
    }

    /**
     * 获取当前请求 Authorization Bearer 令牌。
     */
    public static function authorizationToken(?Request $request = null): string
    {
        return static::capture($request)->authorizationToken();
    }

    /**
     * 编码要写入 Cookie 的认证令牌。
     * Header 仍保持标准 Bearer 原文，只有 Cookie 分支做对称加密。
     */
    public static function encodeCookieToken(string $token): string
    {
        $token = static::normalizeToken($token);
        if ($token === '') {
            return '';
        }
        if (!static::cookieTokenEncryptionEnabled()) {
            return $token;
        }

        return self::COOKIE_TOKEN_PREFIX . CodeToolkit::encrypt($token, static::cookieTokenSecret());
    }

    public static function shouldUpgradeCookieToken(string $rawToken, ?string $decodedToken = null): bool
    {
        $rawToken = trim($rawToken);
        if ($rawToken === '' || !static::cookieTokenEncryptionEnabled() || static::isEncryptedCookieToken($rawToken)) {
            return false;
        }

        $decodedToken = static::normalizeToken(strval($decodedToken));
        return $decodedToken !== '' && static::normalizeToken($rawToken) === $decodedToken;
    }

    /**
     * 标准化令牌内容。
     */
    public static function isEncryptedCookieToken(string $token): bool
    {
        return stripos(trim($token), self::COOKIE_TOKEN_PREFIX) === 0;
    }

    /**
     * 获取 Cookie 令牌加密密钥。
     */
    private static function cookieTokenSecret(): string
    {
        $secret = trim(strval(config('app.token_cookie_secret', '')));
        return $secret !== '' ? $secret : JwtToken::jwtkey();
    }

    /**
     * 判断是否为系统 JWT。
     */
    private static function isSystemToken(string $token): bool
    {
        try {
            return strval(JwtToken::verify($token)['typ'] ?? '') === SystemContext::getTokenType();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 判断是否为账号 JWT。
     */
    private static function isAccountToken(string $token): bool
    {
        try {
            return strval(JwtToken::verify($token)['typ'] ?? '') === Account::getTokenType();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * 是否启用 Cookie 令牌加密。
     */
    private static function cookieTokenEncryptionEnabled(): bool
    {
        return boolval(config('app.token_cookie_encrypt', true));
    }
}
