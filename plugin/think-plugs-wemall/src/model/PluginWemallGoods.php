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

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * 商城商品数据数据.
 *
 * @property array $cates 分类编号
 * @property array $marks 商品标签
 * @property array $slider 轮播图片
 * @property array $specs 商品规格(JSON)
 * @property string $allow_balance 最大余额支付
 * @property string $allow_integral 最大积分兑换
 * @property string $price_market 最低市场价格
 * @property string $price_selling 最低销售价格
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $discount_id 折扣方案编号(0无折扣,其他折扣)
 * @property int $id
 * @property int $level_agent 推广权益(0无,1有)
 * @property int $level_upgrade 购买升级等级(-1非入会,0不升级,其他升级)
 * @property int $limit_lowvip 限制购买等级(0不限制,其他限制)
 * @property int $limit_maxnum 最大购买数量(0不限制,其他限制)
 * @property int $num_read 访问阅读统计
 * @property int $rebate_type 参与返利(0无需返利,1需要返利)
 * @property int $sort 列表排序权重
 * @property int $ssid 所属商家
 * @property int $status 商品状态(1使用,0禁用)
 * @property int $stock_sales 商品销售统计
 * @property int $stock_total 商品库存统计
 * @property int $stock_virtual 商品虚拟销量
 * @property string $code 商品编号
 * @property string $content 商品详情
 * @property string $cover 商品封面
 * @property string $create_time 创建时间
 * @property string $delivery_code 物流运费模板
 * @property string $name 商品名称
 * @property string $remark 商品描述
 * @property string $update_time 更新时间
 * @property PluginWemallConfigDiscount $discount
 * @property PluginWemallGoodsItem[] $items
 * @property PluginWemallUserActionComment[] $comments
 * @class PluginWemallGoods
 */
class PluginWemallGoods extends Abs
{
    /**
     * 日志名称.
     * @var string
     */
    protected $oplogName = '商品';

    /**
     * 日志类型.
     * @var string
     */
    protected $oplogType = '分销商城管理';

    /**
     * 关联产品规格
     */
    public function items(): HasMany
    {
        return $this->hasMany(PluginWemallGoodsItem::class, 'gcode', 'code')
            ->withoutField('id,status,create_time,update_time')
            ->where(['status' => 1]);
    }

    /**
     * 关联商品评论数据.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(PluginWemallUserActionComment::class, 'gcode', 'code')->with('bindUser');
    }

    /**
     * 关联商品折扣方案.
     */
    public function discount(): HasOne
    {
        return $this->hasOne(PluginWemallConfigDiscount::class, 'id', 'discount_id')->where(['status' => 1, 'deleted' => 0])->field('id,name,items');
    }

    /**
     * 关联产品列表.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists(): array
    {
        $model = static::mk()->with('items')->withoutField('specs');
        return $model->order('sort desc,id desc')->where(['deleted' => 0])->select()->toArray();
    }

    /**
     * 标签处理.
     * @param mixed $value
     */
    public function getMarksAttr($value): array
    {
        $ckey = 'PluginWemallGoodsMarkItems';
        $items = sysvar($ckey) ?: sysvar($ckey, PluginWemallGoodsMark::items());
        return str2arr(is_array($value) ? arr2str($value) : $value, ',', $items);
    }

    /**
     * 处理商品分类数据.
     * @param mixed $value
     */
    public function getCatesAttr($value): array
    {
        $ckey = 'PluginWemallGoodsCateItem';
        $cates = sysvar($ckey) ?: sysvar($ckey, PluginWemallGoodsCate::items(true));
        $cateids = is_string($value) ? str2arr($value) : (array)$value;
        foreach ($cates as $cate) {
            if (in_array($cate['id'], $cateids)) {
                return $cate;
            }
        }
        return [];
    }

    /**
     * 设置轮播图片.
     * @param mixed $value
     */
    public function setSliderAttr($value): string
    {
        return is_string($value) ? $value : (is_array($value) ? arr2str($value) : '');
    }

    /**
     * 获取轮播图片.
     * @param mixed $value
     */
    public function getSliderAttr($value): array
    {
        return is_string($value) ? str2arr($value, '|') : [];
    }

    /**
     * 设置规格数据.
     * @param mixed $value
     */
    public function setSpecsAttr($value): string
    {
        return $this->setExtraAttr($value);
    }

    /**
     * 获取规格数据.
     * @param mixed $value
     */
    public function getSpecsAttr($value): array
    {
        return $this->getExtraAttr($value);
    }
}
