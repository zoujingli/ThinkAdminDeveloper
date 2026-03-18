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

namespace think\admin\middleware;

use think\admin\extend\FileTools;
use think\admin\Library;
use think\admin\route\Route as AdminRoute;
use think\admin\runtime\RequestContext;
use think\admin\service\AppService;
use think\admin\service\NodeService;
use think\App;
use think\Request;
use think\Response;

/**
 * 多应用调度中间件.
 * @class MultAccess
 */
class MultAccess
{
    /**
     * 应用实例.
     */
    private App $app;

    /**
     * 当前应用路径.
     */
    private string $appPath = '';

    /**
     * 当前应用命名空间.
     */
    private string $appSpace = '';

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
     * 调度优先级：
     * 1. 显式插件前缀
     * 2. 显式本地 app 前缀
     * 3. 根路由声明的目标应用
     * 4. 动态插件切换
     * 5. 默认本地 app.
     */
    private function parseMultiApp(): bool
    {
        $request = $this->app->request;
        $pathinfo = $request->pathinfo();

        if ($plugin = AppService::matchPluginPath($pathinfo)) {
            return $this->applyPlugin($plugin, true);
        }

        if ($local = AppService::matchPath($pathinfo)) {
            return $this->applyLocal($local, true);
        }

        if ($target = $this->resolveGlobalRouteTarget($request)) {
            return ($target['type'] ?? '') === 'plugin'
                ? $this->applyPlugin($target, false)
                : $this->applyLocal($target, false);
        }

        $switch = AppService::detectPluginSwitch($request);
        if ($switch && ($plugin = AppService::resolvePlugin($switch))) {
            $plugin['entry'] = RequestContext::ENTRY_WEB;
            $plugin['matched_prefix'] = '';
            $plugin['pathinfo'] = $pathinfo;
            return $this->applyPlugin($plugin, false);
        }

        RequestContext::instance()->setEntryType(RequestContext::ENTRY_WEB);
        return $this->setMultiApp(AppService::singleCode(), true);
    }

    /**
     * 应用插件调度结果.
     *
     * @param array<string, mixed> $plugin
     */
    private function applyPlugin(array $plugin, bool $stripPrefix): bool
    {
        [$this->appPath, $this->appSpace] = [strval($plugin['path'] ?? ''), strval($plugin['space'] ?? '')];
        RequestContext::instance()->setEntryType(strval($plugin['entry'] ?? RequestContext::ENTRY_WEB));

        $prefix = trim(strval($plugin['matched_prefix'] ?? ''), '\/');
        if ($stripPrefix && $prefix !== '') {
            $root = strval($plugin['entry'] ?? '') === RequestContext::ENTRY_API
                ? '/' . trim(AppService::pluginApiPrefix() . '/' . $prefix, '/')
                : '/' . $prefix;
            $this->app->request->setRoot($root);
            $this->app->request->setPathinfo(strval($plugin['pathinfo'] ?? ''));
        }

        return $this->setMultiApp(strval($plugin['code'] ?? ''), true, $prefix);
    }

    /**
     * 设置应用参数.
     */
    private function setMultiApp(string $appName, bool $appBind, string $prefix = ''): bool
    {
        if ($appName === '') {
            return false;
        }

        $app = AppService::get($appName);
        ($app['type'] ?? '') === 'plugin' ? AppService::activatePlugin($appName, $prefix) : AppService::activatePlugin();

        if (empty($this->appPath) && $app) {
            [$this->appPath, $this->appSpace] = [strval($app['path'] ?? ''), strval($app['space'] ?? '')];
        }

        if (!is_dir($this->appPath)) {
            return false;
        }

        $this->app->setNamespace($this->appSpace ?: NodeService::space($appName))->setAppPath($this->appPath);
        $this->app->http->setBind($appBind)->name($appName)->path($this->appPath)->setRoutePath($this->appPath . 'route' . DIRECTORY_SEPARATOR);

        $uris = array_merge($this->app->config->get('view.tpl_replace_string', []), AppService::uris());
        $this->app->config->set([
            'view_path' => $this->appPath . 'view' . DIRECTORY_SEPARATOR,
            'tpl_replace_string' => $uris,
        ], 'view');

        return $this->loadMultiApp($this->appPath);
    }

    /**
     * 加载应用文件.
     * @codeCoverageIgnore
     */
    private function loadMultiApp(string $appPath): bool
    {
        [$ext, $fmaps] = [$this->app->getConfigExt(), []];

        if (is_file($file = "{$appPath}common{$ext}")) {
            Library::load($file);
        }

        FileTools::find($appPath . 'config', 1, function (\SplFileInfo $info) use ($ext, &$fmaps) {
            if ($info->isFile() && strtolower(".{$info->getExtension()}") === $ext) {
                $name = $info->getBasename($ext);
                $fmaps[] = $name;
                $this->app->config->load($info->getPathname(), $name);
            }
        });

        if ((in_array('route', $fmaps, true) || is_dir($appPath . 'route')) && method_exists($this->app->route, 'reload')) {
            $this->app->route->reload();
        }

        if (is_file($file = "{$appPath}provider{$ext}")) {
            $this->app->bind(include $file);
        }

        if (is_file($file = "{$appPath}event{$ext}")) {
            $this->app->loadEvent(include $file);
        }

        if (is_file($file = "{$appPath}middleware{$ext}")) {
            $this->app->middleware->import(include $file, 'app');
        }

        if (method_exists($this->app->lang, 'switchLangSet')) {
            $this->app->lang->switchLangSet($this->app->lang->getLangSet());
        }

        return true;
    }

    /**
     * 应用本地 app 调度结果.
     *
     * @param array<string, mixed> $local
     */
    private function applyLocal(array $local, bool $stripPrefix): bool
    {
        [$this->appPath, $this->appSpace] = [strval($local['path'] ?? ''), strval($local['space'] ?? '')];
        RequestContext::instance()->setEntryType(strval($local['entry'] ?? RequestContext::ENTRY_WEB));

        $prefix = trim(strval($local['matched_prefix'] ?? ''), '\/');
        if ($stripPrefix && $prefix !== '') {
            $this->app->request->setRoot('/' . $prefix);
            $this->app->request->setPathinfo(strval($local['pathinfo'] ?? ''));
        }

        return $this->setMultiApp(strval($local['code'] ?? ''), true);
    }

    /**
     * 从根路由预解析目标应用。
     *
     * @return null|array<string, mixed>
     */
    private function resolveGlobalRouteTarget(Request $request): ?array
    {
        $route = $this->app->route;
        if (!$route instanceof AdminRoute) {
            return null;
        }

        return $route->resolveTarget($request, $this->app->getRootPath() . 'route' . DIRECTORY_SEPARATOR);
    }
}
