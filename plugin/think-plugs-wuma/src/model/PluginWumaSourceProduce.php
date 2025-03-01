<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\model;

use plugin\wemall\model\PluginWemallGoodsItem;
use think\model\relation\HasOne;

/**
 * 生产批次数据模型
 *
 * @property int $deleted 删除状态(0未删1已删)
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 记录状态(0无效1有效)
 * @property string $addr_area 所在区域
 * @property string $addr_city 所在城市
 * @property string $addr_prov 所在省份
 * @property string $batch 生产批次
 * @property string $create_time 创建时间
 * @property string $ghash 产品编号
 * @property string $remark 批次备注
 * @property string $tcode 关联溯源模板
 * @property string $update_time 更新时间
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $bind_goods
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $goods
 * @property-read \plugin\wuma\model\PluginWumaSourceTemplate $bind_template
 * @property-read \plugin\wuma\model\PluginWumaSourceTemplate $template
 * @class PluginWumaSourceProduce
 * @package plugin\wuma\model
 */
class PluginWumaSourceProduce extends AbstractPrivate
{

    /**
     * 获取所有生产批次
     * @param mixed $map
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists($map = []): array
    {
        return static::mk()->with(['bindGoods', 'bindTemplate'])
            ->where($map)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 关联产品数据
     * @return HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash')->with('bindGoods');
    }

    /**
     * 绑定产品数据
     * @return HasOne
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gcode'  => 'gcode',
            'gname'  => 'gname',
            'gunit'  => 'gunit',
            'gspec'  => 'gspec',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 关联模板数据
     * @return \think\model\relation\HasOne
     */
    public function template(): HasOne
    {
        return $this->hasOne(PluginWumaSourceTemplate::class, 'code', 'tcode')->where(['deleted' => 0]);
    }

    /**
     * 绑定模板数据
     * @return HasOne
     */
    public function bindTemplate(): HasOne
    {
        return $this->template()->bind([
            'tname'    => 'name',
            'tstatus'  => 'status',
            'sdeleted' => 'deleted'
        ]);
    }
}