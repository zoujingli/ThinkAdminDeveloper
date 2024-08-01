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

namespace plugin\wemall\controller\shop;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallExpressCompany;
use plugin\wemall\model\PluginWemallExpressTemplate;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderSender;
use plugin\wemall\service\ExpressService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 订单发货管理
 * @class Sender
 * @package plugin\wemall\controller\shop
 */
class Sender extends Controller
{
    /**
     * 订单状态
     * @var int[]
     */
    private $oStatus = [4, 5, 6, 7];

    /**
     * 订单发货管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = trim(input('type', 'ta'), 't');
        PluginWemallOrderSender::mQuery()->layTable(function () {
            $this->title = '订单发货管理';
            $this->total = ['t0' => 0, 't1' => 0, 't2' => 0, 'ta' => 0];
            $this->address = sysdata('plugin.wemall.address');
            // 订单状态统计
            $order = PluginWemallOrder::mk()->whereIn('status', $this->oStatus)->where(['delivery_type' => 1]);
            $query = PluginWemallOrderSender::mk()->whereRaw("order_no in {$order->field('order_no')->buildSql()}");
            foreach ($query->fieldRaw('status,count(1) total')->group('status')->cursor() as $vo) {
                $this->total["ta"] += $vo['total'];
                $this->total["t{$vo['status']}"] = $vo['total'];
            }
        }, function (QueryHelper $query) {
            $query->with(['user', 'main']);
            $query->like('user_name|user_phone#user_name,region_prov|region_city|region_area|region_addr#address');
            $query->dateBetween('create_time,express_time')->equal('status')->like('express_code,order_no');

            // 用户搜索查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#user_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");

            // 订单搜索查询
            $db = PluginWemallOrder::mk()->whereIn('status', $this->oStatus)->where(['delivery_type' => 1]);
            $query->whereRaw("order_no in {$db->field('order_no')->buildSql()}");

            // 列表选项卡状态
            if (is_numeric($this->type)) {
                $query->where(['status' => $this->type]);
            }
        });
    }

    /**
     * 快递发货地址
     * @auth true
     * @throws \think\admin\Exception
     */
    public function config()
    {
        if ($this->request->isGet()) {
            $this->vo = sysdata('plugin.wemall.address');
            $this->fetch();
        } else {
            sysdata('plugin.wemall.address', $this->request->post());
            $this->success('地址保存成功！');
        }
    }

    /**
     * 修改快递管理
     * @auth true
     */
    public function delivery()
    {
        PluginWemallOrderSender::mForm('delivery_form', 'order_no');
    }

    /**
     * 快递表单处理
     * @param array $vo
     */
    protected function _delivery_form_filter(array &$vo)
    {
        if ($this->request->isGet()) {
            $map = ['code' => $vo['delivery_code'], 'status' => 1, 'deleted' => 0];
            $delivery = PluginWemallExpressTemplate::mk()->where($map)->findOrEmpty();
            if ($delivery->isEmpty() || empty($this->items = $delivery->getAttr('company'))) {
                $this->items = PluginWemallExpressCompany::items();
            }
        } elseif ($this->request->isPost()) {
            $map = ['order_no' => $vo['order_no']];
            $order = PluginWemallOrder::mk()->where($map)->findOrEmpty();
            if ($order->isEmpty()) $this->error('订单查询异常，请稍候再试！');

            // 配送快递公司填写
            $map = ['code' => $vo['company_code']];
            $company = PluginWemallExpressCompany::mk()->where($map)->findOrEmpty();
            if ($company->isEmpty()) $this->error('配送快递公司异常，请重新选择快递公司！');

            // 追加表单数据
            $vo['status'] = 2;
            $vo['company_name'] = $company['name'];
            $vo['express_time'] = $vo['express_time'] ?? date('Y-m-d H:i:s');

            $vo['region_prov'] = $vo['form_prov'] ?? '';
            $vo['region_city'] = $vo['form_city'] ?? '';
            $vo['region_area'] = $vo['form_area'] ?? '';

            // 更新订单发货状态
            if ($order['status'] === 4) $order->save(['status' => 5]);
        }
    }

    /**
     * 快递追踪查询
     * @auth true
     */
    public function query()
    {
        try {
            $data = $this->_vali([
                'code.require'   => '快递编号不能为空！',
                'number.require' => '快递单号不能为空！'
            ]);
            $this->result = ExpressService::query($data['code'], $data['number']);
            if (empty($this->result['code'])) {
                $this->error($this->result['info']);
            } else {
                $this->fetch('delivery_query');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}