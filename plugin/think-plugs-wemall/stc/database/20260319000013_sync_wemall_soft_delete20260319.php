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
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class SyncWemallSoftDelete20260319 extends Migrator
{
    public function getName(): string
    {
        return 'WemallSoftDelete20260319';
    }

    public function up(): void
    {
        foreach ([
            'plugin_wemall_config_agent',
            'plugin_wemall_config_coupon',
            'plugin_wemall_config_discount',
            'plugin_wemall_config_level',
            'plugin_wemall_config_notify',
            'plugin_wemall_config_poster',
            'plugin_wemall_config_rebate',
            'plugin_wemall_express_company',
            'plugin_wemall_express_template',
            'plugin_wemall_goods',
            'plugin_wemall_goods_cate',
            'plugin_wemall_goods_mark',
            'plugin_wemall_goods_stock',
            'plugin_wemall_help_feedback',
            'plugin_wemall_help_problem',
            'plugin_wemall_help_question',
            'plugin_wemall_help_question_x',
            'plugin_wemall_order',
            'plugin_wemall_order_item',
            'plugin_wemall_user_action_comment',
            'plugin_wemall_user_checkin',
            'plugin_wemall_user_coupon',
            'plugin_wemall_user_create',
            'plugin_wemall_user_rebate',
            'plugin_wemall_user_recharge',
        ] as $table) {
            $this->syncSoftDelete($table);
        }
    }

    public function down(): void {}

    private function syncSoftDelete(string $table): void
    {
        $instance = $this->table($table);
        if (!$instance->exists()) {
            return;
        }

        if (!$instance->hasColumn('delete_time')) {
            $instance->addColumn('delete_time', 'datetime', ['default' => null, 'null' => true, 'comment' => '删除时间'])->update();
        }

        $this->migrateDeleteFlag($table, 'deleted');
        $this->migrateDeleteTime($table, 'deleted_at');
        $this->migrateDeleteTime($table, 'deleted_time');

        foreach (['deleted', 'deleted_at', 'deleted_time'] as $column) {
            $this->dropLegacyColumn($table, $column);
        }
    }

    private function migrateDeleteFlag(string $table, string $column): void
    {
        if (!$this->table($table)->hasColumn($column)) {
            return;
        }

        $table = $this->quoteName($table);
        $column = $this->quoteName($column);
        $this->execute("UPDATE {$table} SET `delete_time` = COALESCE(`delete_time`, CURRENT_TIMESTAMP) WHERE {$column} <> 0 AND (`delete_time` IS NULL OR `delete_time` = '')");
    }

    private function migrateDeleteTime(string $table, string $column): void
    {
        if (!$this->table($table)->hasColumn($column)) {
            return;
        }

        $table = $this->quoteName($table);
        $column = $this->quoteName($column);
        $this->execute("UPDATE {$table} SET `delete_time` = {$column} WHERE (`delete_time` IS NULL OR `delete_time` = '') AND {$column} IS NOT NULL AND {$column} <> ''");
    }

    private function dropLegacyColumn(string $table, string $column): void
    {
        $instance = $this->table($table);
        if (!$instance->hasColumn($column)) {
            return;
        }

        $this->dropLegacyIndexes($table, $column);
        $instance = $this->table($table);
        if ($instance->hasColumn($column)) {
            $instance->removeColumn($column)->update();
        }
    }

    private function dropLegacyIndexes(string $table, string $column): void
    {
        try {
            $stmt = $this->query(sprintf('SHOW INDEX FROM %s', $this->quoteName($table)));
            if ($stmt === false) {
                return;
            }
            $indexes = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $name = strval($row['Key_name'] ?? $row['key_name'] ?? '');
                $field = strval($row['Column_name'] ?? $row['column_name'] ?? '');
                if ($name !== '' && strtoupper($name) !== 'PRIMARY' && $field === $column) {
                    $indexes[$name] = true;
                }
            }
            foreach (array_keys($indexes) as $name) {
                $this->execute(sprintf('ALTER TABLE %s DROP INDEX %s', $this->quoteName($table), $this->quoteName($name)));
            }
        } catch (Throwable) {
        }
    }

    private function quoteName(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
