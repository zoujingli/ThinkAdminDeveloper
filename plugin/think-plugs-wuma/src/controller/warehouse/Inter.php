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

namespace plugin\wuma\controller\warehouse;

use plugin\wemall\model\PluginWemallGoods;
use plugin\wemall\model\PluginWemallGoodsItem;
use plugin\wuma\model\PluginWumaWarehouse;
use plugin\wuma\model\PluginWumaWarehouseOrder;
use plugin\wuma\model\PluginWumaWarehouseOrderData;
use plugin\wuma\model\PluginWumaWarehouseStock;
use plugin\wuma\service\RemoveService;
use plugin\wuma\service\WhImportService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 仓库入库订单
 * @class Inter
 * @package plugin\wuma\controller\warehouse
 */
class Inter extends Controller
{
    /**
     * 仓库入库订单
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWumaWarehouseOrder::mQuery()->layTable(function () {
            $this->title = '仓库入库订单';
        }, static function (QueryHelper $query) {
            // 加载对应数据
            $query->with(['bindGoods', 'bindWarehouse']);
            $query->withSearch('inter')->where(['deleted' => 0]);

            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }

            // 仓库搜索查询
            $db = PluginWumaWarehouse::mQuery()->like('code|name#wname')->db();
            if ($db->getOptions('where')) $query->whereRaw("wcode in {$db->field('code')->buildSql()}");

            // 数据列表处理
            $query->like('code')->equal('type#data_type')->dateBetween('create_time');
        });
    }

    /**
     * 创建产品入库订
     * @auth true
     */
    public function add()
    {
        PluginWumaWarehouseOrder::mForm('form');
    }

    /**
     * 入库订数据处理
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = WhImportService::withCode();
        }
        if ($this->request->isGet()) {
            $this->products = PluginWemallGoods::lists();
            $this->warehouses = PluginWumaWarehouse::lists([
                'status' => 1, 'deleted' => 0
            ]);
        } else {
            $data['type'] = 1;
            // 检查入库数量统计
            if ($data['num_need'] < 1) $this->error('入库数量不能为空！');
            // 检查编号是否出现重复
            $map = [['code', '=', $data['code']], ['id', '<>', $data['id'] ?? 0]];
            if (PluginWumaWarehouseOrder::mk()->where($map)->count() > 0) {
                $this->error("入库单号已经存在！");
            }
            // 虚拟入库直接完成入库
            if ($data['mode'] == 2) {
                try {
                    WhImportService::virtual(intval($data['num_need']), $data['wcode'], $data['ghash']);
                    $this->success('虚拟入库成功！');
                } catch (HttpResponseException $exception) {
                    throw $exception;
                } catch (\Exception $exception) {
                    $this->error($exception->getMessage());
                }
            }
        }
    }

    /**
     * 表单结果处理
     * @param bool $state
     * @param array $data
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function _form_result(bool $state, array $data)
    {
        if ($state) {
            // 刷新仓库统计数据
            PluginWumaWarehouseStock::sync($data['wcode']);
        }
    }

    /**
     * 查看入库详细
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function show()
    {
        PluginWumaWarehouseOrderData::mQuery()->layTable(function () {
            $this->title = '查看入库详细';
        }, static function (QueryHelper $query) {
            // 加载数据
            $query->with(['main'])->where(['status' => 1])->whereIn('type', [1, 2]);

            // 主订单搜索
            $odb = PluginWumaWarehouseOrder::mQuery()->equal('type#data_type')->db();

            // 产品搜索查询
            $gdb = PluginWemallGoods::mQuery()->like('code|name#gname')->db();
            if ($gdb->getOptions('where')) {
                $db2 = PluginWemallGoodsItem::mk()->whereRaw("gcode in {$gdb->field('code')->buildSql()}");
                $query->whereRaw("ghash in {$db2->field('ghash')->buildSql()}");
            }

            // 仓库数据搜索
            $db = PluginWumaWarehouse::mQuery()->like('name|code#wname')->db();
            if ($db->getOptions('where')) $odb->whereRaw("wcode in {$db->field('code')->buildSql()}");

            // 整合条件到模型
            if ($odb->getOptions('where')) $query->whereRaw("code in {$odb->field('code')->buildSql()}");

            // 入库数据查询
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
            $map = $this->_vali(['code.require' => '入库号不能为空！']);
            $inter = PluginWumaWarehouseOrder::mk()->where($map)->findOrEmpty()->toArray();
            if (empty($inter)) $this->error('待操作入库单数据异常！');
            $this->app->db->transaction(static function () use ($map, $inter) {
                // 清理入库数据
                RemoveService::inter([], [$map['code']]);
                // 计算仓库库存
                PluginWumaWarehouseStock::sync($inter['wcode']);
            });
            $this->success('撤销入库成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception$exception) {
            trace_file($exception);
            $this->error("操作失败，{$exception->getMessage()}");
        }
    }
}