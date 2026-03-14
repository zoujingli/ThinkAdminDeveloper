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

namespace think\admin\model;

use think\admin\Library;
use think\db\BaseQuery;
use think\db\Mongo;
use think\db\Query;
use think\Model;

/**
 * 查询对象标准化工厂。
 * @class QueryFactory
 */
class QueryFactory
{
    /**
     * 统一构造查询对象，并补齐模型与前置事件。
     */
    public static function build(BaseQuery|Model|string $query): BaseQuery|Mongo|Query
    {
        if (is_string($query)) {
            if (static::isSubquery($query)) {
                $query = Library::$sapp->db->table($query);
            } else {
                return static::triggerBeforeEvent(ModelFactory::build($query)->db());
            }
        }
        if ($query instanceof Model) {
            return static::triggerBeforeEvent($query->db());
        }
        if ($query instanceof BaseQuery && !$query->getModel()) {
            // 子查询不挂载模型，实体表查询则补齐运行时模型。
            if (!static::isSubquery($query->getTable())) {
                $name = $query->getConfig('name') ?: '';
                if (is_string($name) && strlen($name) > 0) {
                    $name = config("database.connections.{$name}") ? $name : '';
                }
                $query->model(ModelFactory::build($query->getName(), [], $name));
            }
        }
        return static::triggerBeforeEvent($query);
    }

    /**
     * 触发查询执行前事件。
     * @param BaseQuery|mixed|Model $query
     * @return BaseQuery|mixed|Model
     */
    private static function triggerBeforeEvent($query)
    {
        Library::$sapp->db->trigger('think_before_event', $query);
        return $query;
    }

    /**
     * 判断是否为子查询 SQL。
     */
    private static function isSubquery(string $sql): bool
    {
        return preg_match('/^\(?\s*select\s+/i', $sql) > 0;
    }
}
