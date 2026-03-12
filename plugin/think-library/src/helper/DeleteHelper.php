<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库: https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\helper;

use think\admin\Helper;
use think\admin\query\QueryFactory;
use think\db\BaseQuery;
use think\db\exception\DbException;
use think\Model;

/**
 * 通用删除管理器
 * @class DeleteHelper
 */
class DeleteHelper extends Helper
{
    /**
     * @param BaseQuery|Model|string $dbQuery
     * @param string $field
     * @param mixed $where
     * @throws DbException
     */
    public function init($dbQuery, string $field = '', $where = [])
    {
        $query = QueryFactory::build($dbQuery);
        $field = $field ?: ($query->getPk() ?: 'id');
        $value = $this->app->request->post($field);

        if (!empty($where)) {
            $query->where($where);
        }
        if (!isset($where[$field]) && is_string($value)) {
            $query->whereIn($field, str2arr($value));
        }

        if ($this->class->callback('_delete_filter', $query, $where) === false) {
            return false;
        }

        if (empty($query->getOptions()['where'] ?? [])) {
            $this->class->error('数据删除失败！');
        }

        if ($result = $query->delete() !== false) {
            $model = $query->getModel();
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
}
