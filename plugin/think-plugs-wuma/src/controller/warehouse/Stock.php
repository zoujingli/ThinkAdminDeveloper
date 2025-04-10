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

namespace plugin\wuma\controller\warehouse;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsItem;
use plugin\wuma\model\PluginWumaWarehouse;
use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseStock;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 仓库储存统计
 * @class Stock
 * @package plugin\wuma\controller\warehouse
 */
class Stock extends Controller
{
    /**
     * 仓库库存管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseStock::mQuery()->layTable(function () {
            $this->title = '仓库库存管理';
        }, static function (QueryHelper $query) {

            // 仓库搜索查询
            $wdb = PluginWumaWarehouse::mQuery()->like('code|name#wname')->db();
            if ($wdb->getOptions('where')) $query->whereRaw("wcode in {$wdb->field('code')->buildSql()}");

            // 关联其他数据
            $query->with(['bindGoods', 'bindWarehouse']);

            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }
        });
    }

    /**
     * 仓库出入库明细
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function show()
    {
        PluginWumaWarehouseOrder::mQuery()->layTable(function () {
            $data = $this->_vali(['ghash.require' => '商品不能为空！', 'wcode.require' => '仓库不能为空！']);
            $this->stock = PluginWumaWarehouseStock::mk()->where($data)->findOrEmpty()->toArray();
            $this->goods = PluginWemallGoodsItem::mk()->where(['ghash' => $data['ghash']])->with('bindGoods')->findOrEmpty()->toArray();
            $this->warehouse = PluginWumaWarehouse::mk()->where(['code' => $data['wcode']])->findOrEmpty()->toArray();
        }, static function (QueryHelper $query) {
            $query->where(['deleted' => 0, 'status' => 2]);
        });
    }
}