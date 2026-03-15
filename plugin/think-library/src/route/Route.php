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

namespace think\admin\route;

use Closure;
use think\admin\runtime\RequestContext;
use think\admin\service\AppService;
use think\admin\service\PluginService;
use think\exception\RouteNotFoundException;
use think\Request;
use think\Route as ThinkRoute;
use think\route\RuleGroup;
use think\route\RuleItem;

/**
 * 自定义路由对象
 * @class Route
 */
class Route extends ThinkRoute
{
    /**
     * 根路由绑定本地应用的 option 键。
     */
    public const OPTION_APP = 'ta_app';

    /**
     * 根路由绑定插件应用的 option 键。
     */
    public const OPTION_PLUGIN = 'ta_plugin';

    /**
     * 根路由绑定插件入口类型的 option 键。
     */
    public const OPTION_ENTRY = 'ta_entry';

    /**
     * 已加载路由目录缓存。
     * @var array<string, bool>
     */
    private array $loadedPaths = [];

    /**
     * 重载路由配置.
     * @return $this
     */
    public function reload(): Route
    {
        $this->config = array_merge($this->config, $this->app->config->get('route'));
        return $this;
    }

    /**
     * 注册绑定到本地 app 的根路由。
     * @param mixed $route
     */
    public function bindApp(string $rule, $route, string $app, string $method = '*'): RuleItem
    {
        return $this->bindTarget($rule, $route, [self::OPTION_APP => $app], $method);
    }

    /**
     * 注册绑定到插件的根路由。
     * @param mixed $route
     */
    public function bindPlugin(
        string $rule,
        $route,
        string $plugin,
        string $entry = RequestContext::ENTRY_WEB,
        string $method = '*'
    ): RuleItem {
        return $this->bindTarget($rule, $route, [
            self::OPTION_PLUGIN => $plugin,
            self::OPTION_ENTRY => $entry,
        ], $method);
    }

    /**
     * 注册带目标声明的根路由。
     * 这里的 $route 必须是目标应用内部的相对控制器地址，而不是带模块前缀的旧写法。
     *
     * @param string $rule 路由规则
     * @param mixed $route 路由地址
     * @param array<string, mixed> $target 目标声明
     * @param string $method 请求方法
     */
    public function bindTarget(string $rule, $route, array $target, string $method = '*'): RuleItem
    {
        $item = $this->rule($rule, $route, $method);
        $option = $this->normalizeTargetOptions($target);
        return empty($option) ? $item : $item->option($option);
    }

    /**
     * 注册绑定到本地 app 的根路由分组。
     * @param null|mixed $route
     */
    public function appGroup(string $app, \Closure|string $name, $route = null): RuleGroup
    {
        return $this->groupTarget([self::OPTION_APP => $app], $name, $route);
    }

    /**
     * 注册绑定到插件的根路由分组。
     * 当第二个参数是 Closure 时，第三个参数可直接传 api/web 入口类型。
     * @param null|mixed $route
     */
    public function pluginGroup(
        string $plugin,
        \Closure|string $name,
        $route = null,
        string $entry = RequestContext::ENTRY_WEB
    ): RuleGroup {
        if ($name instanceof \Closure && is_string($route) && in_array($route, [RequestContext::ENTRY_WEB, RequestContext::ENTRY_API], true)) {
            $entry = $route;
            $route = null;
        }

        return $this->groupTarget([
            self::OPTION_PLUGIN => $plugin,
            self::OPTION_ENTRY => $entry,
        ], $name, $route);
    }

    /**
     * 注册带目标声明的根路由分组。
     *
     * @param array<string, mixed> $target
     * @param null|mixed $route
     */
    public function groupTarget(array $target, \Closure|string $name, $route = null): RuleGroup
    {
        $group = $this->group($name, $route);
        $option = $this->normalizeTargetOptions($target);
        return empty($option) ? $group : $group->option($option);
    }

    /**
     * 预解析根路由目标，在实际路由调度前先确定要绑定的应用。
     *
     * @return null|array<string, mixed>
     */
    public function resolveTarget(Request $request, string $routePath): ?array
    {
        if (!$this->app->config->get('app.with_route', true) || !$this->loadPath($routePath)) {
            return null;
        }

        $this->request = $request;
        $this->host = $request->host(true);

        try {
            $dispatch = $this->check(
                $this->normalizePathinfo($request),
                boolval($this->config['route_complete_match'] ?? true)
            );
        } catch (RouteNotFoundException $exception) {
            return null;
        }

        if (!$dispatch) {
            return null;
        }

        return $this->extractDispatchTarget($dispatch, $request->pathinfo());
    }

    /**
     * 按目录加载路由文件，并做一次进程级缓存，避免重复 include。
     */
    private function loadPath(string $routePath): bool
    {
        $routePath = rtrim($routePath, '\/') . DIRECTORY_SEPARATOR;
        if (array_key_exists($routePath, $this->loadedPaths)) {
            return $this->loadedPaths[$routePath];
        }

        $files = is_dir($routePath) ? (glob($routePath . '*.php') ?: []) : [];
        if (empty($files)) {
            return $this->loadedPaths[$routePath] = false;
        }

        foreach ($files as $file) {
            include $file;
        }

        return $this->loadedPaths[$routePath] = true;
    }

    /**
     * 规范化当前请求 pathinfo，使其与框架路由检测逻辑一致。
     */
    private function normalizePathinfo(Request $request): string
    {
        $pathinfo = trim($request->pathinfo(), '\/');
        $suffix = $this->config['url_html_suffix'] ?? 'html';

        if ($suffix === false) {
            $path = $pathinfo;
        } elseif (!empty($suffix)) {
            $path = preg_replace('/\.(' . preg_quote(ltrim(strval($suffix), '.'), '/') . ')$/i', '', $pathinfo) ?: $pathinfo;
        } else {
            $ext = $request->ext();
            $path = $ext === '' ? $pathinfo : (preg_replace('/\.' . preg_quote($ext, '/') . '$/i', '', $pathinfo) ?: $pathinfo);
        }

        return str_replace(strval($this->config['pathinfo_depr'] ?? '/'), '|', $path);
    }

    /**
     * 从命中的根路由中提取目标应用声明。
     *
     * @return null|array<string, mixed>
     */
    private function extractDispatchTarget(object $dispatch, string $pathinfo): ?array
    {
        $option = $this->dispatchOptions($dispatch);
        $target = trim($this->dispatchTarget($dispatch), '\/');

        $pluginCode = trim(strval($option[self::OPTION_PLUGIN] ?? $option['plugin'] ?? ''));
        if ($pluginCode !== '' && ($plugin = PluginService::resolve($pluginCode))) {
            $plugin['type'] = 'plugin';
            $plugin['entry'] = $this->detectPluginEntry($dispatch, $option, $target);
            $plugin['matched_prefix'] = '';
            $plugin['pathinfo'] = $pathinfo;
            return $plugin;
        }

        $appCode = trim(strval($option[self::OPTION_APP] ?? $option['app'] ?? $option['module'] ?? ''));
        if ($appCode !== '' && ($local = AppService::localApp($appCode))) {
            $local['type'] = 'local';
            $local['entry'] = RequestContext::ENTRY_WEB;
            $local['matched_prefix'] = '';
            $local['pathinfo'] = $pathinfo;
            return $local;
        }

        // 兼容旧三段式全局路由：system/login/index、index/demo/index。
        // 新代码仍应优先使用 bindApp/bindPlugin 显式声明目标，避免把目标选择耦合到路由字符串首段。
        if ($target !== '' && count($parts = array_values(array_filter(explode('/', $target), 'strlen'))) >= 3) {
            $code = trim(strval($parts[0]));
            $inner = join('/', array_slice($parts, 1));

            if ($code !== '' && ($plugin = PluginService::resolve($code))) {
                $plugin['type'] = 'plugin';
                $plugin['entry'] = $this->detectPluginEntry($dispatch, $option, $inner);
                $plugin['matched_prefix'] = '';
                $plugin['pathinfo'] = $pathinfo;
                return $plugin;
            }

            if ($code !== '' && ($local = AppService::localApp($code))) {
                $local['type'] = 'local';
                $local['entry'] = RequestContext::ENTRY_WEB;
                $local['matched_prefix'] = '';
                $local['pathinfo'] = $pathinfo;
                return $local;
            }
        }

        return null;
    }

    /**
     * 读取命中路由的 option 参数。
     *
     * @return array<string, mixed>
     */
    private function dispatchOptions(object $dispatch): array
    {
        return (array)\Closure::bind(function (): array {
            if (isset($this->rule) && method_exists($this->rule, 'getOption')) {
                return (array)$this->rule->getOption();
            }
            return $this->option ?? [];
        }, $dispatch, get_class($dispatch))();
    }

    /**
     * 推断插件根路由绑定的入口类型。
     *
     * @param array<string, mixed> $option
     */
    private function detectPluginEntry(object $dispatch, array $option, string $target = ''): string
    {
        $entry = trim(strval($option[self::OPTION_ENTRY] ?? $option['entry'] ?? ''));
        if (in_array($entry, [RequestContext::ENTRY_API, RequestContext::ENTRY_WEB], true)) {
            return $entry;
        }

        return preg_match('#^api([/.]|$)#i', trim($target ?: $this->dispatchTarget($dispatch), '\/'))
            ? RequestContext::ENTRY_API
            : RequestContext::ENTRY_WEB;
    }

    /**
     * 读取命中路由的最终调度目标。
     */
    private function dispatchTarget(object $dispatch): string
    {
        $target = method_exists($dispatch, 'getDispatch') ? $dispatch->getDispatch() : '';
        return is_array($target) ? join('/', array_map('strval', $target)) : strval($target);
    }

    /**
     * 标准化根路由目标声明。
     *
     * @param array<string, mixed> $target
     * @return array<string, string>
     */
    private function normalizeTargetOptions(array $target): array
    {
        $option = [];

        $app = trim(strval($target[self::OPTION_APP] ?? ''));
        if ($app !== '') {
            $option[self::OPTION_APP] = $app;
        }

        $plugin = trim(strval($target[self::OPTION_PLUGIN] ?? ''));
        if ($plugin !== '') {
            $option[self::OPTION_PLUGIN] = $plugin;
        }

        $entry = trim(strval($target[self::OPTION_ENTRY] ?? ''));
        if (in_array($entry, [RequestContext::ENTRY_WEB, RequestContext::ENTRY_API], true)) {
            $option[self::OPTION_ENTRY] = $entry;
        }

        return $option;
    }
}
