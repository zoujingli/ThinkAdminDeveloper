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

namespace plugin\payment\controller;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentRecord;
use plugin\payment\service\Payment;
use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\extend\CodeToolkit;
use think\admin\helper\FormBuilder;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\exception\HttpResponseException;

/**
 * 支付行为管理.
 * @class Record
 */
class Record extends Controller
{
    /**
     * 支付行为管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->mode = $this->get['open_type'] ?? 'index';
        PluginPaymentRecord::mQuery()->layTable(function () {
            if ($this->mode === 'index') {
                $this->title = '支付行为管理';
            }
        }, static function (QueryHelper $query) {
            $db = PluginAccountUser::mQuery()->like('email|nickname|username|phone#userinfo')->db();
            if (!empty($db->getOptions()['where'] ?? [])) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
            $query->with(['user'])->like('order_no|order_name#orderinfo')->dateBetween('create_time');
        });
    }

    /**
     * 单据凭证审核.
     * @auth true
     */
    public function audit()
    {
        if ($this->request->isGet()) {
            $this->buildAuditForm()->fetch([
                'vo' => $this->loadAuditRecord(intval($this->request->param('id', 0))),
            ]);
        }

        $data = $this->buildAuditForm()->validate();
        $data['id'] = intval($this->request->param('id', 0));
        if (intval($data['status']) === 1) {
            $this->error('请选择通过或驳回！');
        }
        $action = PluginPaymentRecord::mk()->findOrEmpty($data['id']);
        if ($action->isEmpty()) {
            $this->error('支付记录不存在！');
        }
        if ($action->getAttr('channel_type') !== Payment::VOUCHER) {
            $this->error('无需审核操作！');
        }
        if ($action->getAttr('payment_status') === 1) {
            $this->success('该凭证已审核！');
        }
        $data['audit_user'] = SystemAuthService::getUserId();
        $data['audit_time'] = date('Y-m-d H:i:s');
        $data['audit_remark'] = $data['remark'];
        $data['payment_time'] = date('Y-m-d H:i:s');
        $data['payment_trade'] = CodeToolkit::uniqidNumber(18, 'AU');
        if (empty($data['status'])) {
            $data['audit_status'] = 0;
            $data['payment_status'] = 0;
            $data['payment_remark'] = $data['remark'] ?: '后台支付凭证被驳回';
        } else {
            $data['audit_status'] = 2;
            $data['payment_status'] = 1;
            $data['payment_remark'] = $data['remark'] ?: '后台支付凭证已通过';
        }
        if ($action->save($data)) {
            if (empty($data['status'])) {
                $this->app->event->trigger('PluginPaymentRefuse', $action->refresh());
                $this->success('凭证审核驳回！');
            } else {
                $this->app->event->trigger('PluginPaymentSuccess', $action->refresh());
                $this->success('凭证审核通过！');
            }
        } else {
            $this->error('凭证审核失败！');
        }
    }

    /**
     * 取消支付订单.
     * @auth true
     */
    public function cancel()
    {
        try {
            $data = $this->_vali(['code.require' => '支付单号不能为空！']);
            $items = PluginPaymentRecord::mk()->where(function (Query $query) {
                $query->whereOr([['payment_status', '=', 1], ['audit_status', '>', 0]]);
            })->where($data)->column('code,channel_code,payment_amount,payment_coupon');
            foreach ($items as $item) {
                $amount = bcsub(strval($item['payment_amount']), strval($item['payment_coupon']), 2);
                Payment::mk($item['channel_code'])->refund($item['code'], $amount);
            }
            $this->success('退款申请成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 重新触发支付行为.
     * @auth true
     */
    public function notify()
    {
        $data = $this->_vali(['code.require' => '支付单号不能为空！']);
        $record = PluginPaymentRecord::mk()->where(['code' => $data['code']])->findOrEmpty();
        if ($record->isEmpty()) {
            $this->error('支付单号异常！');
        }
        if (empty($record->getAttr('payment_status'))) {
            $this->error('未完成支付！');
        }
        $this->app->event->trigger('PluginPaymentSuccess', $record);
        $this->success('重新触发支付行为！');
    }

    private function buildAuditForm(): FormBuilder
    {
        $id = intval($this->request->param('id', 0));
        $voucherRemark = <<<'HTML'
<div class="layui-textarea"><img alt="img" data-tips-image src="{$vo.payment_images|default=''}" style="width:auto;height:220px"></div>
HTML;

        return FormBuilder::mk()
            ->setAction(url('audit', array_filter(['id' => $id ?: null]))->build())
            ->addTextInput('order_no_display', '业务单号', 'Order No.', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('code_display', '交易单号', 'Payment No.', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('payment_amount_display', '交易金额', 'Payment Amount', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('payment_images_display', '支付单据凭证', 'Payment Voucher', false, $voucherRemark, null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addField([
                'type' => 'radio',
                'name' => 'status',
                'title' => '审核操作类型',
                'subtitle' => 'Audit Status',
                'required' => true,
                'options' => [0 => '驳回凭证', 1 => '等待审核', 2 => '审核通过'],
            ])
            ->addTextArea('remark', '订单审核描述', 'Audit Remark', false, '', [
                'placeholder' => '请输入订单审核描述',
            ])
            ->addSubmitButton()
            ->addCancelButton('取消操作', '确定要取消吗？');
    }

    private function loadAuditRecord(int $id): array
    {
        if ($id < 1) {
            $this->error('支付号不能为空！');
        }
        $record = PluginPaymentRecord::mk()->findOrEmpty($id);
        if ($record->isEmpty()) {
            $this->error('支付记录不存在！');
        }
        $data = $record->toArray();
        $data['order_no_display'] = strval($data['order_no'] ?? '');
        $data['code_display'] = strval($data['code'] ?? '');
        $data['payment_amount_display'] = strval(($data['payment_amount'] ?? '0') + 0) . ' 元';
        $data['payment_images_display'] = strval($data['payment_images'] ?? '');
        $data['remark'] = strval($data['remark'] ?? ($data['audit_remark'] ?? '支付凭证已查验'));
        $data['status'] = intval($data['audit_status'] ?? 1);
        return $data;
    }
}
