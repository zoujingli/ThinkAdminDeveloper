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
use Phinx\Db\Table;
use think\migration\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class InstallWorker20241010 extends Migrator
{
    public function getName(): string
    {
        return 'WorkerPlugin';
    }

    public function change(): void
    {
        $this->createSystemQueue();
    }

    private function createSystemQueue(): void
    {
        $table = $this->table('system_queue', [
            'engine' => 'InnoDB',
            'collation' => 'utf8mb4_general_ci',
            'comment' => 'System queue task table',
        ]);

        $this->upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => false, 'comment' => 'Queue code']],
            ['exec_hash', 'string', ['limit' => 40, 'default' => '', 'null' => false, 'comment' => 'Singleton execution hash']],
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => false, 'comment' => 'Queue title']],
            ['command', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => 'Queue command']],
            ['exec_pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => 'Worker pid']],
            ['exec_data', 'text', ['default' => null, 'null' => true, 'comment' => 'Execution payload']],
            ['exec_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => 'Scheduled time']],
            ['exec_desc', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => 'Execution summary']],
            ['enter_time', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => 'Start time']],
            ['outer_time', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => 'Finish time']],
            ['loops_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => 'Loop interval']],
            ['attempts', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => 'Attempt count']],
            ['message', 'text', ['default' => null, 'null' => true, 'comment' => 'Progress snapshot']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '1 waiting, 2 running, 3 done, 4 failed']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => 'Created at']],
        ], [
            ['columns' => ['code'], 'options' => ['unique' => true, 'name' => 'uq_system_queue_code']],
            ['columns' => ['exec_hash', 'status'], 'options' => ['name' => 'idx_system_queue_hash_status']],
            ['columns' => ['status', 'exec_time', 'id'], 'options' => ['name' => 'idx_system_queue_status_exec']],
            ['columns' => ['status', 'enter_time'], 'options' => ['name' => 'idx_system_queue_status_enter']],
            ['columns' => ['title'], 'options' => ['name' => 'idx_system_queue_title']],
            ['columns' => ['create_time'], 'options' => ['name' => 'idx_system_queue_create_time']],
        ], true);
    }

    /**
     * @param array<int, array<int|string, mixed>> $fields
     * @param array<int, array<int|string, mixed>|string> $indexes
     */
    private function upgrade(Table $table, array $fields, array $indexes = [], bool $force = false): Table
    {
        $existing = [];
        if ($exists = $table->exists()) {
            if (!$force) {
                return $table;
            }

            foreach ($table->getColumns() as $column) {
                $existing[] = $column->getName();
            }
        }

        foreach ($fields as $field) {
            if (in_array($field[0], $existing, true)) {
                $table->changeColumn($field[0], ...array_slice($field, 1));
            } else {
                $table->addColumn($field[0], ...array_slice($field, 1));
            }
        }

        foreach ($indexes as $index) {
            if (is_string($index)) {
                $columns = [$index];
                $options = [];
            } elseif (array_is_list($index) && isset($index[0]) && is_string($index[0])) {
                $columns = $index;
                $options = [];
            } else {
                $columns = array_values((array)($index['columns'] ?? []));
                $options = (array)($index['options'] ?? []);
            }

            if ($columns === [] || ($exists && $table->hasIndex($columns))) {
                continue;
            }

            $table->addIndex($columns, $options);
        }

        $exists ? $table->update() : $table->create();
        if ($table->hasColumn('id')) {
            $table->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
        }

        return $table;
    }
}
