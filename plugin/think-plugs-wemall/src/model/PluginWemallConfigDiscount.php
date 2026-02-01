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

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 用户优惠方案数据.
 *
 * @property int $deleted 删除状态(1已删,0未删)
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 方案状态(0禁用,1使用)
 * @property string $create_time 创建时间
 * @property string $items 方案规则
 * @property string $name 方案名称
 * @property string $remark 方案描述
 * @property string $update_time 更新时间
 * @class PluginWemallConfigDiscount
 */
class PluginWemallConfigDiscount extends Abs
{
    /**
     * 获取折扣方案.
     * @return array[]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function items(bool $allow = false): array
    {
        $query = self::mk()->where(['status' => 1, 'deleted' => 0]);
        $items = $query->order('sort desc,id desc')->field('id,name,items')->select()->toArray();
        if ($allow) {
            array_unshift($items, ['id' => '0', 'name' => '无折扣']);
        }
        return $items;
    }

    /**
     * 格式化等级规则.
     * @param mixed $value
     */
    public function getItemsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    /**
     * @param mixed $value
     */
    public function setItemsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
}
