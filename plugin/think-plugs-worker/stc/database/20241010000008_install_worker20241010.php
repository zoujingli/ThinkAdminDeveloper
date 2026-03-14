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
    /**
     * 获取脚本名称.
     */
    public function getName(): string
    {
        return 'WorkerPlugin';
    }

    /**
     * 创建数据库.
     */
    public function change()
    {
        $this->_create_system_queue();
    }

    /**
     * 创建数据对象
     * @class SystemQueue
     * @table system_queue
     */
    private function _create_system_queue()
    {
        // 创建数据表对象
        $table = $this->table('system_queue', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '系统-任务',
        ]);
        // 创建或更新数据表
        $this->upgrade($table, [
            ['code', 'string', ['limit' => 20, 'default' => '', 'null' => false, 'comment' => '任务编号']],
            ['title', 'string', ['limit' => 100, 'default' => '', 'null' => false, 'comment' => '任务名称']],
            ['command', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '执行指令']],
            ['exec_pid', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '执行进程']],
            ['exec_data', 'text', ['default' => null, 'null' => true, 'comment' => '执行参数']],
            ['exec_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '执行时间']],
            ['exec_desc', 'string', ['limit' => 500, 'default' => '', 'null' => true, 'comment' => '执行描述']],
            ['enter_time', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => '开始时间']],
            ['outer_time', 'decimal', ['precision' => 20, 'scale' => 4, 'default' => '0.0000', 'null' => true, 'comment' => '结束时间']],
            ['loops_time', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '循环时间']],
            ['attempts', 'biginteger', ['limit' => 20, 'default' => 0, 'null' => true, 'comment' => '执行次数']],
            ['message', 'text', ['default' => null, 'null' => true, 'comment' => '最新消息']],
            ['rscript', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '任务类型(0单例,1多例)']],
            ['status', 'integer', ['limit' => 1, 'default' => 1, 'null' => true, 'comment' => '任务状态(1新任务,2处理中,3成功,4失败)']],
            ['create_time', 'timestamp', ['default' => 'CURRENT_TIMESTAMP', 'null' => false, 'comment' => '创建时间']],
        ], [
            'code', 'title', 'status', 'rscript', 'create_time', 'exec_time',
        ], true);
    }

    /**
     * 升级更新数据表.
     * @param array<int, array<int|string, mixed>> $fields
     * @param array<int, array<int, string>|string> $indexs
     */
    private function upgrade(Table $table, array $fields, array $indexs = [], bool $force = false): Table
    {
        [$_exists, $_fields] = [[], array_column($fields, 0)];
        if ($isExists = $table->exists()) {
            if (!$force) {
                return $table;
            }
            foreach ($table->getColumns() as $column) {
                $_exists[] = $column->getName();
            }
        }
        foreach ($fields as $field) {
            if (in_array($field[0], $_exists, true)) {
                $table->changeColumn($field[0], ...array_slice($field, 1));
            } else {
                $table->addColumn($field[0], ...array_slice($field, 1));
            }
        }
        foreach ($indexs as $field) {
            $columns = is_array($field) ? $field : [$field];
            if (empty($columns) || (!empty($isExists) && $table->hasIndex($columns))) {
                continue;
            }
            $table->addIndex($columns);
        }
        $isExists ? $table->update() : $table->create();
        if ($table->hasColumn('id')) {
            $table->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
        }
        return $table;
    }
}
