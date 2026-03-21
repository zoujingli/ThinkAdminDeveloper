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

namespace plugin\helper\command\database;

use plugin\system\service\SystemService;
use think\admin\Command;
use think\admin\Exception;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\db\PDOConnection;

/**
 * 数据库修复优化命令。
 */
class DatabaseCommand extends Command
{
    /**
     * 指令任务配置.
     */
    public function configure()
    {
        $this->setName('xadmin:database');
        $this->addArgument('action', Argument::OPTIONAL, 'repair|optimize', 'optimize');
        $this->setDescription('Database Optimize and Repair for ThinkAdmin');
    }

    /**
     * 任务执行入口.
     * @throws Exception
     */
    protected function execute(Input $input, Output $output): int
    {
        if ($this->app->db->connect()->getConfig('type') === 'sqlite') {
            $this->setQueueError('Sqlite 数据库不支持 REPAIR 和 OPTIMIZE 操作！');
        }
        $action = $input->getArgument('action');
        if (method_exists($this, $method = "_{$action}")) {
            $this->{$method}();
        } else {
            $this->output->error('Wrong operation, currently allow repair|optimize');
        }
        return 0;
    }

    /**
     * 修复所有数据表.
     * @throws Exception
     */
    protected function _repair(): void
    {
        $connection = $this->app->db->connect();
        if (!$connection instanceof PDOConnection) {
            throw new Exception('当前数据库连接不支持 REPAIR 操作');
        }
        $this->setQueueProgress('正在获取需要修复的数据表', '0');
        [$tables, $total, $count] = SystemService::getTables();
        $this->setQueueProgress("总共需要修复 {$total} 张数据表", '0');
        foreach ($tables as $table) {
            $this->setQueueMessage($total, ++$count, "正在修复数据表 {$table}");
            $connection->query("REPAIR TABLE `{$table}`");
            $this->setQueueMessage($total, $count, "完成修复数据表 {$table}", 1);
        }
        $this->setQueueSuccess("已完成对 {$total} 张数据表修复操作");
    }

    /**
     * 优化所有数据表.
     * @throws Exception
     */
    protected function _optimize(): void
    {
        $connection = $this->app->db->connect();
        if (!$connection instanceof PDOConnection) {
            throw new Exception('当前数据库连接不支持 OPTIMIZE 操作');
        }
        $this->setQueueProgress('正在获取需要优化的数据表', '0');
        [$tables, $total, $count] = SystemService::getTables();
        $this->setQueueProgress("总共需要优化 {$total} 张数据表", '0');
        foreach ($tables as $table) {
            $this->setQueueMessage($total, ++$count, "正在优化数据表 {$table}");
            $connection->query("OPTIMIZE TABLE `{$table}`");
            $this->setQueueMessage($total, $count, "完成优化数据表 {$table}", 1);
        }
        $this->setQueueSuccess("已完成对 {$total} 张数据表优化操作");
    }
}
