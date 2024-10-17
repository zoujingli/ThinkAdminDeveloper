<?php

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class InstallPayment20241010 extends Migrator
{

    /**
     * 获取脚本名称
     * @return string
     */
    public function getName(): string
    {
        return 'PaymentPlugin';
    }

    /**
     * 创建数据库
     */
    public function change()
    {
        $this->_create_plugin_payment_address();
        $this->_create_plugin_payment_balance();
        $this->_create_plugin_payment_config();
        $this->_create_plugin_payment_integral();
        $this->_create_plugin_payment_record();
        $this->_create_plugin_payment_refund();
    }

    /**
     * 创建数据对象
     * @class PluginPaymentAddress
     * @table plugin_payment_address
     * @return void
     */
    private function _create_plugin_payment_address()
    {
        // 创建数据表对象
        $table = $this->table('plugin_payment_address', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-支付-地址',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '主账号ID']],
            ['type', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '默认状态(0普通,1默认)']],
            ['idcode', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '身体证证号']],
            ['idimg1', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '身份证正面']],
            ['idimg2', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '身份证反面']],
            ['user_name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '收货人姓名']],
            ['user_phone', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '收货人手机']],
            ['region_prov', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '地址-省份']],
            ['region_city', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '地址-城市']],
            ['region_area', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '地址-区域']],
            ['region_addr', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '地址-详情']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(1已删,0未删)']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'unid', 'deleted', 'user_phone', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginPaymentBalance
     * @table plugin_payment_balance
     * @return void
     */
    private function _create_plugin_payment_balance()
    {
        // 创建数据表对象
        $table = $this->table('plugin_payment_balance', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-支付-余额',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '账号编号']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作编号']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '操作名称']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '操作备注']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作金额']],
            ['amount_prev', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作前金额']],
            ['amount_next', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作后金额']],
            ['cancel', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '作废状态(0未作废,1已作废)']],
            ['unlock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '解锁状态(0锁定中,1已生效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户']],
            ['cancel_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '作废时间']],
            ['unlock_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '解锁时间']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['deleted_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '删除时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'code', 'cancel', 'unlock', 'deleted', 'create_time', 'deleted_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginPaymentConfig
     * @table plugin_payment_config
     * @return void
     */
    private function _create_plugin_payment_config()
    {
        // 创建数据表对象
        $table = $this->table('plugin_payment_config', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-支付-配置',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '支付类型']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '通道编号']],
            ['name', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '支付名称']],
            ['cover', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '支付图标']],
            ['remark', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '支付说明']],
            ['content', 'text', ['default' => NULL, 'null' => true, 'comment' => '支付参数']],
            ['sort', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '排序权重']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '支付状态(1使用,0禁用)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'type', 'code', 'sort', 'status', 'deleted', 'create_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginPaymentIntegral
     * @table plugin_payment_integral
     * @return void
     */
    private function _create_plugin_payment_integral()
    {
        // 创建数据表对象
        $table = $this->table('plugin_payment_integral', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-支付-积分',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '账号编号']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '操作编号']],
            ['name', 'string', ['limit' => 200, 'default' => '', 'null' => true, 'comment' => '操作名称']],
            ['remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '操作备注']],
            ['amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作金额']],
            ['amount_prev', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作前金额']],
            ['amount_next', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '操作后金额']],
            ['cancel', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '作废状态(0未作废,1已作废)']],
            ['unlock', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '解锁状态(0锁定中,1已生效)']],
            ['deleted', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '删除状态(0未删除,1已删除)']],
            ['create_by', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '系统用户']],
            ['cancel_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '作废时间']],
            ['unlock_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '解锁时间']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['deleted_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '删除时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'code', 'cancel', 'unlock', 'deleted', 'create_time', 'deleted_time',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginPaymentRecord
     * @table plugin_payment_record
     * @return void
     */
    private function _create_plugin_payment_record()
    {
        // 创建数据表对象
        $table = $this->table('plugin_payment_record', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-支付-行为',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '主账号编号']],
            ['usid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '子账号编号']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '发起支付号']],
            ['order_no', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '原订单编号']],
            ['order_name', 'string', ['limit' => 255, 'default' => '', 'null' => true, 'comment' => '原订单标题']],
            ['order_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '原订单金额']],
            ['channel_type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '支付通道类型']],
            ['channel_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '支付通道编号']],
            ['payment_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '支付生效时间']],
            ['payment_trade', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '平台交易编号']],
            ['payment_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未付,1已付,2取消)']],
            ['payment_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '实际支付金额']],
            ['payment_coupon', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '平台优惠券金额']],
            ['payment_images', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '凭证支付图片']],
            ['payment_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '支付状态备注']],
            ['payment_notify', 'text', ['default' => NULL, 'null' => true, 'comment' => '支付通知内容']],
            ['audit_user', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '审核用户(系统用户ID)']],
            ['audit_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '审核时间']],
            ['audit_status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '审核状态(0已拒,1待审,2已审)']],
            ['audit_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '审核描述']],
            ['refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '退款状态(0未退,1已退)']],
            ['refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '累计退款']],
            ['refund_payment', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回金额']],
            ['refund_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回余额']],
            ['refund_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回积分']],
            ['used_payment', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '支付金额']],
            ['used_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '扣除余额']],
            ['used_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '扣除积分']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'usid', 'code', 'order_no', 'create_time', 'audit_status', 'channel_type', 'channel_code', 'payment_trade', 'refund_status', 'payment_status',
        ], true);
    }

    /**
     * 创建数据对象
     * @class PluginPaymentRefund
     * @table plugin_payment_refund
     * @return void
     */
    private function _create_plugin_payment_refund()
    {
        // 创建数据表对象
        $table = $this->table('plugin_payment_refund', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '插件-支付-退款',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade($table, [
            ['unid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '主账号编号']],
            ['usid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '子账号编号']],
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '发起支付号']],
            ['record_code', 'string', ['limit' => 20, 'default' => '', 'null' => true, 'comment' => '子支付编号']],
            ['refund_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '完成时间']],
            ['refund_trade', 'string', ['limit' => 100, 'default' => '', 'null' => true, 'comment' => '交易编号']],
            ['refund_status', 'integer', ['limit' => 1, 'default' => 0, 'null' => true, 'comment' => '支付状态(0未付,1已付,2取消)']],
            ['refund_amount', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退款金额']],
            ['refund_account', 'string', ['limit' => 180, 'default' => '', 'null' => true, 'comment' => '退回账号']],
            ['refund_scode', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '状态编码']],
            ['refund_remark', 'string', ['limit' => 999, 'default' => '', 'null' => true, 'comment' => '退款备注']],
            ['refund_notify', 'text', ['default' => NULL, 'null' => true, 'comment' => '通知内容']],
            ['used_payment', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回金额']],
            ['used_balance', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回余额']],
            ['used_integral', 'decimal', ['precision' => 20, 'scale' => 2, 'default' => '0.00', 'null' => true, 'comment' => '退回积分']],
            ['create_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '创建时间']],
            ['update_time', 'datetime', ['default' => NULL, 'null' => true, 'comment' => '更新时间']],
        ], [
            'unid', 'usid', 'code', 'record_code', 'create_time', 'refund_trade', 'refund_status', 'refund_account',
        ], true);
    }
}
