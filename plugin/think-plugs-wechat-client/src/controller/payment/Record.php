<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wechat\client\controller\payment;

use plugin\wechat\client\model\WechatFans;
use plugin\wechat\client\model\WechatPaymentRecord;
use plugin\wechat\client\service\PaymentService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 微信支付行为管理.
 * @class Record
 */
class Record extends Controller
{
    /**
     * 微信支付行为管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        WechatPaymentRecord::mQuery()->layTable(function () {
            $this->title = '支付行为管理';
        }, static function (QueryHelper $query) {
            $fansQuery = WechatFans::mQuery();
            $fansQuery->like('openid|nickname#nickname');
            $db = $fansQuery->db();
            if (!empty($db->getOptions()['where'] ?? [])) {
                $query->whereRaw("openid in {$db->field('openid')->buildSql()}");
            }
            $query->like('order_code|order_name#order')->dateBetween('create_time');
            $query->with(['bindFans']);
            $query->equal('payment_status');
        });
    }

    /**
     * 创建退款申请.
     * @auth true
     */
    public function refund()
    {
        try {
            $data = $this->_vali(['code.require' => '支付号不能为空！']);
            $recode = WechatPaymentRecord::mk()->where($data)->findOrEmpty();
            if ($recode->isEmpty()) {
                $this->error('支付单不存在！');
            }
            if ($recode->getAttr('payment_status') < 1) {
                $this->error('支付单未完成支付！');
            }
            $reason = "来自订单 {$recode['order_code']} 的退款！";
            sysoplog('微信支付退款', "支付单 {$data['code']} 发起退款！");
            [$state, $message] = PaymentService::refund($data['code'], strval($recode->getAttr('payment_amount')), $reason);
            $state ? $this->success($message) : $this->error($message);
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 清理未支付数据.
     * @auth true
     */
    public function clear()
    {
        sysoplog('微信支付清理', '创建粉丝未支付数据清理任务');
        $this->_queue('清理微信未支付数据', 'xadmin:fanspay', 0, [], 600);
    }
}
