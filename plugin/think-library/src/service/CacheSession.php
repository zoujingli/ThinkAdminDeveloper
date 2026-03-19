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

namespace think\admin\service;

use Psr\SimpleCache\InvalidArgumentException;
use think\admin\Exception;
use think\admin\Library;
use think\admin\runtime\RequestContext;
use think\admin\Service;

/**
 * 基于 Token SID 的缓存会话服务。
 * 用于替代原先基于标准 Session 的临时用户态数据读写。
 * @class CacheSession
 */
final class CacheSession extends Service
{
    /**
     * 会话缓存前缀。
     */
    private const CACHE_PREFIX = 'think.user.session.data.';

    /**
     * 会话索引缓存键。
     */
    private const INDEX_KEY = 'think.user.session.index';

    /**
     * 垃圾清理锁缓存键。
     */
    private const GC_KEY = 'think.user.session.gc.next';

    /**
     * 默认过期时间（秒）。
     */
    private const DEFAULT_EXPIRE = 7200;

    /**
     * 默认清理间隔（秒）。
     */
    private const DEFAULT_GC_INTERVAL = 300;

    /**
     * 读取指定会话数据别名。
     * @param mixed $default
     * @return mixed
     * @throws Exception
     */
    public static function read(string $name, $default = null, ?string $scope = null, ?bool $touch = null)
    {
        return self::get($name, $default, $scope, $touch);
    }

    /**
     * 读取指定会话数据。
     * @param mixed $default
     * @return mixed
     * @throws Exception
     */
    public static function get(string $name, $default = null, ?string $scope = null, ?bool $touch = null)
    {
        $data = self::all($scope, $touch);
        return $data[$name] ?? $default;
    }

    /**
     * 读取全部会话数据。
     * @return array<string, mixed>
     * @throws Exception|InvalidArgumentException
     */
    public static function all(?string $scope = null, ?bool $touch = null): array
    {
        self::sweep();
        $scope = self::scope($scope);
        $cache = self::store();
        $key = self::sessionKey($scope);
        $payload = $cache->get($key, []);
        if (!is_array($payload) || !array_key_exists('data', $payload) || !is_array($payload['data'])) {
            self::dropIndex($key);
            return [];
        }

        $touch = is_null($touch) ? self::autoTouch() : $touch;
        if ($touch && intval($payload['expire'] ?? 0) > 0) {
            $payload['updated_at'] = time();
            $cache->set($key, $payload, intval($payload['expire']));
            self::saveIndex($key, intval($payload['expire']));
        }

        return $payload['data'];
    }

    /**
     * 惰性清理已过期会话。
     */
    public static function sweep(bool $force = false): int
    {
        $cache = self::store();
        $interval = self::gcInterval();
        $now = time();
        if (!$force && $interval > 0 && intval($cache->get(self::GC_KEY, 0)) > $now) {
            return 0;
        }

        $index = $cache->get(self::INDEX_KEY, []);
        if (!is_array($index)) {
            $index = [];
        }

        $count = 0;
        foreach ($index as $key => $expireAt) {
            $expireAt = intval($expireAt);
            if ($expireAt > 0 && $expireAt <= $now) {
                $cache->delete($key);
                unset($index[$key]);
                ++$count;
            } elseif ($expireAt <= 0 && !$cache->has($key)) {
                unset($index[$key]);
            }
        }

        $cache->set(self::INDEX_KEY, $index);
        $cache->set(self::GC_KEY, $now + $interval, max(60, $interval));
        return $count;
    }

    /**
     * 删除指定会话字段。
     * @throws Exception
     */
    public static function delete(string $name, ?string $scope = null): bool
    {
        self::sweep();
        $scope = self::scope($scope);
        $cache = self::store();
        $key = self::sessionKey($scope);
        $payload = self::payload($scope, $cache->get($key, []));
        if (!array_key_exists($name, $payload['data'])) {
            return true;
        }

        unset($payload['data'][$name]);
        $payload['updated_at'] = time();
        if (!$cache->set($key, $payload, intval($payload['expire']))) {
            return false;
        }

        self::saveIndex($key, intval($payload['expire']));
        return true;
    }

    /**
     * 获取当前会话作用域。
     * @throws Exception
     */
    public static function scope(?string $scope = null): string
    {
        $scope = trim(strval($scope));
        if ($scope !== '') {
            return $scope;
        }

        if (($sessionId = self::currentSessionId()) !== '') {
            return "sid:{$sessionId}";
        }

        throw new Exception('令牌会话未初始化，请先完成 Token 鉴权或显式传入作用域标识！', 401);
    }

    /**
     * 获取缓存会话键名。
     * @throws Exception
     */
    public static function sessionKey(?string $scope = null): string
    {
        return self::CACHE_PREFIX . hash('sha256', self::scope($scope));
    }

    /**
     * 写入指定会话数据。
     * @param mixed $value
     * @throws Exception
     */
    public static function set(string $name, $value, ?int $expire = null, ?string $scope = null): bool
    {
        return self::put([$name => $value], $expire, $scope);
    }

    /**
     * 批量写入会话数据。
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public static function put(array $data, ?int $expire = null, ?string $scope = null, bool $replace = false): bool
    {
        self::sweep();
        $scope = self::scope($scope);
        $cache = self::store();
        $key = self::sessionKey($scope);
        $payload = self::payload($scope, $cache->get($key, []));
        $payload['expire'] = self::ttl(is_null($expire) ? (intval($payload['expire'] ?? 0) ?: self::getExpire()) : $expire);
        $payload['updated_at'] = time();
        $payload['data'] = $replace ? $data : array_merge($payload['data'], $data);
        if (!$cache->set($key, $payload, $payload['expire'])) {
            return false;
        }

        self::saveIndex($key, $payload['expire']);
        return true;
    }

    /**
     * 判断指定键是否存在。
     */
    public static function has(string $name, ?string $scope = null): bool
    {
        return array_key_exists($name, self::all($scope, false));
    }

    /**
     * 写入指定会话数据别名。
     * @param mixed $value
     * @throws Exception
     */
    public static function write(string $name, $value, ?int $expire = null, ?string $scope = null): bool
    {
        return self::set($name, $value, $expire, $scope);
    }

    /**
     * 读取并删除指定会话字段。
     * @param mixed $default
     * @return mixed
     * @throws Exception
     */
    public static function pull(string $name, $default = null, ?string $scope = null)
    {
        $value = self::get($name, $default, $scope, false);
        self::delete($name, $scope);
        return $value;
    }

    /**
     * 清空当前会话数据，但保留会话本身。
     * @throws Exception
     */
    public static function clear(?string $scope = null): bool
    {
        $scope = self::scope($scope);
        $cache = self::store();
        $key = self::sessionKey($scope);
        $payload = self::payload($scope, $cache->get($key, []));
        if (!self::exists($scope)) {
            self::dropIndex($key);
            return false;
        }

        $payload['updated_at'] = time();
        $payload['data'] = [];
        if (!$cache->set($key, $payload, intval($payload['expire']))) {
            return false;
        }

        self::saveIndex($key, intval($payload['expire']));
        return true;
    }

    /**
     * 判断当前会话是否存在。
     * @throws Exception
     */
    public static function exists(?string $scope = null): bool
    {
        self::sweep();
        $payload = self::store()->get(self::sessionKey($scope), null);
        return is_array($payload) && array_key_exists('data', $payload) && is_array($payload['data']);
    }

    /**
     * 销毁当前会话别名。
     * @throws Exception
     */
    public static function forget(?string $scope = null): bool
    {
        return self::destroy($scope);
    }

    /**
     * 销毁当前会话。
     * @throws Exception
     */
    public static function destroy(?string $scope = null): bool
    {
        $scope = self::scope($scope);
        $key = self::sessionKey($scope);
        self::dropIndex($key);
        self::store()->delete($key);
        return true;
    }

    /**
     * 刷新当前会话过期时间。
     * @throws Exception
     */
    public static function touch(?int $expire = null, ?string $scope = null): bool
    {
        self::sweep();
        $scope = self::scope($scope);
        $cache = self::store();
        $key = self::sessionKey($scope);
        $payload = self::payload($scope, $cache->get($key, []));
        if (empty($payload['data'])) {
            if (!self::exists($scope)) {
                self::dropIndex($key);
                return false;
            }
        }

        $payload['expire'] = self::ttl(is_null($expire) ? intval($payload['expire'] ?? 0) : $expire);
        $payload['updated_at'] = time();
        if (!$cache->set($key, $payload, intval($payload['expire']))) {
            return false;
        }

        self::saveIndex($key, intval($payload['expire']));
        return true;
    }

    /**
     * 垃圾清理别名。
     */
    public static function gc(bool $force = false): int
    {
        return self::sweep($force);
    }

    /**
     * 获取缓存驱动。
     */
    private static function store()
    {
        $store = trim(strval(self::config('token_session_store', '')));
        return $store === '' ? Library::$sapp->cache : Library::$sapp->cache->store($store);
    }

    /**
     * 读取令牌会话配置。
     * @param mixed $default
     * @return mixed
     */
    private static function config(string $name, $default = null)
    {
        $config = Library::$sapp->config->get('app', []);
        if (is_array($config) && array_key_exists($name, $config)) {
            return $config[$name];
        }
        return $default;
    }

    /**
     * 获取垃圾清理间隔。
     */
    private static function gcInterval(): int
    {
        return max(60, intval(self::config('token_session_gc_interval', self::DEFAULT_GC_INTERVAL)));
    }

    /**
     * 获取当前请求绑定的认证会话编号。
     */
    private static function currentSessionId(): string
    {
        $sessionId = RequestContext::instance()->sessionId();
        if ($sessionId !== '') {
            return $sessionId;
        }

        return trim(strval(sysvar('plugin_account_user_session_id') ?: ''));
    }

    /**
     * 规范化缓存载荷。
     * @param mixed $payload
     * @return array{scope:string,expire:int,updated_at:int,data:array<string,mixed>}
     */
    private static function payload(string $scope, $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        return [
            'scope' => $scope,
            'expire' => self::ttl(is_array($payload) ? intval($payload['expire'] ?? 0) : 0),
            'updated_at' => is_array($payload) ? intval($payload['updated_at'] ?? time()) : time(),
            'data' => $data,
        ];
    }

    /**
     * 获取缓存会话默认过期时间。
     */
    private static function ttl(?int $expire = null): int
    {
        $expire = is_null($expire) ? self::getExpire() : $expire;
        return max(0, intval($expire));
    }

    /**
     * 获取默认过期时间。
     */
    private static function getExpire(): int
    {
        return max(0, intval(self::config('token_session_expire', self::DEFAULT_EXPIRE)));
    }

    /**
     * 保存会话索引。
     */
    private static function saveIndex(string $key, int $expire): void
    {
        $cache = self::store();
        $index = $cache->get(self::INDEX_KEY, []);
        if (!is_array($index)) {
            $index = [];
        }

        $index[$key] = $expire > 0 ? time() + $expire : 0;
        $cache->set(self::INDEX_KEY, $index);
    }

    /**
     * 删除会话索引。
     */
    private static function dropIndex(string $key): void
    {
        $cache = self::store();
        $index = $cache->get(self::INDEX_KEY, []);
        if (!is_array($index) || !array_key_exists($key, $index)) {
            return;
        }

        unset($index[$key]);
        $cache->set(self::INDEX_KEY, $index);
    }

    /**
     * 获取自动续期配置。
     */
    private static function autoTouch(): bool
    {
        return boolval(self::config('token_session_touch', true));
    }
}
