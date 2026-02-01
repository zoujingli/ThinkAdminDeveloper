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

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallExpressCompany;
use plugin\wemall\model\PluginWemallOrder;
use plugin\wemall\model\PluginWemallOrderRefund;
use plugin\wemall\service\UserRefund;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\Storage;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 订单退货管理.
 * @class Refund
 */
class Refund extends Auth
{
    /**
     * 获取退货订单.
     */
    public function get()
    {
        PluginWemallOrderRefund::mQuery(null, function (QueryHelper $query) {
            $query->equal('code')->in('status')->with([
                'orderinfo' => function (Query $query) {
                    $query->with(['items', 'payments' => function (Query $query) {
                        $query->where(static function (Query $query) {
                            $query->whereOr(['payment_status' => 1, 'audit_status' => 1]);
                        });
                    }]);
                },
            ]);
            $query->where(['unid' => $this->unid])->order('id desc');
            $this->success('获取退货订单！', $query->page(true, false, false, 20));
        });
    }

    /**
     * 创建退货订单.
     * @throws \think\admin\Exception
     */
    public function add()
    {
        $data = $this->_vali([
            'order_no.require' => '订单单号为空！',
            'type.in:1,2' => '类型范围异常！',
            'type.require' => '退货类型为空！',
            'phone.default' => '',
            'images.default' => '',
            'amount.require' => '退款金额为空！',
            'reason.require' => '退货原因为空！',
            'content.default' => '',
        ]);
        // 处理订单数据
        $map = ['order_no' => $data['order_no'], 'unid' => $this->unid];
        $order = PluginWemallOrder::mk()->where($map)->findOrEmpty();
        if ($order->isEmpty()) {
            $this->error('无效订单数据！');
        }
        if ($order->getAttr('refund_status') > 0) {
            $this->error('已发起售后！');
        }
        // 是否已有售后
        $map = ['order_no' => $data['order_no'], 'status' => [1, 2, 3, 4, 5]];
        $refund = PluginWemallOrderRefund::mk()->where($map)->findOrEmpty();
        if ($refund->isExists()) {
            $this->error('已存在售后单！');
        }
        // 上传图片转存
        if (!empty($data['images'])) {
            $images = explode('|', $data['images']);
            foreach ($images as &$image) {
                $image = Storage::saveImage($image, 'feedback')['url'];
            }
            $data['images'] = implode('|', $images);
        }
        $data['unid'] = $this->unid;
        $data['code'] = CodeExtend::uniqidNumber(16, 'R');
        $data['status'] = 2;
        $data['number'] = $order->getAttr('number_goods');
        if (($refund = PluginWemallOrderRefund::mk())->save($data)) {
            $order->save(['refund_status' => $data['status'], 'refund_code' => $data['code']]);
            $this->success('提交成功！', $refund->toArray());
        } else {
            $this->error('提交失败!');
        }
    }

    /**
     * 填写退货物流
     */
    public function express()
    {
        $data = $this->_vali([
            'express_no.require' => '快递单号为空！',
            'express_code.require' => '快递公司为空！',
        ]);
        // 快递公司名称
        $map = ['code' => $data['express_code']];
        $data['express_name'] = PluginWemallExpressCompany::mk()->where($map)->value('name');
        if (empty($data['express_name'])) {
            $this->error('无效快递公司！');
        }
        // 更新售后内容
        self::saveRefund(function (PluginWemallOrderRefund $refund) use ($data) {
            // 流程状态(0已取消,1预订单,2待审核,3待退货,4已退货,5待退款,6已退货,7已完成)
            if ($refund->getAttr('status') < 4) {
                $data['status'] = 4;
            }
            return $data;
        }, '更新成功!');
    }

    /**
     * 取消售后订单.
     */
    public function cancel()
    {
        self::saveRefund(function () {
            return [
                'status' => 0,
                'status_at' => date('Y-m-d H:i:s', time()),
                'status_ds' => '用户主动取消售后！',
            ];
        }, '取消成功！');
    }

    /**
     * 确认售后完成.
     */
    public function confirm()
    {
        self::saveRefund(function () {
            return [
                'status' => 7,
                'status_at' => date('Y-m-d H:i:s'),
                'status_ds' => '用户主动确认完成！',
            ];
        }, '确认完成！');
    }

    /**
     * 获取退货原因.
     */
    public function reasons()
    {
        $this->success('获取退货原因！', UserRefund::reasons);
    }

    /**
     * 获取售后模型.
     */
    private function saveRefund(callable $fn, string $title): void
    {
        try {
            $refund = UserRefund::withRefund($this->_vali([
                'unid.value' => $this->unid,
                'code.require' => '售后单为空！',
            ]), $fn);
            $this->success($title, $refund->toArray());
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
