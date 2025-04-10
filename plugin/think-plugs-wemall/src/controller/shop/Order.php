<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
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
use plugin\payment\service\Payment;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderSender;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserRefund;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 订单数据管理
 * @class Order
 * @package plugin\wemall\controller\shop
 */
class Order extends Controller
{
    /**
     * 支付方式
     * @var array
     */
    protected $payments = [];

    /**
     * 控制器初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->payments = Payment::types();
    }

    /**
     * 订单数据管理
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = trim($this->get['type'] ?? 'ta', 't');
        PluginWemallOrder::mQuery()->layTable(function (QueryHelper $query) {
            $this->title = '订单数据管理';
            $this->total = ['t0' => 0, 't1' => 0, 't2' => 0, 't3' => 0, 't4' => 0, 't5' => 0, 't6' => 0, 't7' => 0, 'ta' => 0];
            $this->types = ['ta' => '全部订单', 't2' => '待支付', 't3' => '待审核', 't4' => '待发货', 't5' => '已发货', 't6' => '已收货', 't7' => '已评论', 't0' => '已取消'];
            $this->refunds = UserRefund::states2;
            foreach ($query->db()->field('status,count(1) total')->group('status')->cursor() as $vo) {
                [$this->total["t{$vo['status']}"] = $vo['total'], $this->total['ta'] += $vo['total']];
            }
        }, function (QueryHelper $query) {

            $query->with(['user', 'from', 'items', 'address']);

            $query->equal('status,refund_status')->like('order_no');
            $query->dateBetween('create_time,payment_time,cancel_time,delivery_type');

            // 发货信息搜索
            $db = PluginWemallOrderSender::mQuery()->dateBetween('express_time')
                ->like('user_name|user_phone|region_prov|region_city|region_area|region_addr#address,express_code#delivery_express_code')->db();
            if ($db->getOptions('where')) $query->whereRaw("order_no in {$db->field('order_no')->buildSql()}");

            // 用户搜索查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#user_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("unid in {$db->field('id')->buildSql()}");

            // 代理搜索查询
            $db = PluginAccountUser::mQuery()->like('phone|nickname#from_keys')->db();
            if ($db->getOptions('where')) $query->whereRaw("puid1 in {$db->field('id')->buildSql()}");

            // 列表选项卡
            if (is_numeric($this->type)) {
                $query->where(['status' => $this->type]);
            }

            // 分页排序处理
            $query->where(['deleted_status' => 0]);
        });
    }

    /**
     * 单据凭证支付审核
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function audit()
    {
        if ($this->request->isGet()) {
            PluginWemallOrder::mForm('', 'order_no');
        } else {
            $data = $this->_vali([
                'order_no.require' => '订单单号不能为空！',
                'status.in:0,1'    => '审核状态数值异常！',
                'status.require'   => '审核状态不能为空！',
                'remark.default'   => '',
            ]);
            if (empty($data['status'])) {
                $data['status'] = 0;
                $data['cancel_status'] = 1;
                $data['cancel_remark'] = $data['remark'] ?: '后台审核驳回并取消订单';
                $data['cancel_time'] = date('Y-m-d H:i:s');
            } else {
                $data['status'] = 4;
                $data['payment_code'] = CodeExtend::uniqidDate(16, 'T');
                $data['payment_time'] = date('Y-m-d H:i:s');
                $data['payment_status'] = 1;
                $data['payment_remark'] = $data['remark'] ?: '后台审核支付凭证通过';
            }
            $order = PluginWemallOrder::mk()->where(['order_no' => $data['order_no']])->findOrEmpty();
            if ($order->isEmpty() || $order['status'] !== 3) $this->error('不允许操作审核！');
            // 无需发货时的处理
            if ($data['status'] === 4 && empty($order['delivery_type'])) $data['status'] = 6;
            // 更新订单支付状态
            $map = ['status' => 3, 'order_no' => $data['order_no']];
            if (PluginWemallOrder::mk()->strict(false)->where($map)->update($data) !== false) {
                if (in_array($data['status'], [4, 5, 6])) {
                    $this->app->event->trigger('PluginPaymentSuccess', $data);
                    $this->success('订单审核通过成功！');
                } else {
                    $this->app->event->trigger('PluginWemallOrderCancel', $order);
                    UserOrder::stock($data['order_no']);
                    $this->success('审核驳回并取消成功！');
                }
            } else {
                $this->error('订单审核失败！');
            }
        }
    }

    /**
     * 订单自动处理
     * @auth true
     * @return void
     */
    public function clean()
    {
        $this->_queue('定时清理无效订单数据', "xdata:mall:clear", 0, [], 0, 60);
    }

    /**
     * 取消未支付的订单
     * @auth true
     * @return void
     */
    public function cancel()
    {
        $data = $this->_vali(['order_no.require' => '订单号不能为空！']);
        $order = PluginWemallOrder::mk()->where($data)->findOrEmpty();
        if ($order->isEmpty()) $this->error('订单查询异常！');
        try {
            if (!in_array($order['status'], [1, 2, 3])) {
                $this->error('订单不能取消！');
            }
            $result = $order->save([
                'status'        => 0,
                'cancel_status' => 1,
                'cancel_remark' => '后台取消未支付的订单',
                'cancel_time'   => date('Y-m-d H:i:s'),
            ]);
            if ($result !== false) {
                UserOrder::stock($order['order_no']);
                $this->app->event->trigger('PluginWemallOrderCancel', $order);
                $this->success('取消未支付的订单成功！');
            } else {
                $this->error('取消支付的订单失败！');
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}