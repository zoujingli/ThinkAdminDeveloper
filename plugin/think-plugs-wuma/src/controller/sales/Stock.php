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

namespace plugin\wuma\controller\sales;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsItem;
use plugin\wuma\model\PluginWumaSalesOrder;
use plugin\wuma\model\PluginWumaSalesUser;
use plugin\wuma\model\PluginWumaSalesUserStock;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 代理库存管理.
 * @class Stock
 */
class Stock extends Controller
{
    /**
     * 代理库存管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSalesUserStock::mQuery()->layTable(function () {
            $this->title = '代理库存管理';
        }, static function (QueryHelper $query) {
            // 查询条件排序
            $query->with(['agent', 'bindGoods']);
            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }
            // 代理搜索查询
            $db = PluginWumaSalesUser::mQuery()->like('phone,username')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("auid in {$db->field('id')->buildSql()}");
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
        $data = $this->_vali([
            'auid.require' => '代理用户不能为空！',
            'product_code.require' => '产品编号不能为空！',
            'product_spec.require' => '产品规格不能为空！',
        ]);

        PluginWumaSalesOrder::mQuery()->layTable(function () use ($data) {
            $this->assign($data);
            $this->stock = PluginWumaSalesUserStock::mk()->where($data)->find();
            $this->agent = PluginWumaSalesUser::mk()->where(['id' => $data['auid']])->find();
            // $this->product = DataBrandProduct::mk()->where(['code' => $data['product_code']])->find();
        }, static function (QueryHelper $query) use ($data) {
            $data['auid|xuid'] = $data['auid'];
            unset($data['auid']);
            $query->where($data)->field('mode,auid,xuid,code,num_need,num_used,vir_need,vir_used,create_time');
        });
    }
}
