<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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
 * Class plugin\wuma\model\PluginWumaSalesOrder
 *
 * @property int $auid 经销商编号
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $mode 操作方式(1扫码,2虚拟)
 * @property int $num_count 累计已经出库
 * @property int $num_need 累计出库数量
 * @property int $status 记录状态(0无效,1有效,2完成)
 * @property int $vir_count 虚拟库使用
 * @property int $vir_need 虚拟库统计
 * @property int $xuid 来源经销商
 * @property string $code 操作单单号
 * @property string $create_time 创建时间
 * @property string $ghash 商品哈唏
 * @property string $update_time 更新时间
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $bind_goods
 * @property-read \plugin\wemall\model\PluginWemallGoodsItem $goods
 * @property-read \plugin\wuma\model\PluginWumaSalesUser $agent
 * @property-read \plugin\wuma\model\PluginWumaSalesUser $fromer
 */
class PluginWumaSalesOrder extends AbstractPrivate
{
    /**
     * 关联代理数据
     * @return \think\model\relation\HasOne
     */
    public function agent(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUser::class, 'id', 'auid');
    }

    /**
     * 关联代理数据
     * @return \think\model\relation\HasOne
     */
    public function fromer(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUser::class, 'id', 'xuid');
    }

    /**
     * 关联商品数据
     * @return \think\model\relation\HasOne
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash')->with('bindGoods');
    }

    /**
     * 绑定商品数据
     * @return \think\model\relation\HasOne
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gunit'    => 'gunit',
            'gcode'    => "gcode",
            'gname'    => 'gname',
            'gspec'    => 'gspec',
            'gcover'   => 'gcover',
            'gstatus'  => 'gstatus',
            'gdeleted' => 'gdeleted',
        ]);
    }
}