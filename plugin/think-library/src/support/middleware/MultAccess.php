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

namespace think\admin\support\middleware;

use think\admin\extend\ToolsExtend;
use think\admin\Library;
use think\admin\service\AppService;
use think\admin\service\NodeService;
use think\admin\service\SystemService;
use think\App;
use think\exception\HttpException;
use think\Request;
use think\Response;

/**
 * 多应用调度中间键.
 * @class MultAccess
 */
class MultAccess
{
    /**
     * 应用实例.
     * @var App
     */
    private $app;

    /**
     * 应用路径.
     * @var string
     */
    private $appPath;

    /**
     * 应用空间.
     * @var string
     */
    private $appSpace;

    /**
     * App constructor.
     */
    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 多应用解析.
     */
    public function handle(Request $request, \Closure $next): Response
    {
        [$this->appPath, $this->appSpace] = ['', ''];
        if (!$this->parseMultiApp()) {
            return $next($request);
        }
        return $this->app->middleware->pipeline('app')->send($request)->then(function ($request) use ($next) {
            return $next($request);
        });
    }

    /**
     * 解析多应用.
     */
    private function parseMultiApp(): bool
    {
        $defaultApp = $this->app->config->get('route.default_app') ?: 'index';
        $apps = AppService::get();
        [$script, $pathinfo] = [$this->scriptName(), $this->app->request->pathinfo()];
        if ($script && !in_array($script, ['index', 'router', 'think'])) {
            $this->app->request->setPathinfo(preg_replace("#^{$script}\\.php(/|\\.|$)#i", '', $pathinfo) ?: '/');
            return $this->setMultiApp($script, true);
        }
        // 域名绑定处理
        $domains = $this->app->config->get('app.domain_bind', []);
        if (!empty($domains)) {
            foreach ([$this->app->request->host(true), $this->app->request->subDomain(), '*'] as $key) {
                if (isset($domains[$key])) {
                    return $this->setMultiApp($domains[$key], true);
                }
            }
        }
        $name = current(explode('/', $pathinfo));
        if (strpos($name, '.')) {
            $name = strstr($name, '.', true);
        }
        // 应用绑定与插件处理
        $appmap = $this->app->config->get('app.app_map', []);
        if (isset($appmap[$name])) {
            $appName = $appmap[$name] instanceof \Closure ? (call_user_func_array($appmap[$name], [$this->app]) ?: $name) : $appmap[$name];
        } elseif ($name && (in_array($name, $appmap) || in_array($name, $this->app->config->get('app.deny_app_list', [])))) {
            throw new HttpException(404, "app not exists: {$name}");
        } elseif ($name && isset($appmap['*'])) {
            $appName = $appmap['*'];
        } else {
            $appName = $name ?: $defaultApp;
            if (!isset($apps[$appName])) {
                return $this->app->config->get('app.app_express', false) && $this->setMultiApp($defaultApp, false);
            }
        }
        // 应用绑定处理
        if (isset($apps[$appName])) {
            [$this->appPath, $this->appSpace] = [$apps[$appName]['path'], $apps[$appName]['space']];
        }
        if ($name) {
            $this->app->request->setRoot('/' . $name);
            $this->app->request->setPathinfo(strpos($pathinfo, '/') ? ltrim(strstr($pathinfo, '/'), '/') : '');
        }

        return $this->setMultiApp($appName ?? $defaultApp, $this->app->http->isBind());
    }

    /**
     * 获取当前运行入口名称.
     * @codeCoverageIgnore
     */
    private function scriptName(): string
    {
        $file = $_SERVER['SCRIPT_FILENAME'] ?? ($_SERVER['argv'][0] ?? '');
        return empty($file) ? '' : pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * 设置应用参数.
     * @param string $appName 应用名称
     * @param bool $appBind 应用绑定
     */
    private function setMultiApp(string $appName, bool $appBind): bool
    {
        sysvar('CurrentPluginCode', $appName);
        if (empty($this->appPath) && ($app = AppService::get($appName))) {
            [$this->appPath, $this->appSpace] = [$app['path'], $app['space']];
        }
        if (is_dir($this->appPath)) {
            // 设置多应用模式
            $this->app->setNamespace($this->appSpace ?: NodeService::space($appName))->setAppPath($this->appPath);
            $this->app->http->setBind($appBind)->name($appName)->path($this->appPath)->setRoutePath($this->appPath . 'route' . DIRECTORY_SEPARATOR);
            // 修改模板参数配置
            $uris = array_merge($this->app->config->get('view.tpl_replace_string', []), SystemService::uris());
            $this->app->config->set(['view_path' => $this->appPath . 'view' . DIRECTORY_SEPARATOR, 'tpl_replace_string' => $uris], 'view');
            // 初始化多应用文件
            return $this->loadMultiApp($this->appPath);
        }
        return false;
    }

    /**
     * 加载应用文件.
     * @param string $appPath 应用路径
     * @codeCoverageIgnore
     */
    private function loadMultiApp(string $appPath): bool
    {
        [$ext, $fmaps] = [$this->app->getConfigExt(), []];
        // 加载应用函数文件
        if (is_file($file = "{$appPath}common{$ext}")) {
            Library::load($file);
        }
        // 加载应用配置文件
        ToolsExtend::find($appPath . 'config', 1, function (\SplFileInfo $info) use ($ext, &$fmaps) {
            if ($info->isFile() && strtolower(".{$info->getExtension()}") === $ext) {
                $name = $info->getBasename($ext);
                $fmaps[] = $name;
                $this->app->config->load($info->getPathname(), $name);
            }
        });
        // 加载应用路由配置
        if ((in_array('route', $fmaps, true) || is_dir($appPath . 'route')) && method_exists($this->app->route, 'reload')) {
            $this->app->route->reload();
        }
        // 加载应用映射配置
        if (is_file($file = "{$appPath}provider{$ext}")) {
            $this->app->bind(include $file);
        }
        // 加载应用事件配置
        if (is_file($file = "{$appPath}event{$ext}")) {
            $this->app->loadEvent(include $file);
        }
        // 加载应用中间键配置
        if (is_file($file = "{$appPath}middleware{$ext}")) {
            $this->app->middleware->import(include $file, 'app');
        }
        // 重新加载应用语言包
        if (method_exists($this->app->lang, 'switchLangSet')) {
            $this->app->lang->switchLangSet($this->app->lang->getLangSet());
        }
        return true;
    }
}
