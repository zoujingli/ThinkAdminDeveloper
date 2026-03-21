<?php

// +----------------------------------------------------------------------
// | Library for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免费声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 仓库地址 ：https://gitee.com/zoujingli/ThinkLibrary
// | github 仓库地址 ：https://github.com/zoujingli/ThinkLibrary
// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 以下代码来自 topthink/think-multi-app，有部分修改以兼容 ThinkAdmin 的需求
// +----------------------------------------------------------------------

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

namespace think\admin\route;

use think\admin\Library;
use think\admin\service\AppService;
use think\admin\service\NodeService;
use think\helper\Str;
use think\route\Url as ThinkUrl;

/**
 * 多应用 URL 生成与解析.
 * @class Url
 */
class Url extends ThinkUrl
{
    /**
     * 将后台页面地址标准化为短链目标。
     */
    public static function normalizeWebTarget(string $url): string
    {
        if (
            preg_match('#^(?:https?://|@|\[)#', $url)
            || (strpos($url, '@') !== false && strpos($url, '\\') === false)
        ) {
            return $url;
        }

        $info = parse_url($url);
        if (!is_array($info)) {
            return $url;
        }

        $path = strval($info['path'] ?? '');
        if ($path === '' && $url !== '') {
            return $url;
        }

        $absolute = str_starts_with($path, '/');
        $segments = array_values(array_filter(explode('/', trim(str_replace('\\', '/', $path), '/')), 'strlen'));
        if (!$absolute && count($segments) < 3) {
            $map = [
                Library::$sapp->http->getName(),
                Library::$sapp->request->controller(),
                Library::$sapp->request->action(true),
            ];
            while (count($segments) < 3) {
                array_unshift($segments, $map[2 - count($segments)] ?? 'index');
            }
        }

        if (isset($segments[0])) {
            $segments[0] = Str::lower($segments[0]);
        }
        if (isset($segments[1])) {
            $segments[1] = Str::snake($segments[1]);
        }

        $segments = self::shrinkWebSegments($segments, false);
        $target = join('/', $segments);
        $target = $target === '' ? '/' : '/' . ltrim($target, '/');

        if (isset($info['query']) && $info['query'] !== '') {
            $target .= '?' . $info['query'];
        }
        if (isset($info['fragment']) && $info['fragment'] !== '') {
            $target .= '#' . $info['fragment'];
        }

        return $target;
    }

    /**
     * Build URL.
     */
    public function build(): string
    {
        $url = $this->url;
        $vars = $this->vars;
        $domain = $this->domain;
        $suffix = $this->suffix;
        $request = $this->app->request;
        if (strpos($url, '[') === 0 && $pos = strpos($url, ']')) {
            // [name] 表示使用路由命名标识生成URL
            $name = substr($url, 1, $pos - 1);
            $url = 'name' . substr($url, $pos + 1);
        }
        if (strpos($url, '://') === false && strpos($url, '/') !== 0) {
            $info = parse_url($url);
            $url = !empty($info['path']) ? $info['path'] : '';
            if (isset($info['fragment'])) {
                // 解析锚点
                $anchor = $info['fragment'];
                if (strpos($anchor, '?') !== false) {
                    // 解析参数
                    [$anchor, $info['query']] = explode('?', $anchor, 2);
                }
                if (strpos($anchor, '@') !== false) {
                    // 解析域名
                    [$anchor, $domain] = explode('@', $anchor, 2);
                }
            } elseif (strpos($url, '@') && strpos($url, '\\') === false) {
                // 解析域名
                [$url, $domain] = explode('@', $url, 2);
            }
        }
        if ($url) {
            $checkDomain = $domain && is_string($domain) ? $domain : null;
            $checkName = $name ?? $url . (isset($info['query']) ? '?' . $info['query'] : '');
            $rule = $this->route->getName($checkName, $checkDomain);
            if (empty($rule) && isset($info['query'])) {
                $rule = $this->route->getName($url, $checkDomain);
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
                unset($info['query']);
            }
        }
        if (!empty($rule) && $match = $this->getRuleUrl($rule, $vars, $domain)) {
            $url = $match[0];
            if ($domain && !empty($match[1])) {
                $domain = $match[1];
            }
            if (!is_null($match[2])) {
                $suffix = $match[2];
            }
            if (!$this->app->http->isBind()) {
                $url = $this->app->http->getName() . '/' . $url;
            }
        } elseif (!empty($rule) && isset($name)) {
            throw new \InvalidArgumentException('route name not exists:' . $name);
        } else {
            // 检测URL绑定
            $bind = $this->route->getDomainBind($domain && is_string($domain) ? $domain : null);
            if ($bind && strpos($url, $bind) === 0) {
                $url = substr($url, strlen($bind) + 1);
            }
            // 路由标识不存在 直接解析
            $url = $this->parseUrl($url, $domain);
            if (isset($info['query'])) {
                // 解析地址里面参数 合并到vars
                parse_str($info['query'], $params);
                $vars = array_merge($params, $vars);
            }
        }
        // 还原 URL 分隔符
        $file = $request->baseFile();
        $depr = $this->route->config('pathinfo_depr');
        [$uri, $url] = [$request->url(), str_replace('/', $depr, $url)];
        if ($file && strpos($uri, $file) !== 0) {
            $file = str_replace('\\', '/', dirname($file));
            // 内置服务器常见入口是 project-root + public/router.php -> public/index.php。
            // 这时真实业务路由位于站点根路径，不能再额外拼接 /public 前缀。
            if (basename($file) === 'public') {
                $file = str_replace('\\', '/', dirname($file));
            }
            $file = in_array($file, ['.', '/', '\\'], true) ? '' : $file;
        }
        /*
         * 插件优先模式下，公开访问路径始终以 URL 前缀区分应用。
         * 这里不再沿用旧多应用模式的“去掉当前应用前缀”或“强制回落 index.php”逻辑，
         * 否则会导致插件绝对链接丢失前缀，或者在 Worker 环境生成不可访问的 /index.php/... 地址。
         */
        $path = self::normalizeWebPath(ltrim($url, '/'));
        $url = rtrim($file, '/') . '/' . ltrim($path, '/');
        // URL后缀
        if (substr($url, -1) == '/' || $url == '') {
            $suffix = '';
        } else {
            $suffix = $this->parseSuffix($suffix);
        }
        // 锚点
        $anchor = !empty($anchor) ? '#' . $anchor : '';
        // 参数组装
        if (!empty($vars)) {
            // 添加参数
            if ($this->route->config('url_common_param')) {
                $vars = http_build_query($vars);
                $url .= $suffix . '?' . $vars . $anchor;
            } else {
                foreach ($vars as $var => $val) {
                    if ('' !== ($val = (string)$val)) {
                        $url .= $depr . $var . $depr . urlencode($val);
                    }
                }
                $url .= $suffix . $anchor;
            }
        } else {
            $url .= $suffix . $anchor;
        }
        // 检测域名
        $domain = $this->parseDomain($url, $domain);
        // URL 组装
        return $domain . rtrim($this->root, '/') . '/' . ltrim($url, '/');
    }

    /**
     * 直接解析 URL 地址
     * @param string $url URL
     * @param bool|string $domain Domain
     */
    protected function parseUrl(string $url, &$domain): string
    {
        $request = $this->app->request;
        if (strpos($url, '/') === 0) {
            $url = substr($url, 1);
        } elseif (strpos($url, '\\') !== false) {
            $url = ltrim(str_replace('\\', '/', $url), '/');
        } elseif (strpos($url, '@') === 0) {
            $url = substr($url, 1);
        } else {
            $attrs = str2arr($url, '/');
            $action = empty($attrs) ? $request->action() : array_pop($attrs);
            $contrl = empty($attrs) ? $request->controller() : array_pop($attrs);
            $module = empty($attrs) ? $this->app->http->getName() : array_pop($attrs);
            // 拼装新的链接地址
            $url = NodeService::nameTolower($contrl) . '/' . $action;
            if ($plugin = AppService::resolvePlugin($module)) {
                $prefix = AppService::pluginPrefix($plugin['code']);
                $url = ($prefix ?: $plugin['code']) . '/' . $url;
            } elseif ($module !== AppService::defaultAppCode()) {
                $url = $module . '/' . $url;
            }
        }
        return $url;
    }

    /**
     * 标准页面路径短链压缩。
     */
    private static function normalizeWebPath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');
        if ($path === '') {
            return '';
        }

        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        if ($segments === []) {
            return '';
        }

        return join('/', self::shrinkWebSegments($segments, true));
    }

    /**
     * 判断是否属于标准应用/插件页面路径。
     *
     * @param array<int, string> $segments
     */
    private static function supportsShortWebPath(array $segments): bool
    {
        $first = Str::lower(strval($segments[0] ?? ''));
        if ($first === '') {
            return false;
        }

        if ($first === Str::lower(AppService::defaultAppCode() ?: 'index')) {
            return true;
        }

        if (AppService::localApp($first) !== null) {
            return true;
        }

        if (AppService::resolvePluginPrefix($first) !== null) {
            return true;
        }

        return AppService::resolvePlugin($first) !== null;
    }

    /**
     * 按默认应用/控制器/方法收缩页面路径。
     *
     * @param array<int, string> $segments
     * @return array<int, string>
     */
    private static function shrinkWebSegments(array $segments, bool $strict): array
    {
        if ($segments === []) {
            return [];
        }

        $apiPrefix = trim(AppService::pluginApiPrefix(), '/');
        if ($apiPrefix !== '' && strcasecmp($segments[0], $apiPrefix) === 0) {
            return $segments;
        }

        if ($strict && !self::supportsShortWebPath($segments)) {
            return $segments;
        }

        $defaultAction = Str::lower(strval(Library::$sapp->route->config('default_action') ?: 'index'));
        $defaultController = Str::snake(strval(Library::$sapp->route->config('default_controller') ?: 'index'));
        $defaultApp = Str::lower(AppService::defaultAppCode() ?: 'index');

        if (count($segments) >= 2 && Str::lower(end($segments) ?: '') === $defaultAction) {
            array_pop($segments);
        }

        if (count($segments) === 2 && Str::snake($segments[1]) === $defaultController) {
            array_pop($segments);
        }

        if (count($segments) === 1 && Str::lower($segments[0]) === $defaultApp) {
            array_pop($segments);
        }

        return $segments;
    }
}
