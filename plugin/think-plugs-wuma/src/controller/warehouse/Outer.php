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
use plugin\wuma\model\PluginWumaSalesUser;
use plugin\wuma\model\PluginWumaWarehouse;
use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseStock;
use plugin\wuma\service\RemoveService;
use plugin\wuma\service\WhExportService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 出库订单管理
 * @class Outer
 * @package plugin\wuma\controller\warehouse
 */
class Outer extends Controller
{
    /**
     * 仓库出库订单
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseOrder::mQuery()->layTable(function () {
            $this->title = '仓库出库订单';
        }, static function (QueryHelper $query) {

            // 加载对应数据
            $query->with(['bindGoods', 'bindWarehouse']);
            $query->withSearch('outer')->where(['deleted' => 0]);

            // 仓库搜索查询
            $wdb = PluginWumaWarehouse::mQuery()->like('code|name#wname')->db();
            if ($wdb->getOptions('where')) $query->whereRaw("wcode in {$wdb->field('code')->buildSql()}");

            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $idb = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$idb->field('ghash')->buildSql()}");
            }

            // 代理搜索查询
            $db = PluginWumaSalesUser::mQuery()->like('phone|username#agent')->db();
            if ($db->getOptions('where')) $query->whereRaw("auid in {$db->field('id')->buildSql()}");

            // 数据列表处理
            $query->like('code')->equal('type#data_type')->dateBetween('create_time');
        });
    }


    /**
     * 创建产品出库订
     * @auth true
     */
    public function add()
    {
        PluginWumaWarehouseOrder::mForm('form');
    }

    /**
     * 出库订数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = WhExportService::withCode();
        }
        if ($this->request->isGet()) {
            $this->agents = [];
            $this->products = PluginWemallGoods::lists();
            $this->warehouses = PluginWumaWarehouse::lists([
                'status' => 1, 'deleted' => 0
            ]);
        } else {
            $data['type'] = 4;
            if (empty($data['auid'])) $this->error('目标代理不能为空！');
            // 检查出库数量统计
            if ($data['num_need'] < 1) $this->error('出库数量不能为空！');
            // 检查编号是否出现重复
            $map = [['code', '=', $data['code']], ['id', '<>', $data['id'] ?? 0]];
            if (PluginWumaWarehouseOrder::mk()->where($map)->count() > 0) {
                $this->error("出库单号已经存在！");
            }
        }
    }

    /**
     * 查看出库详细
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function show()
    {
        PluginWumaWarehouseOrderData::mQuery()->layTable(function () {
            $this->title = '查看出库详细';
        }, static function (QueryHelper $query) {
            // 主订单搜索
            $odb = PluginWumaWarehouseOrder::mQuery()->equal('type#data_type')->db();
            // 仓库数据搜索
            $wdb = PluginWumaWarehouse::mQuery()->like('code|name#wname')->db();
            if ($wdb->getOptions('where')) $odb->whereRaw("wcode in {$wdb->field('code')->buildSql()}");
            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }
            // 整合条件到模型
            if ($odb->getOptions('where')) $query->whereRaw("code in {$odb->field('code')->buildSql()}");
            // 加载数据
            $query->with(['main', 'user'])->where(['status' => 1]);
            // 出库数据查询
            $query->like('code')->dateBetween('create_time');
        });
    }

    /**
     * 撤销出库记录
     * @auth true
     * @return void
     */
    public function remove()
    {
        try {
            $map = $this->_vali(['code.require' => '出库号不能为空！']);
            $outer = PluginWumaWarehouseOrder::mk()->where($map)->findOrEmpty()->toArray();
            if (empty($outer)) $this->error('待操作出库单数据异常！');
            $this->app->db->transaction(static function () use ($map, $outer) {
                // 清除出库数据
                if (count($mins = RemoveService::outer([], [$map['code']])) > 0) {
                    // 清理退货记录并清除代理数据
                    RemoveService::agent(RemoveService::returns($mins));
                }
                // 计算仓库库存
                PluginWumaWarehouseStock::sync($outer['wcode']);
            });
            $this->success('撤销出库成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception$exception) {
            trace_file($exception);
            $this->error("操作失败，{$exception->getMessage()}");
        }
    }
}