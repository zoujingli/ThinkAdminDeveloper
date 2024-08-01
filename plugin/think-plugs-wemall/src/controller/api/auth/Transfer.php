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

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\model\PluginWemallUserTransfer;
use plugin\wemall\service\UserRebate;
use plugin\wemall\service\UserTransfer;
use think\admin\extend\CodeExtend;

/**
 * 用户提现接口
 * @class Transfer
 * @package plugin\wemall\controller\api\auth
 */
class Transfer extends Auth
{
    /**
     * 提交提现处理
     * @throws \think\admin\Exception
     * @throws \think\db\exception\DbException
     */
    public function add()
    {
        // 检查用户状态，接收输入数据
        $data = $this->_vali([
            'type.require'   => '提现方式为空！',
            'amount.require' => '提现金额为空！',
            'remark.default' => '用户提交提现申请！',
        ]);
        $state = UserTransfer::config('status');
        if (empty($state)) $this->error('提现还没有开启！');
        $transfers = UserTransfer::config('transfer');
        if (empty($transfers[$data['type']]['state'])) $this->error('提现方式已停用！');
        // 提现数据补充
        $data['unid'] = $this->unid;
        $data['date'] = date('Y-m-d');
        $data['code'] = CodeExtend::uniqidDate(16, 'T');
        // 提现状态处理
        if (empty($transfers[$data['type']]['state']['audit'])) {
            $data['status'] = 1;
            $data['audit_status'] = 0;
        } else {
            $data['status'] = 3;
            $data['audit_status'] = 1;
            $data['audit_remark'] = '提现免审核！';
            $data['audit_time'] = date('Y-m-d H:i:s');
        }
        // 扣除手续费
        $chargeRate = floatval(UserTransfer::config('charge'));
        $data['charge_rate'] = $chargeRate;
        $data['charge_amount'] = $chargeRate * $data['amount'] / 100;
        // 检查可提现余额
        [$total, $count] = UserRebate::recount($this->unid);
        if (round($total - $count, 2) < $data['amount']) $this->error('可提现余额不足！');
        // 提现方式处理
        if ($data['type'] == 'alipay_account') {
            $data = array_merge($data, $this->_vali([
                'alipay_user.require' => '开户姓名为空！',
                'alipay_code.require' => '支付账号为空！',
            ]));
        } elseif (in_array($data['type'], ['wechat_qrcode', 'alipay_qrcode'])) {
            $data = array_merge($data, $this->_vali([
                'qrcode.require' => '收款码不能为空！',
            ]));
        } elseif (in_array($data['type'], ['wechat_banks', 'transfer_banks'])) {
            $data = array_merge($data, $this->_vali([
                'bank_wseq.require' => '银行编号为空！',
                'bank_name.require' => '银行名称为空！',
                'bank_user.require' => '开户账号为空！',
                'bank_bran.require' => '银行分行为空！',
                'bank_code.require' => '银行卡号为空！',
            ]));
        } elseif ($data['type'] === 'wechat_wallet') {
            $account = $this->account->get();
            $data['appid'] = $account['appid'];
            $data['openid'] = $account['openid'];
            if (empty($data['appid']) || empty($data['openid'])) {
                $this->error('未绑定微信！');
            }
        } else {
            $this->error('转账方式不存在！');
        }
        // 当日提现次数限制
        $map = ['unid' => $this->unid, 'type' => $data['type'], 'date' => $data['date']];
        $count = PluginWemallUserTransfer::mk()->where($map)->count();
        if ($count >= $transfers[$data['type']]['dayNumber']) $this->error("当日提现次数受限");
        // 提现金额范围控制
        if ($transfers[$data['type']]['minAmount'] > $data['amount']) {
            $this->error("不能少于{$transfers[$data['type']]['minAmount']}元");
        }
        if ($transfers[$data['type']]['maxAmount'] < $data['amount']) {
            $this->error("不能大于{$transfers[$data['type']]['maxAmount']}元");
        }
        // 写入用户提现数据
        if (PluginWemallUserTransfer::mk()->save($data)) {
            UserRebate::recount($this->unid);
            $this->success('提现申请成功');
        } else {
            $this->error('提现申请失败');
        }
    }

    /**
     * 用户提现记录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get()
    {
        $query = PluginWemallUserTransfer::mQuery()->where(['unid' => $this->unid]);
        $result = $query->like('date,code,code#keys')->in('status')->order('id desc')->page(true, false, false, 10);
        // 统计历史数据
        $map = [['unid', '=', $this->unid], ['status', '>', 0]];
        [$total, $count, $locks, $usable] = UserRebate::recount($this->unid);
        $this->success('获取提现成功', array_merge($result, [
            'total' => [
                '累计' => $total,
                '已提' => $count,
                '锁定' => $locks,
                '可提' => $usable,
                '上月' => PluginWemallUserTransfer::mk()->where($map)->whereLike('date', date("Y-m-%", strtotime('-1 month')))->sum('amount'),
                '本月' => PluginWemallUserTransfer::mk()->where($map)->whereLike('date', date("Y-m-%"))->sum('amount'),
                '全年' => PluginWemallUserTransfer::mk()->where($map)->whereLike('date', date("Y-%"))->sum('amount'),
            ],
        ]));
    }

    /**
     * 用户取消提现
     */
    public function cancel()
    {
        $data = $this->_vali(['unid.value' => $this->unid, 'code.require' => '单号不能为空！']);
        PluginWemallUserTransfer::mk()->where($data)->whereIn('status', [1, 2, 3])->update([
            'status' => 0, 'change_time' => date("Y-m-d H:i:s"), 'change_desc' => '用户主动取消提现',
        ]);
        UserRebate::recount($this->unid);
        $this->success('取消提现成功');
    }

    /**
     * 用户确认提现
     */
    public function confirm()
    {
        $data = $this->_vali(['unid.value' => $this->unid, 'code.require' => '单号不能为空！']);
        PluginWemallUserTransfer::mk()->where($data)->whereIn('status', [4])->update([
            'status' => 5, 'change_time' => date("Y-m-d H:i:s"), 'change_desc' => '用户主动确认收款',
        ]);
        UserRebate::recount($this->unid);
        $this->success('确认收款成功');
    }

    /**
     * 获取用户提现配置
     * @throws \think\admin\Exception
     */
    public function config()
    {
        $data = UserTransfer::config();
        $this->success('获取用户提现配置', $data);
    }
}