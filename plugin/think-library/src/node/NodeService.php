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

namespace think\admin\node;

use think\admin\extend\filesystem\FileTools;
use think\admin\Library;
use think\admin\module\ModuleService;
use think\admin\runtime\AppService;
use think\admin\Service;

/**
 * 应用节点管理服务。
 * @class NodeService
 */
class NodeService extends Service
{
    /**
     * 获取默认应用命名空间。
     * @param string $suffix 后缀路径
     */
    public static function space(string $suffix = ''): string
    {
        $default = Library::$sapp->config->get('app.app_namespace') ?: 'app';
        return empty($suffix) ? $default : trim($default . '\\' . trim($suffix, '\/'), '\\');
    }

    /**
     * 驼峰转下划线节点规则。
     */
    public static function nameTolower(string $name): string
    {
        $dots = [];
        foreach (explode('.', strtr($name, '/', '.')) as $dot) {
            $dots[] = trim(preg_replace('/[A-Z]/', '_\0', $dot), '_');
        }
        return strtolower(join('.', $dots));
    }

    /**
     * 获取当前节点。
     * @param string $type app|module|controller|action
     */
    public static function getCurrent(string $type = ''): string
    {
        $appname = strtolower(Library::$sapp->http->getName());
        if (in_array($type, ['app', 'module'])) {
            return $appname;
        }
        $controller = static::nameTolower(Library::$sapp->request->controller());
        if ($type === 'controller') {
            return "{$appname}/{$controller}";
        }
        $method = strtolower(Library::$sapp->request->action());
        return "{$appname}/{$controller}/{$method}";
    }

    /**
     * 补全节点内容。
     */
    public static function fullNode(?string $node = ''): string
    {
        if (empty($node)) {
            return static::getCurrent();
        }
        switch (count($attrs = explode('/', $node))) {
            case 1:
                return static::getCurrent('controller') . '/' . strtolower($node);
            case 2:
                $suffix = static::nameTolower($attrs[0]) . '/' . $attrs[1];
                return static::getCurrent('module') . '/' . strtolower($suffix);
            default:
                $attrs[1] = static::nameTolower($attrs[1]);
                return strtolower(join('/', $attrs));
        }
    }

    /**
     * 获取全部控制器方法节点。
     * @param bool $force 强制更新
     */
    public static function getMethods(bool $force = false): array
    {
        $skey = 'think.admin.methods';
        if (empty($force)) {
            $data = sysvar($skey) ?: Library::$sapp->cache->get('SystemAuthNode', []);
            if (count($data) > 0) {
                return sysvar($skey, $data);
            }
        } else {
            $data = [];
        }
        $ignoreMethods = get_class_methods('\think\admin\Controller');
        $ignoreAppNames = Library::$sapp->config->get('app.rbac_ignore', []);
        foreach (AppService::all() as $appName => $app) {
            if (in_array($appName, $ignoreAppNames)) {
                continue;
            }
            if (empty($app['path']) || !is_dir($app['path'])) {
                continue;
            }
            foreach (FileTools::scan($app['path'], null, 'php') as $name) {
                if (preg_match('|^.*?controller/(.+)\.php$|i', strtr($name, '\\', '/'), $matches)) {
                    static::parseClass($appName, $app['space'], $matches[1], $ignoreMethods, $data);
                }
            }
        }
        if (function_exists('admin_node_filter')) {
            $data = call_user_func('admin_node_filter', $data);
        }
        Library::$sapp->cache->set('SystemAuthNode', $data);
        return sysvar($skey, $data);
    }

    /**
     * 扫描目录文件。
     * @param string $path 扫描目录
     * @param ?int $depth 扫描深度
     * @param ?string $ext 文件扩展名
     */
    public static function scanDirectory(string $path, ?int $depth = null, ?string $ext = null): array
    {
        return FileTools::scan($path, $depth, $ext);
    }

    /**
     * 获取本地模块列表。
     */
    public static function getModules(array $data = []): array
    {
        return ModuleService::getModules($data);
    }

    /**
     * 获取全部应用列表。
     */
    public static function getApps(array $data = []): array
    {
        return ModuleService::getApps($data);
    }

    /**
     * 解析类节点定义。
     * @param string $appName 应用名称
     * @param string $appSpace 应用命名空间
     * @param string $className 控制器路径
     * @param array $ignoreNode 忽略节点
     * @param array $data 输出节点数据
     */
    private static function parseClass(string $appName, string $appSpace, string $className, array $ignoreNode, array &$data): void
    {
        $classfull = strtr("{$appSpace}/controller/{$className}", '/', '\\');
        if (class_exists($classfull) && ($class = new \ReflectionClass($classfull))) {
            $prefix = strtolower(strtr("{$appName}/" . static::nameTolower($className), '\\', '/'));
            $data[$prefix] = static::parseComment($class->getDocComment() ?: '', $className);
            foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if (in_array($metname = $method->getName(), $ignoreNode)) {
                    continue;
                }
                $data[strtolower("{$prefix}/{$metname}")] = static::parseComment($method->getDocComment() ?: '', $metname);
            }
        }
    }

    /**
     * 解析节点注释。
     * @param string $comment 注释内容
     * @param string $default 默认标题
     */
    private static function parseComment(string $comment, string $default = ''): array
    {
        $text = strtr($comment, "\n", ' ');
        $title = preg_replace('/^\/\*\s*\*\s*\*\s*(.*?)\s*\*.*?$/', '$1', $text);
        if (in_array(substr($title, 0, 5), ['@auth', '@menu', '@logi'])) {
            $title = $default;
        }
        return [
            'title' => $title ?: $default,
            'isauth' => intval(preg_match('/@auth\s*true/i', $text)),
            'ismenu' => intval(preg_match('/@menu\s*true/i', $text)),
            'islogin' => intval(preg_match('/@login\s*true/i', $text)),
        ];
    }
}
