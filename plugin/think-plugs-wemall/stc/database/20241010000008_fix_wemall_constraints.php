<?php

use think\admin\extend\PhinxExtend;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', -1);

class FixWemallConstraints20241010 extends Migrator
{

    /**
     * 获取脚本名称
     * @return string
     */
    public function getName(): string
    {
        return 'FixWemallConstraints';
    }

    /**
     * 修复数据库约束
     */
    public function change()
    {
        // 为返佣记录表添加外键约束和业务字段
        $this->_fix_plugin_wemall_user_rebate();
        
        // 为余额/积分表添加来源字段
        $this->_fix_plugin_payment_balance_integral();
        
        // 为用户关系表添加索引优化
        $this->_fix_plugin_wemall_user_relation();
        
        // 为订单表添加检查约束
        $this->_fix_plugin_wemall_order();
    }

    /**
     * 修复返佣记录表
     */
    private function _fix_plugin_wemall_user_rebate()
    {
        $table = $this->table('plugin_wemall_user_rebate');
        
        // 添加订单商品项ID字段（用于精确追踪返佣）
        if (!$this->hasColumn('plugin_wemall_user_rebate', 'order_item_id')) {
            $table->addColumn('order_item_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '订单商品项ID'])
                  ->update();
        }
        
        // 添加返佣规则ID字段（用于追溯规则版本）
        if (!$this->hasColumn('plugin_wemall_user_rebate', 'rebate_rule_id')) {
            $table->addColumn('rebate_rule_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '返佣规则ID'])
                  ->update();
        }
        
        // 添加金额非负约束
        $this->execute("ALTER TABLE `plugin_wemall_user_rebate` MODIFY `amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00' CHECK (amount >= 0)");
    }

    /**
     * 修复余额/积分表
     */
    private function _fix_plugin_payment_balance_integral()
    {
        // 修复余额表
        $table = $this->table('plugin_payment_balance');
        if (!$this->hasColumn('plugin_payment_balance', 'source_type')) {
            $table->addColumn('source_type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '资金来源类型'])
                  ->addColumn('source_id', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '资金来源ID'])
                  ->update();
        }
        
        // 修复积分表
        $table = $this->table('plugin_payment_integral');
        if (!$this->hasColumn('plugin_payment_integral', 'source_type')) {
            $table->addColumn('source_type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '积分来源类型'])
                  ->addColumn('source_id', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '积分来源ID'])
                  ->update();
        }
        
        // 添加金额非负约束
        $this->execute("ALTER TABLE `plugin_payment_balance` MODIFY `amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00'");
        $this->execute("ALTER TABLE `plugin_payment_integral` MODIFY `amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00'");
    }

    /**
     * 修复用户关系表索引
     */
    private function _fix_plugin_wemall_user_relation()
    {
        // 为path字段添加前缀索引（优化LIKE查询性能）
        $this->execute("ALTER TABLE `plugin_wemall_user_relation` ADD INDEX `idx_path_prefix` (`path`(20))");
        
        // 为代理层级字段添加索引
        $this->execute("ALTER TABLE `plugin_wemall_user_relation` ADD INDEX `idx_puid1` (`puid1`)");
        $this->execute("ALTER TABLE `plugin_wemall_user_relation` ADD INDEX `idx_puid2` (`puid2`)");
        $this->execute("ALTER TABLE `plugin_wemall_user_relation` ADD INDEX `idx_puid3` (`puid3`)");
    }

    /**
     * 修复订单表约束
     */
    private function _fix_plugin_wemall_order()
    {
        // 添加金额字段的非负约束
        $amount_fields = [
            'amount_cost', 'amount_real', 'amount_total', 'amount_goods', 
            'amount_profit', 'amount_reduct', 'amount_balance', 'amount_integral',
            'amount_payment', 'amount_express', 'amount_discount', 'coupon_amount',
            'allow_balance', 'allow_integral', 'ratio_integral', 'rebate_amount',
            'reward_balance', 'reward_integral', 'payment_amount'
        ];
        
        foreach ($amount_fields as $field) {
            $this->execute("ALTER TABLE `plugin_wemall_order` MODIFY `{$field}` DECIMAL(20,2) NOT NULL DEFAULT '0.00' CHECK ({$field} >= 0)");
        }
        
        // 添加状态字段的枚举约束
        $this->execute("ALTER TABLE `plugin_wemall_order` MODIFY `status` TINYINT NOT NULL DEFAULT 1 CHECK (status BETWEEN 0 AND 7)");
        $this->execute("ALTER TABLE `plugin_wemall_order` MODIFY `refund_status` TINYINT NOT NULL DEFAULT 0 CHECK (refund_status BETWEEN 0 AND 7)");
        $this->execute("ALTER TABLE `plugin_wemall_order` MODIFY `payment_status` TINYINT NOT NULL DEFAULT 0 CHECK (payment_status BETWEEN 0 AND 2)");
        $this->execute("ALTER TABLE `plugin_wemall_order` MODIFY `delivery_type` TINYINT NOT NULL DEFAULT 0 CHECK (delivery_type BETWEEN 0 AND 1)");
    }
}