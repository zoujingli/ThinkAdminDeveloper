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

use think\admin\Exception;
use think\admin\Library;
use think\admin\model\QueryFactory;
use think\db\Query;
use think\Model;

/**
 * Library 通用运行时工具。
 * 不依赖具体插件实现，供表单、查询、模板等基础设施复用。
 * @class RuntimeTools
 */
class RuntimeTools
{
    /**
     * 生成全部静态路径。
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
}
