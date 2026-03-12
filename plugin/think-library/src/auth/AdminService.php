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

namespace think\admin\auth;

use think\admin\Exception;
use think\admin\context\RequestContext;
use think\admin\extend\auth\JwtToken;
use think\admin\extend\codec\CodeToolkit;
use think\admin\extend\data\ArrayTree;
use think\admin\Library;
use think\admin\model\SystemAuth;
use think\admin\model\SystemNode;
use think\admin\model\SystemUser;
use think\admin\node\NodeService;
use think\admin\Service;
use think\admin\system\SystemService;
use think\helper\Str;
use think\Request;

/**
 * 系统权限管理服务
 * @class AdminService
 */
class AdminService extends Service
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
     * 后台登录引导参数。
     * 纯 Token 模式下，首次整页跳转无法附带 Authorization，请先使用一次性引导码完成壳页加载。
     */
    private const BOOTSTRAP_QUERY = 'access_key';

    /**
     * 后台登录引导缓存前缀。
     */
    private const BOOTSTRAP_PREFIX = 'think.admin.bootstrap.';

    /**
     * 后台登录引导有效期（秒）。
     */
    private const BOOTSTRAP_EXPIRE = 120;

    /**
     * JWT 用户类型.
     */
    private const TOKEN_TYPE = 'admin-auth';

    /**
     * JWT 上传类型.
     */
    private const TOKEN_UPLOAD = 'admin-upload';

    /**
     * 表单上传令牌有效期.
     */
    private const DEFAULT_UPLOAD_EXPIRE = 1800;

    /**
     * 后台令牌失效时间记录键前缀.
     */
    private const TOKEN_INVALIDATE_PREFIX = 'AdminTokenInvalidateAt_';

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
        $user = static::currentUser();
        return is_null($field) ? $user : ($user[$field] ?? $default);
    }

    /**
     * 设置当前登录用户并刷新权限.
     */
    public static function login(array $user): array
    {
        RequestContext::instance()->clearAuth();
        return static::bindUser(static::normalizeUser($user), true);
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
        if (($uid = static::resolveLogoutUserId()) > 0) {
            SystemService::setData(self::TOKEN_INVALIDATE_PREFIX . $uid, time());
        }
        static::forget();
    }

    /**
     * 获取后台认证令牌.
     */
    public static function buildToken(?array $user = null): string
    {
        $user = $user ?: static::currentUser();
        if (empty($user['id']) || empty($user['password'])) {
            return '';
        }
        $payload = [
            'typ' => self::TOKEN_TYPE,
            'uid' => intval($user['id']),
            'pwd' => sha1(strval($user['password'])),
        ];
        if (($expire = static::getTokenExpire()) > 0) {
            $payload['exp'] = time() + $expire;
        }
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

        $user = SystemUser::mk()->where(['id' => intval($data['uid'])])->findOrEmpty()->toArray();

        if (empty($user)) {
            throw new Exception('用户不存在或已被删除，请重新登录！');
        }
        if (empty($user['status'])) {
            throw new Exception('账号已经被禁用，请联系管理员！');
        }
        if (($invalidAt = static::getTokenInvalidAt(intval($user['id']))) > 0 && intval($data['iat'] ?? 0) <= $invalidAt) {
            throw new Exception('登录状态已失效，请重新登录！');
        }
        if (sha1(strval($user['password'])) !== strval($data['pwd'] ?? '')) {
            throw new Exception('登录状态已失效，请重新登录！');
        }

        $context->setToken($token);
        return static::bindUser($user, $force);
    }

    /**
     * 获取请求中的认证令牌.
     */
    public static function requestToken(?Request $request = null): string
    {
        $request = $request ?: Library::$sapp->request;
        $token = static::parseRequestToken(strval($request->header(static::getTokenHeader(), '')));
        if ($token !== '') {
            return $token;
        }

        return static::consumeBootstrap(strval($request->param(static::getBootstrapQuery(), '')));
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
     * 获取登录引导 Query 参数名。
     */
    public static function getBootstrapQuery(): string
    {
        return self::BOOTSTRAP_QUERY;
    }

    /**
     * 生成后台首次跳转引导码。
     */
    public static function buildBootstrap(?string $token = null): string
    {
        $token = $token ?: static::buildToken();
        if ($token === '') {
            return '';
        }
        $bootstrap = CodeToolkit::uuid();
        Library::$sapp->cache->set(self::BOOTSTRAP_PREFIX . md5($bootstrap), $token, self::BOOTSTRAP_EXPIRE);
        return $bootstrap;
    }

    /**
     * 构建标准认证头内容.
     */
    public static function buildTokenHeader(?string $token = null): string
    {
        $token = static::parseRequestToken((string)$token);
        return $token === '' ? '' : self::TOKEN_SCHEME . ' ' . $token;
    }

    /**
     * 获取认证令牌有效期.
     */
    public static function getTokenExpire(): int
    {
        return max(0, intval(Library::$sapp->config->get('app.admin_token_expire') ?: 604800));
    }

    /**
     * 获取上传令牌有效期.
     */
    public static function getUploadTokenExpire(): int
    {
        return max(60, intval(Library::$sapp->config->get('app.admin_upload_token_expire') ?: self::DEFAULT_UPLOAD_EXPIRE));
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
        // 自定义权限检查回调
        if (count(self::$checkCallables) > 0) {
            foreach (self::$checkCallables as $callable) {
                if ($callable($current, $methods, $userNodes) === false) {
                    return false;
                }
            }
            return true;
        }
        // 自定义权限检查方法
        if (function_exists('admin_check_filter')) {
            return call_user_func('admin_check_filter', $current, $methods, $userNodes);
        }
        // 超级用户不需要检查权限
        if (static::isSuper()) {
            return true;
        }
        // 节点权限检查，需要兼容 windows 控制器不区分大小写，统一去除节点下划线再检查权限
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
        $user = static::currentUser();
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

    /**
     * 获取当前用户.
     */
    private static function currentUser(): array
    {
        // 这里只能读取请求内状态，不能再把 ready 标记重置掉。
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
        $user = static::normalizeUser($user);
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
     * 获取用户令牌最近失效时间.
     */
    private static function getTokenInvalidAt(int $uid): int
    {
        if ($uid < 1) {
            return 0;
        }
        return intval(SystemService::getData(self::TOKEN_INVALIDATE_PREFIX . $uid, 0));
    }

    /**
     * 解析标准认证头.
     */
    private static function parseRequestToken(string $authorization): string
    {
        $authorization = trim($authorization);
        if ($authorization === '') {
            return '';
        }

        if (preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            $authorization = trim($matches[1]);
        }

        return preg_replace('/\s+/', '', $authorization) ?: '';
    }

    /**
     * 解析一次性登录引导码。
     */
    private static function consumeBootstrap(string $bootstrap): string
    {
        $bootstrap = trim($bootstrap);
        if ($bootstrap === '') {
            return '';
        }
        $cacheKey = self::BOOTSTRAP_PREFIX . md5($bootstrap);
        $token = strval(Library::$sapp->cache->get($cacheKey, ''));
        Library::$sapp->cache->delete($cacheKey);
        return static::parseRequestToken($token);
    }
}
