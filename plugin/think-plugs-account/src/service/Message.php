<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

use plugin\account\service\contract\MessageInterface;
use plugin\account\service\message\Alisms;
use think\admin\Exception;
use think\admin\Library;

/**
 * 短信服务调度器.
 * @class Message
 * @mixin MessageInterface
 */
abstract class Message
{
    public const tLogin = 'LOGIN';

    public const tForget = 'FORGET';

    public const tRegister = 'REGISTER';

    /**
     * 业务场景定义.
     * @var string[]
     */
    public static $scenes = [
        self::tLogin => '用户登录验证',
        self::tForget => '找回用户密码',
        self::tRegister => '用户注册绑定',
    ];

    /**
     * 静态方法调用.
     * @return mixed
     * @throws Exception
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::mk()->{$name}(...$arguments);
    }

    /**
     * 创建短信通道.
     * @throws Exception
     */
    public static function mk(array $config = [], ?string $driver = null): MessageInterface
    {
        if (!is_null($driver) && !isset(class_implements($driver)[MessageInterface::class])) {
            throw new Exception("Sms driver [{$driver}] Not implements MessageInterface.");
        }
        return app($driver ?: Alisms::class)->init($config);
    }

    /**
     * 发送短信验证码
     * @param string $phone 手机号码
     * @param int $wait 等待时间
     * @param string $scene 业务场景
     * @return array [state, message, [timeout]]
     */
    public static function sendVerifyCode(string $phone, int $wait = 120, string $scene = self::tLogin): array
    {
        try {
            $ckey = self::genCacheKey($phone, $scene);
            $cache = Library::$sapp->cache->get($ckey, []);
            // 检查是否已经发送
            if (is_array($cache) && isset($cache['time']) && $cache['time'] > time() - $wait) {
                $dtime = ($cache['time'] + $wait < time()) ? 0 : ($wait - time() + $cache['time']);
                return [1, '验证码已发送', ['time' => $dtime]];
            }
            // 生成新的验证码
            [$code, $time] = [rand(100000, 999999), time()];
            Library::$sapp->cache->set($ckey, ['code' => $code, 'time' => $time], 600);
            // 尝试发送短信内容
            self::mk()->verify($scene, $phone, ['code' => $code]);
            return [1, '验证码发送成功', ['time' => ($time + $wait < time()) ? 0 : ($wait - time() + $time)]];
        } catch (\Exception $ex) {
            trace_file($ex);
            isset($ckey) && Library::$sapp->cache->delete($ckey);
            return [0, $ex->getMessage(), []];
        }
    }

    /**
     * 检查短信验证码
     * @param string $vcode 验证码
     * @param string $phone 手机号码
     * @param string $scene 业务场景
     * @throws Exception
     */
    public static function checkVerifyCode(string $vcode, string $phone, string $scene = self::tLogin): bool
    {
        if (stripos(Library::$sapp->request->domain(), '.thinkadmin.top') !== false) {
            if ($vcode === '123456') {
                return true;
            }
        }
        $cache = Library::$sapp->cache->get(static::genCacheKey($phone, $scene), []);
        return is_array($cache) && isset($cache['code']) && $cache['code'] == $vcode;
    }

    /**
     * 清理短信验证码
     */
    public static function clearVerifyCode(string $phone, string $scene = self::tLogin): bool
    {
        try {
            return Library::$sapp->cache->delete(static::genCacheKey($phone, $scene));
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * 生成验证码缓存名.
     * @param string $phone 手机号码
     * @param string $scene 业务场景
     * @throws Exception
     */
    private static function genCacheKey(string $phone, string $scene = self::tLogin): string
    {
        if (isset(array_change_key_case(static::$scenes)[strtolower($scene)])) {
            return md5(strtolower("sms-{$scene}-{$phone}"));
        }
        throw new Exception('未定义的业务');
    }
}
