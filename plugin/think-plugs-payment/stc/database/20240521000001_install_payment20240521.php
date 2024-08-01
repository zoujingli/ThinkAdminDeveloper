<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-payment
// | github 代码仓库：https://github.com/zoujingli/think-plugs-payment
// +----------------------------------------------------------------------

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallPayment20240521 extends Migrator
{

    /**
     * 创建数据库
     */
    public function change()
    {
        // 检查与更新数据表
        $this->table('plugin_payment_balance')->hasColumn('amount_prev') || $this->table('plugin_payment_balance')
            ->addColumn('amount_prev', 'decimal', ['after' => 'amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作前金额'])
            ->addColumn('amount_next', 'decimal', ['after' => 'amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作后金额'])
            ->update();

        // 检查与更新数据表
        $this->table('plugin_payment_integral')->hasColumn('amount_prev') || $this->table('plugin_payment_integral')
            ->addColumn('amount_prev', 'decimal', ['after' => 'amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作前金额'])
            ->addColumn('amount_next', 'decimal', ['after' => 'amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作后金额'])
            ->update();

        // 检查与更新数据表
        $this->table('plugin_payment_record')->hasColumn('payment_notify') || $this->table('plugin_payment_record')
            ->addColumn('payment_notify', 'text', ['after' => 'payment_remark', 'default' => NULL, 'null' => true, 'comment' => '支付通知内容'])
            ->addColumn('payment_coupon', 'decimal', ['after' => 'payment_amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '平台优惠券金额'])
            ->addColumn('refund_payment', 'decimal', ['after' => 'refund_amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回金额'])
            ->addColumn('refund_balance', 'decimal', ['after' => 'refund_amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回余额'])
            ->addColumn('refund_integral', 'decimal', ['after' => 'refund_amount', 'precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回积分'])
            ->changeColumn('payment_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付生效时间'])
            ->changeColumn('payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际支付金额'])
            ->changeColumn('refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '累计退款'])
            ->update();
    }
}