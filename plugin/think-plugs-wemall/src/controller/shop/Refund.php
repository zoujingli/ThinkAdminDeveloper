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

use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\Payment;
use plugin\wemall\model\PluginWemallOrderRefund;
use plugin\wemall\service\UserCoupon;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserRefund;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 售后订单管理
 * @class Refund
 * @package plugin\wemall\controller\shop
 */
class Refund extends Controller
{
    /**
     * 售后订单管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = trim($this->get['type'] ?? 'ta', 't');
        PluginWemallOrderRefund::mQuery()->layTable(function (QueryHelper $query) {
            $this->title = '售后订单管理';
            $this->total = ['t0' => 0, 't1' => 0, 't2' => 0, 't3' => 0, 't4' => 0, 't5' => 0, 't6' => 0, 't7' => 0, 'ta' => 0];
            $this->types = ['ta' => '全部订单', 't2' => '待审核', 't3' => '待退货', 't4' => '已退货', 't5' => '退款中', 't6' => '已退款', 't7' => '已完成', 't0' => '已取消'];
            $this->states = UserRefund::states1;
            foreach ($query->db()->field('status,count(1) total')->group('status')->cursor() as $vo) {
                [$this->total["t{$vo['status']}"] = $vo['total'], $this->total['ta'] += $vo['total']];
            }
            $this->reasons = UserRefund::reasons;
        }, function (QueryHelper $query) {
            $query->with(['user', 'orderinfo'])->where('status', '<>', '1');
            // 列表选项卡
            if (is_numeric($this->type)) {
                $query->where(['status' => $this->type]);
            }
        });
    }

    /**
     * 处理订单售后
     * @auto true
     * @return void
     */
    public function edit()
    {
        $this->title = '修改售后单';
        PluginWemallOrderRefund::mQuery(null, function (QueryHelper $query) {
            $query->with([
                'user', 'orderinfo' => function (Query $query) {
                    $query->with(['items', 'payments' => function (Query $query) {
                        $query->where(['payment_status' => 1]);
                    }]);
                },
            ])->mForm('form');
        });
    }

    /**
     * 表单数据处理
     * @param array $data
     * @return void
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            if (empty($data)) $this->error('无效售后单！');
        } else try {
            $refund = PluginWemallOrderRefund::mk()->findOrEmpty($data['id'] ?? 0);
            if ($refund->isEmpty()) $this->error('无效的售后单！');
            $order = UserOrder::widthOrder($refund->getAttr('order_no'));
            if ($order->isEmpty()) $this->error('订单数据异常！');
            $this->app->db->transaction(function () use ($data, $order, $refund) {
                // 根据支付类型，自动合并金额与状态
                foreach ($data['ptypes'] as $pcode => $type) {
                    if (($amount = floatval($data['refunds'][$pcode])) > 0) {
                        $code = $data['pcodes'][$pcode] ?? 0;
                        if ($type === Payment::INTEGRAL) {
                            $rcode = $refund->getAttr('integral_code') ?: Payment::withRefundCode();
                            $data['integral_code'] = $rcode;
                            $data['integral_amount'] = $amount;
                        } elseif ($type === Payment::BALANCE) {
                            $rcode = $refund->getAttr('balance_code') ?: Payment::withRefundCode();
                            $data['balance_code'] = $rcode;
                            $data['balance_amount'] = $amount;
                        } elseif ($type === Payment::COUPON) {
                            $map = ['code' => $pcode, 'channel_type' => Payment::COUPON];
                            $coupon = PluginPaymentRecord::mk()->where($map)->findOrEmpty()->toArray();
                            empty($coupon) || UserCoupon::resume($coupon['payment_trade']);
                            $amount = floatval($coupon['payment_amount']);
                        } else {
                            $rcode = $refund->getAttr('payment_code') ?: Payment::withRefundCode();
                            $data['payment_code'] = $rcode;
                            $data['payment_amount'] = $amount;
                        }
                        // 状态大于 4 时发起退款操作
                        // 流程状态(0已取消,1预订单,2待审核,3待退货,4已退货,5待退款,6已退款,7已完成)
                        if ($data['status'] > 4 && $amount > 0) try {
                            // 发起退款，如果返回 2 则表示已经存在，不需要处理
                            Payment::mk($code)->refund($pcode, strval($amount), $data['remark'], $rcode);
                        } catch (\Exception $exception) {
                            if ($exception->getCode() !== 2) {
                                throw $exception;
                            }
                        }
                    }
                }
                // 如果已经退款了，不让改金额
                if ($refund->getAttr('status') > 4) {
                    // 取消订单奖励
                    UserOrder::cancel($refund->getAttr('order_no'), true);
                    // 去除不相关的字段
                    unset($data['payment_amount'], $data['balance_amount'], $data['integral_amount']);
                }
                // 后台操作人
                $data['admin_by'] = AdminService::getUserId();
                // 更新售后数据
                $refund->save($data);
                // 同步订单状态
                $order->save(['refund_status' => $data['status']]);
            });
            $this->success('保存成功!', 'javascript:history.back()');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 表单结构管理
     * @param boolean $state
     * @return void
     */
    protected function _form_result(bool &$state)
    {
        if ($state) $this->success('修改成功！', 'javascript:history.back()');
    }
}