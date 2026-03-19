<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wuma\controller\warehouse;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsItem;
use plugin\wuma\model\PluginWumaWarehouse;
use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseStock;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 仓库储存统计
 * @class Stock
 */
class Stock extends Controller
{
    /**
     * 仓库库存管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseStock::mQuery()->layTable(function () {
            $this->title = '仓库库存管理';
        }, static function (QueryHelper $query) {
            // 仓库搜索查询
            $wdb = PluginWumaWarehouse::mQuery()->like('code|name#wname')->db();
            if (!empty($wdb->getOptions()['where'] ?? [])) {
                $query->whereRaw("wcode in {$wdb->field('code')->buildSql()}");
            }

            // 关联其他数据
            $query->with(['bindGoods', 'bindWarehouse']);

            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if (!empty($gdb->getOptions()['where'] ?? [])) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }
        });
    }

    /**
     * 仓库出入库明细.
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function show()
    {
        PluginWumaWarehouseOrder::mQuery()->layTable(function () {
            $data = $this->_vali(['ghash.require' => '商品不能为空！', 'wcode.require' => '仓库不能为空！']);
            $this->stock = PluginWumaWarehouseStock::mk()->where($data)->findOrEmpty()->toArray();
            $this->goods = PluginWemallGoodsItem::mk()->where(['ghash' => $data['ghash']])->with('bindGoods')->findOrEmpty()->toArray();
            $this->warehouse = PluginWumaWarehouse::mk()->where(['code' => $data['wcode']])->findOrEmpty()->toArray();
        }, static function (QueryHelper $query) {
            $query->where(['status' => 2]);
        });
    }
}
