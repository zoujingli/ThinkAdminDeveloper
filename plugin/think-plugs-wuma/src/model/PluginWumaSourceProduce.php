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