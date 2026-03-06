<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库: https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class FixWemallConstraints extends Migrator
{
    /**
     * 获取脚本名称。
     */
    public function getName(): string
    {
        return 'FixWemallConstraints';
    }

    /**
     * 修复数据库字段和索引。
     */
    public function change()
    {
        $this->fixPluginWemallUserRebate();
        $this->fixPluginPaymentBalanceIntegral();
        $this->fixPluginWemallUserRelation();
        $this->fixPluginWemallOrder();
    }

    /**
     * 修复返佣记录表。
     */
    private function fixPluginWemallUserRebate(): void
    {
        $table = $this->table('plugin_wemall_user_rebate');

        if (!$table->hasColumn('order_item_id')) {
            $table->addColumn('order_item_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '订单商品项ID']);
        }
        if (!$table->hasColumn('rebate_rule_id')) {
            $table->addColumn('rebate_rule_id', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '返佣规则ID']);
        }
        $table->update();

        $this->executeModifyWithCheck('plugin_wemall_user_rebate', 'amount', "DECIMAL(20,2) NOT NULL DEFAULT '0.00'", 'amount >= 0');
    }

    /**
     * 修复余额和积分表。
     */
    private function fixPluginPaymentBalanceIntegral(): void
    {
        $balance = $this->table('plugin_payment_balance');
        if (!$balance->hasColumn('source_type')) {
            $balance->addColumn('source_type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '资金来源类型']);
            $balance->addColumn('source_id', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '资金来源ID']);
            $balance->update();
        }

        $integral = $this->table('plugin_payment_integral');
        if (!$integral->hasColumn('source_type')) {
            $integral->addColumn('source_type', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '积分来源类型']);
            $integral->addColumn('source_id', 'string', ['limit' => 50, 'default' => '', 'null' => true, 'comment' => '积分来源ID']);
            $integral->update();
        }

        if ($this->isMysql()) {
            $this->execute("ALTER TABLE `plugin_payment_balance` MODIFY `amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00'");
            $this->execute("ALTER TABLE `plugin_payment_integral` MODIFY `amount` DECIMAL(20,2) NOT NULL DEFAULT '0.00'");
        }
    }

    /**
     * 修复用户关系表索引。
     */
    private function fixPluginWemallUserRelation(): void
    {
        $table = $this->table('plugin_wemall_user_relation');
        $updated = false;

        if (!$table->hasIndexByName('idx_path_prefix')) {
            $table->addIndex(['path'], ['name' => 'idx_path_prefix']);
            $updated = true;
        }
        if (!$table->hasIndexByName('idx_puid1')) {
            $table->addIndex(['puid1'], ['name' => 'idx_puid1']);
            $updated = true;
        }
        if (!$table->hasIndexByName('idx_puid2')) {
            $table->addIndex(['puid2'], ['name' => 'idx_puid2']);
            $updated = true;
        }
        if (!$table->hasIndexByName('idx_puid3')) {
            $table->addIndex(['puid3'], ['name' => 'idx_puid3']);
            $updated = true;
        }

        if ($updated) {
            $table->update();
        }
    }

    /**
     * 修复订单表约束。
     */
    private function fixPluginWemallOrder(): void
    {
        foreach ([
            'amount_cost', 'amount_real', 'amount_total', 'amount_goods', 'amount_profit',
            'amount_reduct', 'amount_balance', 'amount_integral', 'amount_payment',
            'amount_express', 'amount_discount', 'coupon_amount', 'allow_balance',
            'allow_integral', 'ratio_integral', 'rebate_amount', 'reward_balance',
            'reward_integral', 'payment_amount',
        ] as $field) {
            $this->executeModifyWithCheck('plugin_wemall_order', $field, "DECIMAL(20,2) NOT NULL DEFAULT '0.00'", "{$field} >= 0");
        }

        $this->executeModifyWithCheck('plugin_wemall_order', 'status', 'TINYINT NOT NULL DEFAULT 1', 'status BETWEEN 0 AND 7');
        $this->executeModifyWithCheck('plugin_wemall_order', 'refund_status', 'TINYINT NOT NULL DEFAULT 0', 'refund_status BETWEEN 0 AND 7');
        $this->executeModifyWithCheck('plugin_wemall_order', 'payment_status', 'TINYINT NOT NULL DEFAULT 0', 'payment_status BETWEEN 0 AND 2');
        $this->executeModifyWithCheck('plugin_wemall_order', 'delivery_type', 'TINYINT NOT NULL DEFAULT 0', 'delivery_type BETWEEN 0 AND 1');
    }

    /**
     * 仅在 MySQL 下执行列定义和检查约束。
     */
    private function executeModifyWithCheck(string $table, string $field, string $definition, string $check): void
    {
        if (!$this->isMysql()) {
            return;
        }

        $sql = "ALTER TABLE `{$table}` MODIFY `{$field}` {$definition}";
        if ($this->supportsCheckConstraint()) {
            $sql .= " CHECK ({$check})";
        }
        $this->execute($sql);
    }

    /**
     * 当前数据库是否支持 MySQL Check 约束。
     */
    private function supportsCheckConstraint(): bool
    {
        static $supports = null;
        if ($supports !== null) {
            return $supports;
        }
        if (!$this->isMysql()) {
            return $supports = false;
        }

        $row = $this->getAdapter()->fetchRow('SELECT VERSION() AS version');
        $raw = (string)($row['version'] ?? reset($row) ?: '0.0.0');
        preg_match('/\d+(?:\.\d+){1,2}/', $raw, $match);
        $version = $match[0] ?? '0.0.0';

        if (stripos($raw, 'mariadb') !== false) {
            return $supports = version_compare($version, '10.2.1', '>=');
        }

        return $supports = version_compare($version, '8.0.16', '>=');
    }

    /**
     * 当前迁移适配器是否为 MySQL。
     */
    private function isMysql(): bool
    {
        return $this->getAdapter()->getAdapterType() === 'mysql';
    }
}
