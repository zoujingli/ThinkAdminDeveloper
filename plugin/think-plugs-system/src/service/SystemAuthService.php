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

use plugin\system\model\SystemAuth;
use plugin\system\model\SystemNode;
use plugin\system\model\SystemUser;
use think\admin\Exception;
use think\admin\extend\ArrayTree;
use think\admin\extend\CodeToolkit;
use think\admin\Library;
use think\admin\runtime\RequestContext;
use think\admin\runtime\RequestTokenService;
use think\admin\Service;
use think\admin\service\CacheSession;
use think\admin\service\JwtToken;
use think\admin\service\NodeService;
use think\helper\Str;
use think\Request;

/**
 * 系统权限管理服务
 * @class SystemAuthService
 */
class SystemAuthService extends Service
{
    /**
     * 后台认证请求头.
     */
    private const TOKEN_HEADER = 'Authorization';

    /**
     * 后台认证令牌类型.
     */
    private const TOKEN_SCHEME = 'Bearer';

    /**
     * 后台认证 Cookie 名称.
     */
    private const TOKEN_COOKIE = 'system_access_token';

    /**
     * JWT 用户类型.
     */
    private const TOKEN_TYPE = 'system-auth';

    /**
     * JWT 上传类型.
     */
    private const TOKEN_UPLOAD = 'system-upload';

    /**
     * 表单上传令牌有效期.
     */
    private const DEFAULT_UPLOAD_EXPIRE = 1800;

    /**
     * 后台令牌失效时间记录键前缀.
     */
    private const TOKEN_INVALIDATE_PREFIX = 'SystemTokenInvalidateAt_';

    /**
     * 自定义回调处理.
     * @var array
     */
    private static $checkCallables = [];

    /**
     * 是否已经登录.
     */
    public static function isLogin(): bool
    {
        return static::getUserId() > 0;
    }

    /**
     * 是否为超级用户.
     */
    public static function isSuper(): bool
    {
        return static::getUserName() === static::getSuperName();
    }

    /**
     * 获取超级用户账号.
     */
    public static function getSuperName(): string
    {
        return Library::$sapp->config->get('app.super_user', 'admin');
    }

    /**
     * 获取后台用户ID.
     */
    public static function getUserId(): int
    {
        return intval(static::getUser('id', 0));
    }

    /**
     * 获取后台用户名称.
     */
    public static function getUserName(): string
    {
        return strval(static::getUser('username', ''));
    }

    /**
     * 获取当前用户数据.
     * @param null|string $field 指定字段
     * @param null|mixed $default 默认值
     * @return array|mixed
     */
    public static function getUser(?string $field = null, $default = null)
    {
        $user = self::currentUser();
        return is_null($field) ? $user : ($user[$field] ?? $default);
    }

    /**
     * 设置当前登录用户并刷新权限.
     */
    public static function login(array $user): array
    {
        RequestContext::instance()->clearAuth();
        return self::bindUser(self::normalizeUser($user), true);
    }

    /**
     * 清理当前请求登录态.
     */
    public static function forget(): void
    {
        RequestContext::instance()->clearAuth(true);
    }

    /**
     * 主动注销当前后台令牌。
     */
    public static function logout(): void
    {
        if (($sid = self::resolveLogoutSessionId()) !== '') {
            self::destroySession($sid);
        } elseif (($uid = self::resolveLogoutUserId()) > 0) {
            SystemService::setData(self::TOKEN_INVALIDATE_PREFIX . $uid, time());
        }
        static::forget();
    }

    /**
     * 获取后台认证令牌.
     */
    public static function buildToken(?array $user = null): string
    {
        $user = $user ?: self::currentUser();
        if (empty($user['id']) || empty($user['password'])) {
            return '';
        }
        $sessionId = self::bindSession();
        $payload = [
            'typ' => self::TOKEN_TYPE,
            'uid' => intval($user['id']),
            'pwd' => self::passwordDigest(strval($user['password'])),
            'sid' => $sessionId,
            'jti' => CodeToolkit::uuid(),
        ];
        if (($expire = static::getTokenExpire()) > 0) {
            $payload['exp'] = time() + $expire;
        }
        RequestContext::instance()->setSessionId($sessionId);
        return JwtToken::token($payload);
    }

    /**
     * 解析当前认证令牌.
     * @param null|string $token 指定令牌
     * @param bool $force 强制刷新
     */
    public static function resolve(?string $token = null, bool $force = false): array
    {
        $context = RequestContext::instance();
        if (!$force && !empty($user = $context->user())) {
            return $user;
        }

        $token = $token ?: static::requestToken();
        if ($token === '') {
            static::forget();
            return [];
        }

        $data = JwtToken::verify($token);
        if (($data['typ'] ?? '') !== self::TOKEN_TYPE || empty($data['uid'])) {
            throw new Exception('登录状态已失效，请重新登录！');
        }
        self::verifySession($data);

        $user = SystemUser::mk()->where(['id' => intval($data['uid'])])->findOrEmpty()->toArray();

        if (empty($user)) {
            throw new Exception('用户不存在或已被删除，请重新登录！');
        }
        if (empty($user['status'])) {
            throw new Exception('账号已经被禁用，请联系管理员！');
        }
        if (($invalidAt = self::getTokenInvalidAt(intval($user['id']))) > 0 && intval($data['iat'] ?? 0) <= $invalidAt) {
            throw new Exception('登录状态已失效，请重新登录！');
        }
        if (self::passwordDigest(strval($user['password'])) !== strval($data['pwd'] ?? '')) {
            throw new Exception('登录状态已失效，请重新登录！');
        }

        $context->setToken($token);
        $context->setSessionId(strval($data['sid'] ?? ''));
        return self::bindUser($user, $force);
    }

    /**
     * 获取请求中的认证令牌.
     */
    public static function requestToken(?Request $request = null): string
    {
        $token = RequestTokenService::systemToken($request);
        self::upgradeLegacyCookieToken($request);
        return $token;
    }

    /**
     * 获取认证头名称.
     */
    public static function getTokenHeader(): string
    {
        return self::TOKEN_HEADER;
    }

    /**
     * 获取认证头令牌类型.
     */
    public static function getTokenScheme(): string
    {
        return self::TOKEN_SCHEME;
    }

    /**
     * 获取后台 JWT 类型。
     */
    public static function getTokenType(): string
    {
        return self::TOKEN_TYPE;
    }

    /**
     * 获取认证 Cookie 名称.
     */
    public static function getTokenCookie(): string
    {
        $cookie = trim(strval(Library::$sapp->config->get('app.system_token_cookie', self::TOKEN_COOKIE)));
        return $cookie !== '' ? $cookie : self::TOKEN_COOKIE;
    }

    /**
     * 从请求头中解析认证令牌。
     */
    public static function requestHeaderToken(?Request $request = null, ?string $header = null): string
    {
        $request = $request ?: Library::$sapp->request;
        $header = $header ?: static::getTokenHeader();
        return RequestTokenService::parseHeaderToken(strval($request->header($header, '')));
    }

    /**
     * 从 Cookie 中解析认证令牌。
     */
    public static function requestCookieToken(?Request $request = null, ?string $cookie = null): string
    {
        $request = $request ?: Library::$sapp->request;
        $cookie = trim(strval($cookie ?: static::getTokenCookie()));
        if ($cookie === '') {
            return '';
        }

        return RequestTokenService::decodeCookieToken(strval($request->cookie($cookie, '')));
    }

    /**
     * 构建标准认证头内容.
     */
    public static function buildTokenHeader(?string $token = null): string
    {
        $token = RequestTokenService::normalizeToken((string)$token);
        return $token === '' ? '' : self::TOKEN_SCHEME . ' ' . $token;
    }

    /**
     * 同步后台认证 Cookie。
     * 使用 path=>'/' 保证同站任意路径均携带；值经 encodeCookieToken 加密后写入。
     */
    public static function syncTokenCookie(?string $token = null): string
    {
        $token = RequestTokenService::normalizeToken($token ?? static::buildToken());
        if ($token === '') {
            static::forgetTokenCookie();
            return '';
        }

        $expire = static::getTokenExpire();
        cookie(static::getTokenCookie(), RequestTokenService::encodeCookieToken($token), [
            'expire' => $expire,
            'path' => '/',
        ]);
        return $token;
    }

    /**
     * 清理后台认证 Cookie。
     */
    public static function forgetTokenCookie(): void
    {
        cookie(static::getTokenCookie(), null, ['path' => '/']);
    }

    /**
     * 获取当前会话编号。
     */
    public static function currentSessionId(): string
    {
        return RequestContext::instance()->sessionId();
    }

    /**
     * 获取认证令牌有效期.
     */
    public static function getTokenExpire(): int
    {
        return max(0, intval(Library::$sapp->config->get('app.system_token_expire') ?: 604800));
    }

    /**
     * 获取上传令牌有效期.
     */
    public static function getUploadTokenExpire(): int
    {
        return max(60, intval(Library::$sapp->config->get('app.system_upload_token_expire') ?: self::DEFAULT_UPLOAD_EXPIRE));
    }

    /**
     * 获取用户扩展数据.
     * @param null|mixed $default
     * @return array|mixed
     */
    public static function getUserData(?string $field = null, $default = null)
    {
        $data = SystemService::getData('UserData_' . static::getUserId());
        return is_null($field) ? $data : ($data[$field] ?? $default);
    }

    /**
     * 设置用户扩展数据.
     * @throws Exception
     */
    public static function setUserData(array $data, bool $replace = false): bool
    {
        $data = $replace ? $data : array_merge(static::getUserData(), $data);
        return SystemService::setData('UserData_' . static::getUserId(), $data);
    }

    /**
     * 获取用户主题名称.
     * @throws Exception
     */
    public static function getUserTheme(): string
    {
        $default = sysconf('base.site_theme|raw') ?: 'default';
        return static::getUserData('site_theme', $default);
    }

    /**
     * 设置用户主题名称.
     * @param string $theme 主题名称
     * @throws Exception
     */
    public static function setUserTheme(string $theme): bool
    {
        return static::setUserData(['site_theme' => $theme]);
    }

    /**
     * 注册权限检查函数.
     */
    public static function registerCheckCallable(callable $callable): int
    {
        self::$checkCallables[] = $callable;
        return count(self::$checkCallables) - 1;
    }

    /**
     * 移除权限检查函数.
     */
    public static function removeCheckCallable(?int $index): bool
    {
        if (is_null($index)) {
            self::$checkCallables = [];
            return true;
        }
        if (isset(self::$checkCallables[$index])) {
            unset(self::$checkCallables[$index]);
            return true;
        }
        return false;
    }

    /**
     * 检查指定节点授权
     * --- 需要读取缓存或扫描所有节点.
     */
    public static function check(?string $node = ''): bool
    {
        $skey1 = 'think.admin.methods';
        $current = NodeService::fullNode($node);
        $methods = sysvar($skey1) ?: sysvar($skey1, NodeService::getMethods());
        $userNodes = static::getUser('nodes', []);
        if (count(self::$checkCallables) > 0) {
            foreach (self::$checkCallables as $callable) {
                if ($callable($current, $methods, $userNodes) === false) {
                    return false;
                }
            }
            return true;
        }
        if (function_exists('admin_check_filter')) {
            return call_user_func('admin_check_filter', $current, $methods, $userNodes);
        }
        if (static::isSuper()) {
            return true;
        }
        if (empty($simples = sysvar($skey2 = 'think.admin.fulls') ?: [])) {
            foreach ($methods as $k => $v) {
                $simples[strtr($k, ['_' => ''])] = $v;
            }
            sysvar($skey2, $simples);
        }
        if (empty($simples[$simple = strtr($current, ['_' => ''])]['isauth'])) {
            return !(!empty($simples[$simple]['islogin']) && !static::isLogin());
        }
        return in_array($current, $userNodes);
    }

    /**
     * 获取授权节点列表.
     */
    public static function getTree(array $checkeds = []): array
    {
        [$nodes, $pnodes, $methods] = [[], [], array_reverse(NodeService::getMethods())];
        foreach ($methods as $node => $method) {
            [$count, $pnode] = [substr_count($node, '/'), substr($node, 0, strripos($node, '/'))];
            if ($count === 2 && !empty($method['isauth'])) {
                in_array($pnode, $pnodes) or array_push($pnodes, $pnode);
                $nodes[$node] = ['node' => $node, 'title' => lang($method['title']), 'pnode' => $pnode, 'checked' => in_array($node, $checkeds)];
            } elseif ($count === 1 && in_array($pnode, $pnodes)) {
                $nodes[$node] = ['node' => $node, 'title' => lang($method['title']), 'pnode' => $pnode, 'checked' => in_array($node, $checkeds)];
            }
        }
        foreach (array_keys($nodes) as $key) {
            foreach ($methods as $node => $method) {
                if (stripos($key, $node . '/') !== false) {
                    $pnode = substr($node, 0, strripos($node, '/'));
                    $nodes[$node] = ['node' => $node, 'title' => lang($method['title']), 'pnode' => $pnode, 'checked' => in_array($node, $checkeds)];
                    $nodes[$pnode] = ['node' => $pnode, 'title' => Str::studly($pnode), 'pnode' => '', 'checked' => in_array($pnode, $checkeds)];
                }
            }
        }
        return ArrayTree::arr2tree(array_reverse($nodes), 'node', 'pnode', '_sub_');
    }

    /**
     * 初始化用户权限.
     * @param bool $force 强刷权限
     */
    public static function apply(bool $force = false): array
    {
        $user = self::currentUser();
        if (empty($user['id'])) {
            return [];
        }
        if (!$force && isset($user['nodes'])) {
            return $user;
        }
        return static::resolve(RequestContext::instance()->token() ?: static::requestToken(), true);
    }

    /**
     * 清理节点缓存.
     */
    public static function clear(): bool
    {
        Library::$sapp->cache->delete('SystemAuthNode');
        return true;
    }

    /**
     * 获取会员上传配置.
     * @return array [unid,exts]
     */
    public static function withUploadUnid(?string $uptoken = null): array
    {
        try {
            $uptoken = is_null($uptoken) ? strval(input('uptoken', '')) : $uptoken;
            if ($uptoken === '') {
                return [0, []];
            }
            $data = JwtToken::verify($uptoken);
            if (($data['typ'] ?? '') !== self::TOKEN_UPLOAD) {
                return [0, []];
            }
            return [intval($data['unid'] ?? 0), array_values(array_unique(array_map('strval', $data['exts'] ?? [])))];
        } catch (\Throwable $exception) {
            return [0, []];
        }
    }

    /**
     * 生成上传入口令牌.
     * @param int $unid 会员编号
     * @param string $exts 允许后缀(多个以英文逗号隔开)
     * @throws Exception
     */
    public static function withUploadToken(int $unid, string $exts = ''): string
    {
        return JwtToken::token([
            'typ' => self::TOKEN_UPLOAD,
            'unid' => $unid,
            'exts' => str2arr(strtolower($exts)),
            'exp' => time() + static::getUploadTokenExpire(),
        ]);
    }

    private static function passwordDigest(string $passwordHash): string
    {
        return hash('sha256', $passwordHash);
    }

    /**
     * 获取当前用户.
     */
    private static function currentUser(): array
    {
        $context = RequestContext::instance();
        if ($context->authReady()) {
            return $context->user();
        }
        return static::resolve();
    }

    /**
     * 标准化用户数据.
     */
    private static function normalizeUser(array $user): array
    {
        if (isset($user['id'])) {
            $user['id'] = intval($user['id']);
        }
        return $user;
    }

    /**
     * 绑定用户节点数据.
     */
    private static function bindUser(array $user, bool $force = false): array
    {
        $user = self::normalizeUser($user);
        if (!isset($user['nodes']) || $force) {
            $user['nodes'] = [];
            if (!empty($user['id']) && $user['username'] !== static::getSuperName() && count($aids = str2arr(strval($user['authorize'] ?? ''))) > 0) {
                $aids = SystemAuth::mk()->where(['status' => 1])->whereIn('id', $aids)->column('id');
                if (!empty($aids)) {
                    $user['nodes'] = SystemNode::mk()->distinct()->whereIn('auth', $aids)->column('node');
                }
            }
        }
        $context = RequestContext::instance();
        $context->setAuth($user, $context->token(), true);
        return $user;
    }

    /**
     * 绑定当前后台缓存会话。
     */
    private static function bindSession(?string $sessionId = null): string
    {
        $sessionId = trim(strval($sessionId ?: RequestContext::instance()->sessionId()));
        if ($sessionId === '') {
            $sessionId = CodeToolkit::uuid();
        }

        $scope = "sid:{$sessionId}";
        if (CacheSession::exists($scope)) {
            CacheSession::touch(static::getTokenExpire(), $scope);
        } else {
            CacheSession::put([], static::getTokenExpire(), $scope);
        }
        return $sessionId;
    }

    /**
     * 校验当前后台缓存会话。
     */
    private static function verifySession(array $data): void
    {
        $sessionId = trim(strval($data['sid'] ?? ''));
        if ($sessionId === '') {
            return;
        }

        $scope = "sid:{$sessionId}";
        if (!CacheSession::exists($scope)) {
            throw new Exception('登录状态已失效，请重新登录！');
        }
        CacheSession::touch(static::getTokenExpire(), $scope);
    }

    /**
     * 删除指定后台缓存会话。
     */
    private static function destroySession(string $sessionId): void
    {
        $sessionId = trim($sessionId);
        if ($sessionId === '') {
            return;
        }

        CacheSession::destroy("sid:{$sessionId}");
        RequestContext::instance()->setSessionId('');
    }

    /**
     * 获取当前注销目标用户.
     */
    private static function resolveLogoutUserId(): int
    {
        if (($uid = static::getUserId()) > 0) {
            return $uid;
        }

        $token = static::requestToken();
        if ($token === '') {
            return 0;
        }

        try {
            $data = JwtToken::verify($token);
            return (($data['typ'] ?? '') === self::TOKEN_TYPE && !empty($data['uid'])) ? intval($data['uid']) : 0;
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * 获取当前注销目标会话。
     */
    private static function resolveLogoutSessionId(): string
    {
        if (($sid = RequestContext::instance()->sessionId()) !== '') {
            return $sid;
        }

        $token = static::requestToken();
        if ($token === '') {
            return '';
        }

        try {
            $data = JwtToken::verify($token);
            return (($data['typ'] ?? '') === self::TOKEN_TYPE) ? trim(strval($data['sid'] ?? '')) : '';
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 获取用户令牌最近失效时间.
     */
    private static function upgradeLegacyCookieToken(?Request $request = null): void
    {
        $request = $request ?: Library::$sapp->request;
        $rawToken = strval($request->cookie(static::getTokenCookie(), ''));
        $decodedToken = RequestTokenService::capture($request)->systemCookieToken();
        if (RequestTokenService::shouldUpgradeCookieToken($rawToken, $decodedToken)) {
            self::syncTokenCookie($decodedToken);
        }
    }

    private static function getTokenInvalidAt(int $uid): int
    {
        if ($uid < 1) {
            return 0;
        }
        return intval(SystemService::getData(self::TOKEN_INVALIDATE_PREFIX . $uid, 0));
    }
}
