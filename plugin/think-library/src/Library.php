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

namespace think\admin;

use think\admin\extend\FileTools;
use think\admin\middleware\MultAccess;
use think\admin\runtime\RequestContext;
use think\admin\service\RuntimeService;
use think\App;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\Request;
use think\Response;
use think\Service;

/**
 * 模块注册服务
 * @class Library
 */
class Library extends Service
{
    public static App $sapp;

    /**
     * 启动服务
     */
    public function boot()
    {
        // 静态应用赋值
        static::$sapp = $this->app;

        // 动态应用运行参数
        RuntimeService::apply();

        // 请求初始化处理
        $this->app->event->listen('HttpRun', function (Request $request) {
            // Worker 常驻模式下，请求开始前先清理上次上下文。
            RequestContext::clear();

            // 运行环境配置同步
            RuntimeService::sync();

            // 配置默认输入过滤
            $request->filter([static function ($value) {
                return is_string($value) ? xss_safe($value) : $value;
            }]);

            // 判断访问模式兼容处理
            if ($this->app->runningInConsole()) {
                // 兼容 CLI 访问控制器
                if (empty($_SERVER['REQUEST_URI']) && isset($_SERVER['argv'][1])) {
                    $request->setPathinfo($_SERVER['argv'][1]);
                }
            } else {
                // 兼容 HTTP 调用 Console 后 URL 问题
                $request->setHost($request->host());
            }

            // 注册多应用中间键
            $this->app->middleware->add(MultAccess::class);
        });

        // 请求结束后处理
        $this->app->event->listen('HttpEnd', static function () {
            RequestContext::clear();
            function_exists('sysvar') && sysvar('', '');
        });
    }

    /**
     * 初始化服务
     */
    public function register(): void
    {
        // 动态加载全局配置
        [$dir, $ext] = [$this->app->getBasePath(), $this->app->getConfigExt()];
        FileTools::find($dir, 2, function (\SplFileInfo $info) use ($ext) {
            $info->isFile() && $info->getBasename() === "sys{$ext}" && Library::load($info->getPathname());
        });
        if (is_file($file = "{$dir}common{$ext}")) {
            Library::load($file);
        }
        if (is_file($file = "{$dir}provider{$ext}")) {
            $this->app->bind(include $file);
        }
        if (is_file($file = "{$dir}event{$ext}")) {
            $this->app->loadEvent(include $file);
        }
        if (is_file($file = "{$dir}middleware{$ext}")) {
            $this->app->middleware->import(include $file, 'app');
        }

        // 终端 HTTP 访问时特殊处理
        if (!$this->app->runningInConsole()) {
            // 动态注释 CORS 跨域处理
            $this->app->middleware->add(function (Request $request, \Closure $next): Response {
                $header = ['X-Frame-Options' => $this->app->config->get('app.cors_frame') ?: 'sameorigin'];
                // HTTP.CORS 跨域规则配置
                if ($this->app->config->get('app.cors_on', true) && ($origin = $request->header('origin', '-')) !== '-') {
                    if (is_string($hosts = $this->app->config->get('app.cors_host', []))) {
                        $hosts = str2arr($hosts);
                    }
                    if (empty($hosts) || in_array(parse_url(strtolower($origin), PHP_URL_HOST), $hosts)) {
                        $headers = str2arr(strval($this->app->config->get('app.cors_headers', 'X-Device-Code,X-Device-Type')));
                        $headers = array_values(array_filter(array_unique(array_map('trim', $headers))));
                        $header['Access-Control-Allow-Origin'] = $origin;
                        $header['Access-Control-Allow-Methods'] = $this->app->config->get('app.cors_methods', 'GET,PUT,POST,PATCH,DELETE');
                        $allow = array_merge(['Authorization', 'Content-Type', 'If-Match', 'If-Modified-Since', 'If-None-Match', 'If-Unmodified-Since', 'X-Requested-With'], $headers);
                        $header['Access-Control-Allow-Headers'] = join(',', array_unique($allow));
                        if ($this->app->config->get('app.cors_credentials', false)) {
                            $header['Access-Control-Allow-Credentials'] = 'true';
                        }
                        if (!empty($headers)) {
                            $header['Access-Control-Expose-Headers'] = join(',', $headers);
                        }
                    }
                }
                // 跨域预请求状态处理
                if ($request->isOptions()) {
                    throw new HttpResponseException(response()->code(204)->header($header));
                }
                return $next($request)->header($header);
            });
        }
    }

    /**
     * 动态加载文件.
     * @return mixed
     */
    public static function load(string $file)
    {
        try {
            return include $file;
        } catch (\Error|\Throwable $error) {
            trace_file($error);
            throw new HttpException(500, $error->getMessage());
        }
    }
}
