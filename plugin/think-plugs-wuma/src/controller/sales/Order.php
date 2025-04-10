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

namespace plugin\wuma\controller\sales;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsItem;
use plugin\wuma\model\PluginWumaSalesOrder;
use plugin\wuma\model\PluginWumaSalesUser;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 代理调货管理
 * @class Order
 * @package plugin\wuma\controller\sales
 */
class Order extends Controller
{
    /**
     * 代理调货管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaSalesOrder::mQuery()->layTable(function () {
            $this->title = '代理调货管理';
        }, static function (QueryHelper $query) {
            $query->with(['agent', 'fromer', 'bindGoods']);
            $query->like('code')->dateBetween('create_time');

            // 代理搜索查询
            $db = PluginWumaSalesUser::mQuery()->like('phone|username#agent')->db();
            if ($db->getOptions('where')) $query->whereRaw("auid in {$db->field('id')->buildSql()}");

            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }

            // 代理搜索查询
            $db = PluginWumaSalesUser::mQuery()->like('phone|username#fromer')->db();
            if ($db->getOptions('where')) $query->whereRaw("xuid in {$db->field('id')->buildSql()}");

        });
    }

    /**
     * 查看出库详细
     * @auth true
     */
    public function show()
    {
        $data = $this->_vali(['code.require' => '入货单号不能为空！']);
        $this->data = PluginWumaSalesOrder::mk()->where($data)->with(['nums', 'agent', 'fromer', 'product'])->findOrEmpty()->toArray();
        $this->fetch();
    }
}