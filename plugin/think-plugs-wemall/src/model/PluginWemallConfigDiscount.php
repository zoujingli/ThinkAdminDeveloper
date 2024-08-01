<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\model;

use plugin\account\model\Abs;

/**
 * 用户优惠方案数据
 * @class PluginWemallConfigDiscount
 * @package plugin\wemall\model
 */
class PluginWemallConfigDiscount extends Abs
{

    /**
     * 获取折扣方案
     * @param boolean $allow
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function items(bool $allow = false): array
    {
        $query = self::mk()->where(['status' => 1, 'deleted' => 0]);
        $items = $query->order('sort desc,id desc')->field('id,name,items')->select()->toArray();
        if ($allow) array_unshift($items, ['id' => '0', 'name' => '无折扣']);
        return $items;
    }

    /**
     * 格式化等级规则
     * @param mixed $value
     * @return array
     */
    public function getItemsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function setItemsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }
}