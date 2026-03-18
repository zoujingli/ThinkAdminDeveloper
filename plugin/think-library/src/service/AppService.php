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

use think\admin\Library;

/**
 * 应用注册服务
 * @class AppService
 */
class AppService extends Service
{
    /**
     * 应用缓存键名.
     */
    private const CACHE_APPS = 'think.admin.apps';

    /**
     * 本地应用缓存键名.
     */
    private const CACHE_LOCALS = 'think.admin.apps.locals';

    /**
     * app 根目录下的共享目录，不参与本地多应用扫描.
     */
    private const IGNORE_LOCAL_APPS = ['common', 'config', 'controller', 'lang', 'middleware', 'model', 'route', 'view'];

    /**
     * 清理应用缓存.
     */
    public static function clear(): void
    {
        sysvar(self::CACHE_APPS, false);
        sysvar(self::CACHE_LOCALS, false);
        PluginService::clear();
    }

    /**
     * 判断应用是否存在.
     */
    public static function exists(string $code, bool $force = false): bool
    {
        return isset(self::all($force)[$code]);
    }

    /**
     * 获取全部应用定义.
     *
     * @param bool $force 强制刷新
     * @return array<string, array<string, string>>
     */
    public static function all(bool $force = false): array
    {
        if (!$force && is_array($apps = sysvar(self::CACHE_APPS))) {
            return $apps;
        }

        $apps = array_merge(self::local($force), self::plugins($force));
        ksort($apps);
        return sysvar(self::CACHE_APPS, $apps);
    }

    /**
     * 获取本地 app/* 应用定义.
     *
     * @return array<string, array<string, string>>
     */
    public static function local(bool $force = false): array
    {
        if (!$force && is_array($apps = sysvar(self::CACHE_LOCALS))) {
            return $apps;
        }

        $apps = self::discoverLocalApps();
        ksort($apps);
        return sysvar(self::CACHE_LOCALS, $apps);
    }

    /**
     * 获取插件应用定义.
     *
     * @return array<string, array<string, string>>
     */
    public static function plugins(bool $force = false): array
    {
        return PluginService::all(false, $force);
    }

    /**
     * 获取全部应用编号.
     *
     * @return string[]
     */
    public static function codes(bool $force = false): array
    {
        return array_keys(self::all($force));
    }

    /**
     * 获取默认本地应用编号.
     */
    public static function singleCode(): string
    {
        $apps = self::local();
        $code = strval(Library::$sapp->config->get('route.default_app') ?: Library::$sapp->config->get('app.single_app') ?: '');
        if ($code !== '' && !in_array($code, self::IGNORE_LOCAL_APPS, true) && isset($apps[$code])) {
            return $code;
        }
        if (isset($apps['index'])) {
            return 'index';
        }
        return strval(array_key_first($apps) ?: 'index');
    }

    /**
     * 获取指定应用定义.
     *
     * @param ?string $code 应用编号
     */
    public static function get(?string $code = null, bool $force = false): ?array
    {
        $apps = self::all($force);
        return is_null($code) ? $apps : ($apps[$code] ?? null);
    }

    /**
     * 按首段路径命中本地应用.
     *
     * @return null|array<string, mixed>
     */
    public static function matchPath(string $pathinfo, bool $force = false): ?array
    {
        $pathinfo = trim($pathinfo, '\/');
        if ($pathinfo === '') {
            return null;
        }

        [$prefix, $suffix] = array_pad(explode('/', $pathinfo, 2), 2, '');
        if (strpos($prefix, '.')) {
            $prefix = strstr($prefix, '.', true) ?: $prefix;
        }

        if ($prefix === '' || !($app = self::localApp($prefix, $force))) {
            return null;
        }

        $app['matched_prefix'] = $prefix;
        $app['pathinfo'] = $suffix;
        return $app;
    }

    /**
     * 获取指定本地应用定义.
     */
    public static function localApp(?string $code = null, bool $force = false): ?array
    {
        $apps = self::local($force);
        return is_null($code) ? null : ($apps[$code] ?? null);
    }

    /**
     * 扫描 app/* 本地应用目录.
     *
     * @return array<string, array<string, string>>
     */
    private static function discoverLocalApps(): array
    {
        $apps = [];
        $basePath = rtrim(Library::$sapp->getBasePath(), '\/') . DIRECTORY_SEPARATOR;
        foreach (scandir($basePath) ?: [] as $code) {
            if ($code === '.' || $code === '..' || in_array($code, self::IGNORE_LOCAL_APPS, true)) {
                continue;
            }
            if (!preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $code)) {
                continue;
            }

            $path = $basePath . $code . DIRECTORY_SEPARATOR;
            if (!is_dir($path) || !self::isLocalAppPath($path)) {
                continue;
            }

            $apps[$code] = self::normalize($code, [
                'type' => 'local',
                'name' => ucfirst($code),
                'path' => $path,
                'space' => NodeService::space($code),
            ]);
        }

        return $apps;
    }

    /**
     * 判断是否为本地应用目录.
     */
    private static function isLocalAppPath(string $path): bool
    {
        foreach (['controller', 'route', 'view', 'config'] as $name) {
            if (is_dir($path . $name)) {
                return true;
            }
        }

        return is_file($path . 'Service.php');
    }

    /**
     * 标准化应用定义.
     *
     * @param string $code 应用编号
     * @param array<string, mixed> $app 应用配置
     * @return array<string, string>
     */
    private static function normalize(string $code, array $app): array
    {
        $path = $app['path'] ?? '';
        $path = $path === '' ? '' : rtrim((string)$path, '\/') . DIRECTORY_SEPARATOR;

        return [
            'code' => $code,
            'type' => $app['type'] ?? 'local',
            'name' => $app['name'] ?? ucfirst($code),
            'path' => $path,
            'alias' => $app['alias'] ?? '',
            'space' => $app['space'] ?? NodeService::space($code),
            'package' => $app['package'] ?? '',
            'service' => $app['service'] ?? '',
        ];
    }

    /**
     * 生成全部静态路径。
     * @param string $path 后缀路径
     * @return string[]
     */
    public static function uris(string $path = ''): array
    {
        return static::uri($path, null);
    }

    /**
     * 生成静态路径链接。
     * @param string $path 后缀路径
     * @param ?string $type 路径类型
     * @param mixed $default 默认数据
     * @return array|string
     */
    public static function uri(string $path = '', ?string $type = '__ROOT__', $default = '')
    {
        $plugin = Library::$sapp->http->getName();
        if (strlen($path)) {
            $path = '/' . ltrim($path, '/');
        }
        $prefix = rtrim(dirname(Library::$sapp->request->basefile()), '\/');
        $data = [
            '__APP__' => rtrim(url('@')->build(), '\/') . $path,
            '__ROOT__' => $prefix . $path,
            '__PLUG__' => "{$prefix}/static/extra/{$plugin}{$path}",
            '__FULL__' => Library::$sapp->request->domain() . $prefix . $path,
        ];
        return is_null($type) ? $data : ($data[$type] ?? $default);
    }

    /**
     * 打印调试数据到文件。
     * @param mixed $data 输出的数据
     * @param bool $new 强制替换文件
     * @param null|string $file 文件名称
     * @return false|int
     */
    public static function putDebug($data, bool $new = false, ?string $file = null)
    {
        ob_start();
        var_dump($data);
        $output = preg_replace('/]=>\n(\s+)/m', '] => ', ob_get_clean());
        if (is_null($file)) {
            $file = runpath('runtime/' . date('Ymd') . '.log');
        } elseif (!preg_match('#[/\\\]+#', $file)) {
            $file = runpath("runtime/{$file}.log");
        }
        is_dir($dir = dirname($file)) or mkdir($dir, 0777, true);
        return $new ? file_put_contents($file, $output) : file_put_contents($file, $output, FILE_APPEND);
    }

    /**
     * 批量更新保存数据。
     * @param \think\Model|\think\db\Query|string $query 数据查询对象
     * @param array $data 需要保存的数据
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return bool|int
     * @throws \think\admin\Exception
     */
    public static function update($query, array $data, string $key = 'id', $map = [])
    {
        try {
            $query = \think\admin\model\QueryFactory::build($query)->master()->where($map);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            return (clone $query)->count() > 1 ? $query->strict(false)->update($data) : $query->findOrEmpty()->save($data);
        } catch (\Exception|\Throwable $exception) {
            throw new \think\admin\Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 数据增量保存。
     * @param \think\Model|\think\db\Query|string $query 数据查询对象
     * @param array $data 需要保存的数据
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return bool|int
     * @throws \think\admin\Exception
     */
    public static function save($query, array &$data, string $key = 'id', $map = [])
    {
        try {
            $query = \think\admin\model\QueryFactory::build($query)->master()->strict(false);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            $model = $query->where($map)->findOrEmpty();
            $action = $model->isExists() ? 'onAdminUpdate' : 'onAdminInsert';
            if ($model->save($data) === false) {
                return false;
            }
            if ($model instanceof \think\admin\Model) {
                $model->{$action}(strval($model->getAttr($key)));
            }
            $data = $model->toArray();
            return $model[$key] ?? true;
        } catch (\Exception $exception) {
            throw new \think\admin\Exception($exception->getMessage(), $exception->getCode());
        }
    }
}
