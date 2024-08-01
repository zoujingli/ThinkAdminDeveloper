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
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 商城商品数据数据
 * @class PluginWemallGoods
 * @package plugin\wemall\model
 */
class PluginWemallGoods extends Abs
{
    /**
     * 日志名称
     * @var string
     */
    protected $oplogName = '商品';

    /**
     * 日志类型
     * @var string
     */
    protected $oplogType = '分销商城管理';

    /**
     * 关联产品规格
     * @return \think\model\relation\HasMany
     */
    public function items(): HasMany
    {
        return static::mk()
            ->hasMany(PluginWemallGoodsItem::class, 'gcode', 'code')
            ->withoutField('id,status,create_time,update_time')
            ->where(['status' => 1]);
    }

    /**
     * 关联商品评论数据
     * @return \think\model\relation\HasMany
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PluginWemallUserActionComment::class, 'gcode', 'code')->with('bindUser');
    }

    /**
     * 关联商品折扣方案
     * @return \think\model\relation\HasOne
     */
    public function discount(): HasOne
    {
        return $this->hasOne(PluginWemallConfigDiscount::class, 'id', 'discount_id')->where(['status' => 1, 'deleted' => 0])->field('id,name,items');
    }

    /**
     * 关联产品列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists(): array
    {
        $model = static::mk()->with('items')->withoutField('specs');
        return $model->order('sort desc,id desc')->where(['deleted' => 0])->select()->toArray();
    }

    /**
     * 标签处理
     * @param mixed $value
     * @return array
     */
    public function getMarksAttr($value): array
    {
        $ckey = 'PluginWemallGoodsMarkItems';
        $items = sysvar($ckey) ?: sysvar($ckey, PluginWemallGoodsMark::items());
        return str2arr(is_array($value) ? arr2str($value) : $value, ',', $items);
    }

    /**
     * 处理商品分类数据
     * @param mixed $value
     * @return array
     */
    public function getCatesAttr($value): array
    {
        $ckey = 'PluginWemallGoodsCateItem';
        $cates = sysvar($ckey) ?: sysvar($ckey, PluginWemallGoodsCate::items(true));
        $cateids = is_string($value) ? str2arr($value) : (array)$value;
        foreach ($cates as $cate) if (in_array($cate['id'], $cateids)) return $cate;
        return [];
    }

    /**
     * 设置轮播图片
     * @param mixed $value
     * @return string
     */
    public function setSliderAttr($value): string
    {
        return is_string($value) ? $value : (is_array($value) ? arr2str($value) : '');
    }

    /**
     * 获取轮播图片
     * @param mixed $value
     * @return array
     */
    public function getSliderAttr($value): array
    {
        return is_string($value) ? str2arr($value, '|') : [];
    }

    /**
     * 设置规格数据
     * @param mixed $value
     * @return string
     */
    public function setSpecsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 获取规格数据
     * @param mixed $value
     * @return array
     */
    public function getSpecsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }
}