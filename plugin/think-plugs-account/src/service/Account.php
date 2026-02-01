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

namespace plugin\account\service;

use plugin\account\model\PluginAccountAuth;
use plugin\account\service\contract\AccountAccess;
use plugin\account\service\contract\AccountInterface;
use think\admin\Exception;
use think\admin\extend\JwtExtend;

/**
 * 用户账号调度器.
 * @class Account
 */
abstract class Account
{
    public const WAP = 'wap';

    public const WEB = 'web';

    public const WXAPP = 'wxapp';

    public const WECHAT = 'wechat';

    public const IOSAPP = 'iosapp';

    public const ANDROID = 'android';

    // 已禁用的账号通道
    private static $denys;

    private static $cacheKey = 'plugin.account.denys';

    private static $types = [
        self::WAP => ['name' => '手机浏览器', 'field' => 'phone', 'status' => 1],
        self::WEB => ['name' => '电脑浏览器', 'field' => 'phone', 'status' => 1],
        self::WXAPP => ['name' => '微信小程序', 'field' => 'openid', 'status' => 1],
        self::WECHAT => ['name' => '微信服务号', 'field' => 'openid', 'status' => 1],
        self::IOSAPP => ['name' => '苹果APP应用', 'field' => 'phone', 'status' => 1],
        self::ANDROID => ['name' => '安卓APP应用', 'field' => 'phone', 'status' => 1],
    ];

    /**
     * 创建账号实例.
     * @param string $type 通道编号
     * @param array|string $token 令牌或条件
     * @param bool $isjwt 是否JWT模式
     * @throws Exception
     */
    public static function mk(string $type, $token = '', bool $isjwt = true): AccountInterface
    {
        if ($token === AccountAccess::tester) {
            if (empty($type)) {
                $type = PluginAccountAuth::mk()->where(['token' => $token])->value('type');
                if (empty($type)) {
                    throw new Exception('账号不存在！');
                }
            }
        } elseif ($isjwt && is_string($token) && strlen($token) > 32) {
            $data = JwtExtend::verify($token);
            [$type, $token] = [$type ?: ($data['type'] ?? ''), $data['token'] ?? $token];
            if (($data['type'] ?? '') !== $type) {
                throw new Exception('授权不匹配！');
            }
        }
        if (($field = self::field($type)) || is_array($token)) {
            $vars = ['type' => $type, 'field' => $field];
            return app(AccountAccess::class, $vars, true)->init($token, $isjwt);
        }
        throw new Exception('登录已超时！', 401);
    }

    /**
     * 动态增加通道.
     * @return array[]
     */
    public static function add(string $type, string $name, string $field = 'phone'): array
    {
        self::$types[$type] = ['name' => $name, 'field' => $field, 'status' => 1];
        return self::types();
    }

    /**
     * 设置通道状态
     * @param string $type 通道编号
     * @param int $status 通道状态
     */
    public static function set(string $type, int $status): bool
    {
        if (isset(self::$types[$type])) {
            self::$types[$type]['status'] = $status;
            return true;
        }
        return false;
    }

    /**
     * 获取通道参数.
     */
    public static function get(string $type): array
    {
        return self::$types[$type] ?? [];
    }

    /**
     * 获取全部通道.
     * @param ?int $status 指定状态
     */
    public static function types(?int $status = null): array
    {
        try {
            $all = [];
            foreach (self::init() as $type => $item) {
                $item['code'] = $type;
                if (is_null($status) || $item['status'] === $status) {
                    $all[$type] = $item;
                }
            }
            return $all;
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * 保存用户通道状态
     * @return mixed
     * @throws Exception
     */
    public static function save()
    {
        self::$denys = [];
        foreach (self::types() as $k => $v) {
            if (empty($v['status'])) {
                self::$denys[] = $k;
            }
        }
        return sysdata(self::$cacheKey, self::$denys);
    }

    /**
     * 获取认证字段.
     * @param string $type 通道编码
     */
    public static function field(string $type): string
    {
        $types = self::init();
        if (!empty($types[$type]['status'])) {
            return $types[$type]['field'] ?? '';
        }
        return '';
    }

    /**
     * 接口授权有效时间及默认头像.
     * @param null|int|string $expire 有效时间
     * @param null|string $headimg 默认头像
     * @throws Exception
     */
    public static function expire($expire = null, ?string $headimg = null): int
    {
        $data = sysdata('plugin.account.access');
        if (!is_null($expire) || !is_null($headimg)) {
            if (!is_null($expire)) {
                $data['expire'] = $expire;
            }
            if (!is_null($headimg)) {
                $data['headimg'] = $headimg;
            }
            $data = sysdata('plugin.account.access', $data);
        }
        return intval($data['expire'] ?? 0);
    }

    /**
     * 解析请求令牌.
     * @throws Exception
     */
    public static function token(string $token = '', ?string &$type = null): AccountInterface
    {
        if ($token === AccountAccess::tester) {
            $map = ['token' => $token];
            empty($type) || ($map['type'] = $type);
            $auth = PluginAccountAuth::mk()->where($map)->findOrEmpty();
            if ($auth->isEmpty()) {
                throw new Exception('账号不存在！');
            }
            return static::mk($type = $auth->getAttr('type'), $auth->getAttr('token'));
        }
        $data = JwtExtend::verify($token);
        return static::mk($type = $data['type'] ?? '-', $data['token'] ?? '-');
    }

    /**
     * 账号配置参数设置与读取.
     * @param null|array|string $data
     * @return null|mixed|void
     * @throws Exception
     */
    public static function config($data = null)
    {
        if (is_null($data)) {
            return sysdata('plugin.account.access');
        }
        if (is_array($data)) {
            return sysdata('plugin.account.access', $data);
        }
        if (is_string($data)) {
            return sysdata('plugin.account.access')[$data] ?? null;
        }
        return null;
    }

    /**
     * 是否自动注册.
     * @throws Exception
     */
    public static function enableAutoReigster(): bool
    {
        return empty(self::config('disRegister'));
    }

    /**
     * 获取默认头像.
     * @throws Exception
     */
    public static function headimg(?string $headimg = null): string
    {
        $data = sysdata('plugin.account.access');
        if (!is_null($headimg)) {
            $data['headimg'] = $headimg;
            sysdata('plugin.account.access', $data);
        }
        return $data['headimg'] ?? 'https://thinkadmin.top/static/img/logo.png';
    }

    /**
     * 初始化数据状态
     * @return array[]
     */
    private static function init(): array
    {
        if (is_null(self::$denys)) {
            try {
                self::$denys = sysdata(self::$cacheKey);
                foreach (self::$types as $type => &$item) {
                    $item['status'] = intval(!in_array($type, self::$denys));
                }
            } catch (\Exception $exception) {
            }
        }
        return self::$types;
    }
}
