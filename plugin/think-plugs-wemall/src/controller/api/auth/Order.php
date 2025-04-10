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

namespace plugin\wemall\controller\api\auth;

use plugin\payment\model\PluginPaymentAddress;
use plugin\payment\service\Balance;
use plugin\payment\service\contract\PaymentResponse;
use plugin\payment\service\Integral;
use plugin\payment\service\Payment;
use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderCart;
use plugin\wemall\model\PluginWemallOrderItem;
use plugin\wemall\model\PluginWemallUserCoupon;
use plugin\wemall\service\ExpressService;
use plugin\wemall\service\GoodsService;
use plugin\wemall\service\UserAction;
use plugin\wemall\service\UserCoupon;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserUpgrade;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\Storage;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 订单数据接口
 * @class Order
 * @package plugin\wemall\controller\api\auth
 */
class Order extends Auth
{

    /**
     * 获取订单数据
     * @return void
     */
    public function get()
    {
        PluginWemallOrder::mQuery(null, function (QueryHelper $query) {
            if (empty(input('order_no'))) {
                $query->with('items')->where(['refund_status' => 0]);
            } else {
                $query->with(['items', 'address', 'sender', 'payments' => function (Query $query) {
                    $query->where(static function (Query $query) {
                        $query->whereOr(['channel_type' => Payment::VOUCHER, 'payment_status' => 1, 'audit_status' => 1]);
                    });
                }]);
            }
            $query->in('status')->equal('order_no');
            $query->where(['unid' => $this->unid, 'deleted_status' => 0])->order('id desc');
            $this->success('获取订单成功！', $query->page(intval(input('page')), false, false, 10));
        });
    }

    /**
     * 创建订单数据
     * @return void
     */
    public function add()
    {
        try {
            // 请求参数检查
            $input = $this->_vali([
                'carts.default' => '',
                'rules.default' => '',
                'agent.default' => '0',
            ]);
            if (empty($input['rules']) && empty($input['carts'])) $this->error('参数无效！');
            // 绑定代理数据
            $order = UserUpgrade::withAgent($this->relation, intval($input['agent']));
            // 生成统一编号
            do $extra = ['order_no' => $order['order_no'] = CodeExtend::uniqidNumber(16, 'N')];
            while (PluginWemallOrder::mk()->master()->where($extra)->findOrEmpty()->isExists());
            [$items, $deliveryType] = [[], 0];
            // 组装订单数据
            foreach (GoodsService::parse($this->unid, trim($input['rules'], ':;'), $input['carts']) as $item) {
                if (empty($item['count'])) continue;
                if (empty($item['goods']) || empty($item['specs'])) $this->error('商品无效！');
                [$goods, $gspec, $count] = [$item['goods'], $item['specs'], intval($item['count'])];
                // 订单物流类型
                if (empty($deliveryType) && $goods['delivery_code'] !== 'NONE') $deliveryType = 1;
                // 限制购买数量
                if (isset($goods['limit_maxnum']) && $goods['limit_maxnum'] > 0) {
                    $join = [PluginWemallOrderItem::mk()->getTable() => 'b'];
                    $where = [['a.unid', '=', $this->unid], ['a.status', '>', 1], ['b.gcode', '=', $goods['code']]];
                    $buyCount = PluginWemallOrder::mk()->alias('a')->join($join, 'a.order_no=b.order_no')->where($where)->sum('b.stock_sales');
                    if ($buyCount + $count > $goods['limit_maxnum']) $this->error('商品限购！');
                }
                // 限制购买身份
                if ($goods['limit_lowvip'] > $this->levelCode) $this->error('等级不够！');
                // 商品库存检查
                if ($gspec['stock_sales'] + $count > $gspec['stock_total']) $this->error('库存不足！');
                // 商品折扣处理
                [$discountId, $discountRate] = UserOrder::discount($goods['discount_id'], $this->levelCode);
                // 订单详情处理
                $items[] = [
                    'unid'                  => $order['unid'],
                    'order_no'              => $order['order_no'],
                    // 商品字段
                    'gsku'                  => $gspec['gsku'],
                    'gname'                 => $goods['name'],
                    'gcode'                 => $gspec['gcode'],
                    'ghash'                 => $gspec['ghash'],
                    'gspec'                 => $gspec['gspec'],
                    'gunit'                 => $gspec['gunit'],
                    'gcover'                => empty($gspec['gimage']) ? $goods['cover'] : $gspec['gimage'],
                    // 库存数量处理
                    'stock_sales'           => $count,
                    // 快递发货数据
                    'delivery_code'         => $goods['delivery_code'],
                    'delivery_count'        => $goods['rebate_type'] > 0 ? $gspec['number_express'] * $count : 0,
                    // 商品费用字段
                    'price_cost'            => $gspec['price_cost'],
                    'price_market'          => $gspec['price_market'],
                    'price_selling'         => $gspec['price_selling'],
                    // 商品费用统计
                    'total_price_cost'      => $gspec['price_cost'] * $count,
                    'total_price_market'    => $gspec['price_market'] * $count,
                    'total_price_selling'   => $gspec['price_selling'] * $count,
                    'total_allow_balance'   => $gspec['allow_balance'] * $count,
                    'total_allow_integral'  => $gspec['allow_integral'] * $count,
                    'total_reward_balance'  => $gspec['reward_balance'] * $count,
                    'total_reward_integral' => $gspec['reward_integral'] * $count,
                    // 会员等级
                    'level_code'            => $this->levelCode,
                    'level_name'            => $this->relation->getAttr('level_name'),
                    'level_agent'           => $goods['level_agent'],
                    'level_upgrade'         => $goods['level_upgrade'],
                    // 是否参与返佣
                    'rebate_type'           => $goods['rebate_type'],
                    'rebate_amount'         => $goods['rebate_type'] > 0 ? $gspec['price_selling'] * $count : 0,
                    // 等级优惠方案
                    'discount_id'           => $discountId,
                    'discount_rate'         => $discountRate,
                    'discount_amount'       => $discountRate * $gspec['price_selling'] * $count / 100,
                ];
            }
            // 默认使用销售销售
            $order['rebate_amount'] = array_sum(array_column($items, 'rebate_amount'));
            $order['allow_balance'] = array_sum(array_column($items, 'total_allow_balance'));
            $order['allow_integral'] = array_sum(array_column($items, 'total_allow_integral'));
            $order['reward_balance'] = array_sum(array_column($items, 'total_reward_balance'));
            $order['reward_integral'] = array_sum(array_column($items, 'total_reward_integral'));
            // 会员及代理升级
            $order['level_agent'] = intval(max(array_column($items, 'level_agent')));
            $order['level_member'] = intval(max(array_column($items, 'level_upgrade')));
            // 订单发货类型
            $order['status'] = $deliveryType ? 1 : 2;
            $order['delivery_type'] = $deliveryType;
            $order['ratio_integral'] = Integral::ratio();
            // 统计商品数量
            $order['number_goods'] = array_sum(array_column($items, 'stock_sales'));
            $order['number_express'] = array_sum(array_column($items, 'delivery_count'));
            // 统计商品金额
            $order['amount_cost'] = array_sum(array_column($items, 'total_price_cost'));
            $order['amount_goods'] = array_sum(array_column($items, 'total_price_selling'));
            // 折扣后的金额
            $order['amount_discount'] = array_sum(array_column($items, 'discount_amount'));
            // 订单随减金额
            $order['amount_reduct'] = UserOrder::reduct();
            if ($order['amount_reduct'] > $order['amount_goods']) {
                $order['amount_reduct'] = $order['amount_goods'];
            }
            // 统计订单金额
            $order['amount_real'] = round($order['amount_discount'] - $order['amount_reduct'], 2);
            $order['amount_total'] = $order['amount_goods'];
            $order['amount_profit'] = round($order['amount_real'] - $order['amount_cost']);
            // 写入商品数据
            $model = PluginWemallOrder::mk();
            $this->app->db->transaction(function () use ($order, $items, &$model) {
                $model->save($order) && PluginWemallOrderItem::mk()->saveAll($items);
                // 设置收货地址
                if ($order['delivery_type']) {
                    $where = ['unid' => $this->unid, 'deleted' => 0];
                    $address = PluginPaymentAddress::mk()->where($where)->order('type desc,id desc')->findOrEmpty();
                    $address->isExists() && UserOrder::perfect($model->refresh(), $address);
                }
            });
            // 同步库存销量
            foreach (array_unique(array_column($items, 'gcode')) as $gcode) {
                GoodsService::stock($gcode);
            }
            // 清理购物车数据
            if (count($carts = str2arr($input['carts'])) > 0) {
                PluginWemallOrderCart::mk()->whereIn('id', $carts)->delete();
                UserAction::recount($this->unid);
            }
            // 触发订单创建事件
            $this->app->event->trigger('PluginWemallOrderCreate', $order);
            // 无需发货且无需支付，直接完成支付流程
            if ($order['status'] === 2 && empty($order['amount_real'])) {
                Payment::emptyPayment($this->account, $order['order_no']);
                $this->success('下单成功！', $model->toArray());
            }
            // 返回处理成功数据
            $this->success('下单成功！', array_merge($order, ['items' => $items]));
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error("下单失败，{$exception->getMessage()}");
        }
    }

    /**
     * 模拟计算运费
     * @return void
     * @throws \think\admin\Exception
     */
    public function express()
    {
        $data = $this->_vali([
            'unid.value'         => $this->unid,
            'order_no.require'   => '单号不能为空！',
            'address_id.require' => '地址不能为空！',
        ]);

        // 用户收货地址
        $map = ['unid' => $this->unid, 'id' => $data['address_id']];
        $addr = PluginPaymentAddress::mk()->where($map)->findOrEmpty();
        if ($addr->isEmpty()) $this->error('地址异常！');

        // 订单状态检查
        $map = ['unid' => $this->unid, 'order_no' => $data['order_no']];
        $tCount = intval(PluginWemallOrderItem::mk()->where($map)->sum('delivery_count'));

        // 根据地址计算运费
        $map = ['status' => 1, 'deleted' => 0, 'order_no' => $data['order_no']];
        $tCode = PluginWemallOrderItem::mk()->where($map)->column('delivery_code');
        [$amount, , , $remark] = ExpressService::amount($tCode, $addr['region_prov'], $addr['region_city'], $tCount);
        $this->success('计算运费！', ['amount' => $amount, 'remark' => $remark]);
    }

    /**
     * 确认收货地址
     * @return void
     * @throws \think\admin\Exception
     */
    public function perfect()
    {
        $data = $this->_vali([
            'unid.value'         => $this->unid,
            'order_no.require'   => '单号不能为空',
            'address_id.require' => '地址不能为空',
        ]);

        // 用户收货地址
        $where = ['id' => $data['address_id'], 'unid' => $this->unid, 'deleted' => 0];
        $address = PluginPaymentAddress::mk()->where($where)->findOrEmpty();
        if ($address->isEmpty()) $this->error('地址异常！');

        // 订单状态检查
        $where = ['unid' => $this->unid, 'order_no' => $data['order_no'], 'delivery_type' => 1];
        $order = PluginWemallOrder::mk()->where($where)->whereIn('status', [1, 2])->findOrEmpty();
        if ($order->isEmpty()) $this->error('不能修改！');

        // 更新订单收货地址
        if (UserOrder::perfect($order, $address)) {
            $this->success('确认成功！', $order->refresh()->toArray());
        } else {
            $this->error('确认失败！');
        }
    }

    /**
     * 获取支付通道
     * @return void
     * @throws \think\admin\Exception
     */
    public function channel()
    {
        $this->success('获取支付通道！', [
            'toratio'  => Integral::ratio(),
            'channels' => Payment::typesByAccess($this->type, true),
        ]);
    }

    /**
     * 获取支付参数
     * @return void
     */
    public function payment()
    {
        $data = $this->_vali([
            'unid.value'            => $this->unid,
            'balance.default'       => '0.00',
            'integral.default'      => '0',
            'order_no.require'      => '单号不能为空',
            'order_ps.default'      => '',
            'coupon_code.default'   => '', # 优惠券编号
            'channel_code.require'  => '支付不能为空',
            'payment_back.default'  => '', # 支付回跳地址
            'payment_image.default' => '', # 支付凭证图片
        ]);
        try {
            $order = $this->getOrderModel();
            $status = intval($order->getAttr('status'));
            if ($status > 3) $this->success('已完成支付！');
            if ($status === 3) $this->error('凭证待审核！');
            if ($status !== 2) $this->error('不能发起支付！');

            // 订单备注内容更新
            empty($data['order_ps']) || $order->save(['order_ps' => $data['order_ps']]);

            // 无需支付，直接完成订单
            if (floatval($orderAmount = $order->getAttr('amount_real')) <= 0) {
                $this->success('已支付成功！', Payment::emptyPayment($this->account, $data['order_no'])->toArray());
            }

            // 剩余支付金额
            if (($leaveAmount = Payment::leaveAmount($data['order_no'], $orderAmount)) <= 0) {
                $this->success('已完成支付！', PaymentResponse::mk(true, '已完成支付！')->toArray());
            }

            // 扣除优惠券
            if (!empty($data['coupon_code']) && $data['coupon_code'] !== $order->getAttr('coupon_code')) try {
                // 检查优惠券是否有效
                $where = ['unid' => $this->unid, 'status' => 1, 'deleted' => 0];
                $coupon = PluginWemallUserCoupon::mk()->where($where)->with('bindCoupon')->findOrEmpty();
                if ($coupon->isEmpty() || empty($coupon->getAttr('coupon_status')) || $coupon->getAttr('coupon_deleted') > 0) {
                    $this->error('无限优惠券！');
                }
                if ($coupon->getAttr('expire') > 0 && $coupon->getAttr('expire') < time()) $this->error('优惠券无效！');
                if (floatval($coupon->getAttr('limit_amount')) <= $orderAmount) $this->error('未达到使用条件！');
                [$couponCode, $couponAmount] = [strval($coupon->getAttr('code')), strval($coupon->getAttr('coupon_amount'))];
                $response = Payment::mk(Payment::COUPON)->create($this->account, $data['order_no'], '优惠券抵扣', $orderAmount, $couponAmount, '', '', '', $couponCode);
                $order->save(['coupon_code' => $couponCode, 'coupon_amount' => $couponAmount]);
                $coupon->save(['used' => 1, 'status' => 2, 'used_time' => date('Y-m-d H:i:s')]);
                if (($leaveAmount = Payment::leaveAmount($data['order_no'], $orderAmount)) <= 0) $this->success('已完成支付！', $response->toArray());
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }

            // 积分抵扣处理
            if ($leaveAmount > 0 && $data['integral'] > 0) {
                if ($data['integral'] > $order->getAttr('allow_integral')) $this->error("超出积分抵扣！");
                if ($data['integral'] > Integral::recount($this->unid)['usable']) $this->error('账号积分不足！');
                $response = Payment::mk(payment::INTEGRAL)->create($this->account, $data['order_no'], '账号积分抵扣', $orderAmount, $data['integral']);
                if (($leaveAmount = Payment::leaveAmount($data['order_no'], $orderAmount)) <= 0) $this->success('已完成支付！', $response->toArray());
            }

            // 余额支付扣减
            if ($leaveAmount > 0 && $data['balance'] > 0) {
                if ($data['balance'] > $order->getAttr('allow_balance')) $this->error("超出余额限额！");
                if ($data['balance'] > Balance::recount($this->unid)['usable']) $this->error('账号余额不足！');
                $response = Payment::mk(Payment::BALANCE)->create($this->account, $data['order_no'], '账号余额支付！', $orderAmount, $data['balance']);
                if (($leaveAmount = Payment::leaveAmount($data['order_no'], $orderAmount)) <= 0) $this->success('已完成支付！', $response->toArray());
            }

            // 凭证图片保存
            if (!empty($data['payment_image'])) {
                $data['payment_image'] = Storage::saveImage($data['payment_image'])['url'] ?? '';
            }

            // 创建支付订单
            $response = Payment::mk($data['channel_code'])->create(
                $this->account, $data['order_no'], '商城订单支付',
                $orderAmount, strval($leaveAmount), '', $data['payment_back'], $data['payment_image']
            );

            // 标准化返回结果
            if ($response->status) {
                $this->success($response->message, $response->toArray());
            } else {
                $this->error($response->message, $response->toArray());
            }
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->error($exception->getMessage());
        }
    }

    /**
     * 取消未支付订单
     * @return void
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancel()
    {
        $order = $this->getOrderModel();
        if (in_array($order->getAttr('status'), [1, 2, 3])) {
            $data = [
                'status'        => 0,
                'cancel_time'   => date('Y-m-d H:i:s'),
                'cancel_status' => 1,
                'cancel_remark' => '用户主动取消订单！',
            ];
            if ($order->save($data) && UserOrder::stock($order->getAttr('order_no'))) {
                // 触发订单取消事件
                Payment::refund($order->getAttr('order_no'));
                $this->app->event->trigger('PluginWemallOrderCancel', $order);
                // 返回处理成功数据
                $this->success('取消成功！');
            } else {
                $this->error('取消失败！');
            }
        } else {
            $this->error('不可取消！');
        }
    }

    /**
     * 删除已取消订单
     * @return void
     */
    public function remove()
    {
        $order = $this->getOrderModel();
        if ($order->isEmpty()) $this->error('读取订单失败！');
        if ($order->getAttr('status') == 0) {
            if ($order->save([
                'status'         => 0,
                'deleted_time'   => date('Y-m-d H:i:s'),
                'deleted_status' => 1,
                'deleted_remark' => '用户主动删除订单！',
            ])) {
                // 触发订单删除事件
                $this->app->event->trigger('PluginWemallOrderRemove', $order);
                // 返回处理成功数据
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        } else {
            $this->error('不可删除！');
        }
    }

    /**
     * 订单确认收货
     * @return void
     */
    public function confirm()
    {
        $order = $this->getOrderModel();
        if (in_array($order->getAttr('status'), [5, 6])) {
            // 触发订单确认事件
            $order->save([
                'status'         => 6,
                'confirm_time'   => date('Y-m-d H:i:s'),
                'confirm_remark' => '用户主动确认签收订单！',
            ]);
            $this->app->event->trigger('PluginWemallOrderConfirm', $order);
            // 返回处理成功数据
            $this->success('确认成功！');
        } else {
            $this->error('确认失败！');
        }
    }

    /**
     * 提交订单评论
     * @return void
     */
    public function comment()
    {
        $order = $this->getOrderModel();
        if (in_array($order->getAttr('status'), [6, 7])) try {
            // 接收评论数据
            $items = json_decode($this->request->post('data'), true);
            $this->app->db->transaction(function () use ($order, $items) {
                $order->save(['status' => 7]);
                $order->items()->select()->map(function (PluginWemallOrderItem $item) use ($items) {
                    if (!empty($input = $items[$item->getAttr('ghash')])) {
                        UserAction::comment($item, $input['rate'], $input['content'], $input['images']);
                    }
                });
            });
            $this->success('评论成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        } else {
            $this->error('无需评论！');
        }
    }

    /**
     * 订单状态统计
     * @return void
     */
    public function total()
    {
        $data = ['t0' => 0, 't1' => 0, 't2' => 0, 't3' => 0, 't4' => 0, 't5' => 0, 't6' => 0, 't7' => 0];
        $query = PluginWemallOrder::mk()->where(['unid' => $this->unid, 'refund_status' => 0, 'deleted_status' => 0]);
        foreach ($query->field('status,count(1) count')->group('status')->cursor() as $item) {
            $data["t{$item['status']}"] = $item['count'];
        }
        $this->success('获取统计！', $data);
    }

    /**
     * 物流追踪查询
     * @return void
     */
    public function track()
    {
        try {
            $data = $this->_vali([
                'code.require' => '快递不能为空', 'number.require' => '单号不能为空'
            ]);
            $result = ExpressService::query($data['code'], $data['number']);
            empty($result['code']) ? $this->error($result['info']) : $this->success('快递追踪！', $result);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 获取输入订单模型
     * @return PluginWemallOrder
     */
    private function getOrderModel(): PluginWemallOrder
    {
        $map = $this->_vali(['unid.value' => $this->unid, 'order_no.require' => '单号不能为空']);
        $order = PluginWemallOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) $this->error('读取订单失败！');
        return $order;
    }
}