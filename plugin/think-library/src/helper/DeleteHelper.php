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

namespace think\admin\helper;

use think\admin\Helper;
use think\admin\model\QueryFactory;
use think\db\BaseQuery;
use think\db\Query;
use think\Model;
use think\model\concern\SoftDelete;

/**
 * 通用删除管理器.
 * @class DeleteHelper
 */
class DeleteHelper extends Helper
{
    /**
     * @param array|mixed $where
     */
    public function init(BaseQuery|Model|string $dbQuery, string $field = '', mixed $where = [])
    {
        $query = QueryFactory::build($dbQuery);
        if (!$query instanceof Query) {
            throw new \InvalidArgumentException('DeleteHelper only supports relational Query instances.');
        }
        $field = $field ?: ($query->getPk() ?: 'id');
        $value = $this->app->request->post($field);

        if (!empty($where)) {
            $query->where($where);
        }
        if (!isset($where[$field]) && $value !== null && $value !== '') {
            $query->whereIn($field, is_array($value) ? $value : str2arr(strval($value)));
        }

        if ($this->class->callback('_delete_filter', $query, $where) === false) {
            return false;
        }

        if (empty($query->getOptions()['where'] ?? [])) {
            $this->class->error('数据删除失败！');
        }

        $model = $query->getModel();
        $result = $this->deleteRecords($query, $model);
        if ($result) {
            if ($model instanceof \think\admin\Model) {
                $model->onAdminDelete(strval($value));
            }
        }

        if ($this->class->callback('_delete_result', $result) === false) {
            return $result;
        }

        if ($result !== false) {
            $this->class->success('数据删除成功！', '');
        } else {
            $this->class->error('数据删除失败！');
        }
    }

    private function deleteRecords(Query $query, ?Model $model = null): bool
    {
        if ($model instanceof Model && $this->usesSoftDelete($model)) {
            $result = false;
            foreach ((clone $query)->select() as $item) {
                $result = $item->delete() || $result;
            }
            return $result;
        }

        return $query->delete() !== false;
    }

    private function usesSoftDelete(Model $model): bool
    {
        $traits = [];
        foreach ([get_class($model), ...class_parents($model)] as $class) {
            $traits = array_merge($traits, class_uses($class) ?: []);
        }

        return in_array(SoftDelete::class, array_values(array_unique($traits)), true)
            && $model->getOption('deleteTime', 'delete_time') !== false;
    }
}
