<?php

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

namespace plugin\system\service;

use plugin\system\model\SystemConfig;
use plugin\system\model\SystemData;
use plugin\system\model\SystemOplog;
use think\admin\Exception;
use think\admin\Library;
use think\admin\model\QueryFactory;
use think\admin\runtime\SystemContext;
use think\admin\Service;
use think\admin\service\FaviconBuilder;
use think\admin\service\NodeService;
use think\admin\Storage;
use think\db\Query;
use think\Model;

/**
 * 系统配置与运行辅助服务。
 * @class SystemService
 */
class SystemService extends Service
{
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
     * 生成全部静态路径。
     * @return string[]
     */
    public static function uris(string $path = ''): array
    {
        return static::uri($path, null);
    }

    /**
     * 设置配置数据。
     * @param string $name 配置名称
     * @param mixed $value 配置内容
     * @return int|string
     * @throws Exception
     */
    public static function set(string $name, $value = '')
    {
        [$type, $field] = static::parseRule($name);
        if (is_array($value)) {
            $count = 0;
            foreach ($value as $kk => $vv) {
                $count += static::set("{$field}.{$kk}", $vv);
            }
            return $count;
        }
        try {
            $map = ['type' => $type, 'name' => $field];
            SystemConfig::mk()->master()->where($map)->findOrEmpty()->save(array_merge($map, ['value' => $value]));
            sysvar('think.admin.config', []);
            Library::$sapp->cache->delete('SystemConfig');
            return 1;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 读取配置数据。
     * @return array|mixed|string
     * @throws Exception
     */
    public static function get(string $name = '', string $default = '')
    {
        try {
            if (empty($config = sysvar($keys = 'think.admin.config') ?: [])) {
                SystemConfig::mk()->cache('SystemConfig')->select()->map(function ($item) use (&$config) {
                    $config[$item['type']][$item['name']] = $item['value'];
                });
                sysvar($keys, $config);
            }
            [$type, $field, $outer] = static::parseRule($name);
            if (empty($name)) {
                return $config;
            }
            if (isset($config[$type])) {
                $group = $config[$type];
                if ($outer !== 'raw') {
                    foreach ($group as $kk => $vo) {
                        $group[$kk] = htmlspecialchars(strval($vo));
                    }
                }
                return $field ? ($group[$field] ?? $default) : $group;
            }
            return $default;
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 数据增量保存。
     * @param Model|Query|string $query 数据查询对象
     * @param array $data 需要保存的数据
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return bool|int
     * @throws Exception
     */
    public static function save($query, array &$data, string $key = 'id', $map = [])
    {
        try {
            $query = QueryFactory::build($query)->master()->strict(false);
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
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 批量更新保存数据。
     * @param Model|Query|string $query 数据查询对象
     * @param array $data 需要保存的数据
     * @param string $key 更新条件查询主键
     * @param mixed $map 额外更新查询条件
     * @return bool|int
     * @throws Exception
     */
    public static function update($query, array $data, string $key = 'id', $map = [])
    {
        try {
            $query = QueryFactory::build($query)->master()->where($map);
            if (empty($map[$key])) {
                $query->where([$key => $data[$key] ?? null]);
            }
            return (clone $query)->count() > 1 ? $query->strict(false)->update($data) : $query->findOrEmpty()->save($data);
        } catch (\Exception|\Throwable $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 获取数据库所有数据表。
     * @return array [table, total, count]
     */
    public static function getTables(): array
    {
        $tables = Library::$sapp->db->getTables();
        return [$tables, count($tables), 0];
    }

    /**
     * 复制并创建表结构。
     * @param string $from 来源表名
     * @param string $create 创建表名
     * @param array $tables 现有表集合
     * @param bool $copy 是否复制数据
     * @param mixed $where 复制条件
     * @throws Exception
     */
    public static function copyTableStruct(string $from, string $create, array $tables = [], bool $copy = false, $where = [])
    {
        try {
            if (empty($tables)) {
                [$tables] = static::getTables();
            }
            if (!in_array($from, $tables)) {
                throw new Exception("待复制的数据表 {$from} 不存在！");
            }
            if (!in_array($create, $tables)) {
                Library::$sapp->db->connect()->query("CREATE TABLE IF NOT EXISTS {$create} (LIKE {$from})");
                if ($copy) {
                    $sql1 = Library::$sapp->db->name($from)->where($where)->buildSql(false);
                    Library::$sapp->db->connect()->query("INSERT INTO {$create} {$sql1}");
                }
            }
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 保存系统数据。
     * @param string $name 数据名称
     * @param mixed $value 数据内容
     * @throws Exception
     */
    public static function setData(string $name, $value): bool
    {
        try {
            $data = ['name' => $name, 'value' => json_encode([$value], 64 | 256)];
            return SystemData::mk()->where(['name' => $name])->findOrEmpty()->save($data);
        } catch (\Exception $exception) {
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 读取系统数据。
     * @param string $name 数据名称
     * @param mixed $default 默认内容
     * @return mixed
     */
    public static function getData(string $name, $default = [])
    {
        try {
            $value = SystemData::mk()->where(['name' => $name])->value('value');
            if (is_null($value)) {
                return $default;
            }
            if (is_string($value) && strpos($value, '[') === 0) {
                return json_decode($value, true)[0];
            }
        } catch (\Exception $exception) {
            trace_file($exception);
            return $default;
        }
        try {
            return unserialize($value);
        } catch (\Exception $exception) {
            trace_file($exception);
        }
        try {
            $unit = 'i:\d+;|b:[01];|s:\d+:".*?";|O:\d+:".*?":\d+:\{';
            $preg = '/(?=^|' . $unit . ')s:(\d+):"(.*?)";(?=' . $unit . '|}+$)/';
            return unserialize(preg_replace_callback($preg, static function ($attr) {
                return sprintf('s:%d:"%s";', strlen($attr[2]), $attr[2]);
            }, $value));
        } catch (\Exception $exception) {
            trace_file($exception);
            return $default;
        }
    }

    /**
     * 写入系统日志。
     */
    public static function setOplog(string $action, string $content): bool
    {
        return SystemOplog::mk()->save(static::getOplog($action, $content)) !== false;
    }

    /**
     * 获取系统日志数组。
     */
    public static function getOplog(string $action, string $content): array
    {
        return [
            'node' => NodeService::getCurrent(),
            'action' => lang($action),
            'content' => lang($content),
            'geoip' => Library::$sapp->request->ip() ?: '127.0.0.1',
            'username' => strval(SystemContext::getUser('username', '-')) ?: '-',
            'create_time' => date('Y-m-d H:i:s'),
        ];
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
     * 设置网站 favicon。
     * @param ?string $icon 网站图标地址
     * @throws Exception
     */
    public static function setFavicon(?string $icon = null): bool
    {
        try {
            $icon = $icon ?: sysconf('base.site_icon|raw');
            if (!preg_match('#^https?://#i', $icon)) {
                throw new Exception(lang('无效的原文件地址！'));
            }
            [$file, $temporary] = static::resolveFaviconFile($icon);
            if (empty($file) || !is_file($file)) {
                return false;
            }
            try {
                $favicon = new FaviconBuilder($file, [48, 48]);
                return $favicon->saveIco(runpath('public/favicon.ico'));
            } finally {
                if ($temporary && is_file($file)) {
                    @unlink($file);
                }
            }
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            throw new Exception($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * 解析 favicon 源文件。
     * @return array{0:string,1:bool}
     */
    private static function resolveFaviconFile(string $icon): array
    {
        if ($file = static::resolveUploadFile($icon)) {
            return [$file, false];
        }
        $body = Storage::curlGet($icon);
        if ($body === '') {
            return ['', false];
        }
        $file = runpath('runtime/' . Storage::name($icon, 'tmp', 'favicon'));
        is_dir($dir = dirname($file)) || mkdir($dir, 0777, true);
        return file_put_contents($file, $body) === false ? ['', false] : [$file, true];
    }

    /**
     * 解析本地上传的图标文件路径。
     */
    private static function resolveUploadFile(string $icon): ?string
    {
        $path = parse_url($icon, PHP_URL_PATH);
        if (!is_string($path) || !preg_match('#/upload/(.+)$#i', rawurldecode($path), $matches)) {
            return null;
        }
        $name = ltrim(str_replace('\\', '/', $matches[1]), '/');
        if (preg_match('#(^|/)\.\.(/|$)#', $name)) {
            return null;
        }
        // upload 目录属于可写运行目录（Phar 环境在外部挂载）
        $file = runpath("public/upload/{$name}");
        return is_file($file) ? $file : null;
    }

    /**
     * 解析配置规则。
     * @param string $rule 配置名称
     */
    private static function parseRule(string $rule): array
    {
        $type = 'base';
        if (stripos($rule, '.') !== false) {
            [$type, $rule] = explode('.', $rule, 2);
        }
        [$field, $outer] = explode('|', "{$rule}|");
        return [$type, $field, strtolower($outer)];
    }
}
