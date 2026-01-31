<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wuma\model;

use plugin\wemall\model\PluginWemallGoodsItem;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasOne;

/**
 * 生产批次数据模型.
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
 * @property PluginWemallGoodsItem $bind_goods
 * @property PluginWemallGoodsItem $goods
 * @property PluginWumaSourceTemplate $bind_template
 * @property PluginWumaSourceTemplate $template
 * @class PluginWumaSourceProduce
 */
class PluginWumaSourceProduce extends AbstractPrivate
{
    /**
     * 获取所有生产批次
     * @param mixed $map
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists($map = []): array
    {
        return static::mk()->with(['bindGoods', 'bindTemplate'])
            ->where($map)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 关联产品数据.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash')->with('bindGoods');
    }

    /**
     * 绑定产品数据.
     */
    public function bindGoods(): HasOne
    {
        return $this->goods()->bind([
            'gcode' => 'gcode',
            'gname' => 'gname',
            'gunit' => 'gunit',
            'gspec' => 'gspec',
            'gcover' => 'gcover',
        ]);
    }

    /**
     * 关联模板数据.
     */
    public function template(): HasOne
    {
        return $this->hasOne(PluginWumaSourceTemplate::class, 'code', 'tcode')->where(['deleted' => 0]);
    }

    /**
     * 绑定模板数据.
     */
    public function bindTemplate(): HasOne
    {
        return $this->template()->bind([
            'tname' => 'name',
            'tstatus' => 'status',
            'sdeleted' => 'deleted',
        ]);
    }
}
