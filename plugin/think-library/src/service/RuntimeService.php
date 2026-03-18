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

namespace think\admin\service;

use Exception;
use think\admin\Library;
use think\admin\route\Route;
use think\admin\route\Url;
use think\admin\runtime\SystemContext;
use think\App;
use think\Container;
use think\Request;
use think\Response;

/**
 * 系统运行服务
 * @class RuntimeService
 */
class RuntimeService
{
    /**
     * 开发运行模式.
     * @var string
     */
    public const MODE_DEV = 'dev';

    /**
     * 演示运行模式.
     * @var string
     */
    public const MODE_DEMO = 'demo';

    /**
     * 本地运行模式.
     * @var string
     */
    public const MODE_LOCAL = 'local';

    /**
     * 环境配置文件位置.
     * @var string
     */
    private static $envFile = './runtime/.env';

    /**
     * 初始化文件哈希值
     * @var string
     */
    private static $envHash = '';

    /**
     * 同步运行配置.
     */
    public static function sync()
    {
        clearstatcache(true, self::$envFile);
        is_file(self::$envFile) && md5_file(self::$envFile) !== self::$envHash && self::apply();
    }

    /**
     * 绑定动态配置.
     * @param array $data 配置数据
     * @return bool 是否调试模式
     */
    public static function apply(array $data = []): bool
    {
        $data = array_merge(static::get(), $data);
        is_file(self::$envFile) && self::$envHash = md5_file(self::$envFile);
        return Library::$sapp->debug($data['mode'] !== 'product')->isDebug();
    }

    /**
     * 获取动态配置.
     * @param null|string $name 配置名称
     * @param array $default 配置内容
     * @return array|string
     */
    public static function get(?string $name = null, array $default = [])
    {
        $keys = 'think.admin.runtime';
        if (empty($envs = sysvar($keys) ?: [])) {
            // 读取默认配置
            clearstatcache(true, self::$envFile);
            is_file(self::$envFile) && Library::$sapp->env->load(self::$envFile);
            // 动态判断赋值
            $envs['mode'] = Library::$sapp->env->get('RUNTIME_MODE') ?: 'debug';
            $envs['appmap'] = [];
            $envs['domain'] = [];
            sysvar($keys, $envs);
        }
        return is_null($name) ? $envs : ($envs[$name] ?? $default);
    }

    /**
     * 开发模式运行.
     */
    public static function isDebug(): bool
    {
        return static::get('mode') !== 'product';
    }

    /**
     * 压缩发布项目.
     */
    public static function push(): string
    {
        self::set('product');
        $connection = Library::$sapp->db->getConfig('default');
        Library::$sapp->console->call('optimize:schema', ["--connection={$connection}"]);
        return $connection;
    }

    /**
     * 设置动态配置.
     * @param null|mixed $mode 支持模式
     * @param null|array $appmap 历史保留参数（已停用）
     * @param null|array $domain 历史保留参数（已停用）
     * @return bool 是否调试模式
     */
    public static function set(?string $mode = null, ?array $appmap = [], ?array $domain = []): bool
    {
        $envs = self::get();
        $envs['mode'] = is_null($mode) ? $envs['mode'] : $mode;
        $envs['appmap'] = [];
        $envs['domain'] = [];

        // 组装配置文件格式
        $rows[] = "mode = {$envs['mode']}";

        is_dir($dir = dirname(self::$envFile)) || @mkdir($dir, 0777, true);

        // 写入并刷新文件哈希值
        @file_put_contents(self::$envFile, "[RUNTIME]\n" . join("\n", $rows));

        // 同步更新当前环境
        sysvar('think.admin.runtime', $envs);

        //  应用当前的配置文件
        return static::apply($envs);
    }

    /**
     * 判断运行环境.
     * @param string $type 运行模式（dev|demo|local）
     */
    public static function check(string $type = 'dev'): bool
    {
        $domain = Library::$sapp->request->host(true);
        $isDemo = boolval(preg_match('|v\d+\.thinkadmin\.top|', $domain));
        $isLocal = $domain === '127.0.0.1' || is_numeric(stripos($domain, 'local'));
        if ($type === static::MODE_DEV) {
            return $isLocal || $isDemo;
        }
        if ($type === static::MODE_DEMO) {
            return $isDemo;
        }
        if ($type === static::MODE_LOCAL) {
            return $isLocal;
        }
        return true;
    }

    /**
     * 清理运行缓存.
     * @param bool $force 清理目录
     */
    public static function clear(bool $force = true): bool
    {
        $data = static::get();
        SystemContext::clearAuth() && Library::$sapp->cache->clear();
        $force && Library::$sapp->console->call('clear', ['--dir']);
        static::set($data['mode']);
        return true;
    }

    /**
     * 生产模式运行.
     */
    public static function isOnline(): bool
    {
        return static::get('mode') === 'product';
    }

    /**
     * 初始化主程序.
     */
    public static function doWebsiteInit(?App $app = null, ?Request $request = null): Response
    {
        $http = static::init($app)->http;
        $request = $request ?: Library::$sapp->make(Request::class);
        Library::$sapp->instance('request', $request);
        ($response = $http->run($request))->send();
        $http->end($response);
        return $response;
    }

    /**
     * 系统服务初始化.
     */
    public static function init(?App $app = null): App
    {
        // 初始化运行环境
        Library::$sapp = $app ?: Container::getInstance()->make(App::class);
        Library::$sapp->bind('think\Route', Route::class);
        Library::$sapp->bind('think\route\Url', Url::class);
        // 初始化运行配置位置
        // 运行配置固定落在可写目录（Phar 环境为安装目录，普通环境为项目根目录）
        self::$envFile = runpath('runtime/.env');
        return Library::$sapp->debug(static::isDebug());
    }

    /**
     * 初始化命令行.
     */
    public static function doConsoleInit(?App $app = null): int
    {
        try {
            return static::init($app)->console->run();
        } catch (Exception $exception) {
            ProcessService::message($exception->getMessage());
            return 0;
        }
    }
}
