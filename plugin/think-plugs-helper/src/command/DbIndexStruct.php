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

namespace plugin\helper\command;

use plugin\helper\service\IndexNameService;
use plugin\system\service\SystemService;
use think\admin\service\RuntimeService;
use think\console\Command;
use think\facade\Db;

class DbIndexStruct extends Command
{
    /**
     * 配置索引同步命令。
     */
    public function configure(): void
    {
        $this->setName('xadmin:helper:index');
        $this->setDescription('同步数据库索引命名规范');
    }

    /**
     * 仅在调试模式下允许执行。
     */
    public function isEnabled(): bool
    {
        return RuntimeService::isDebug();
    }

    /**
     * 遍历全部数据表并规范索引名称，支持复合索引。
     */
    public function handle(): void
    {
        if (strtolower(strval($this->app->db->connect()->getConfig('type', ''))) !== 'mysql') {
            $this->output->writeln('Skip index sync: current database driver does not support SHOW INDEX rename flow.');
            return;
        }

        [$tables, $total, $count] = SystemService::getTables();
        foreach ($tables as $table) {
            $this->output->writeln(sprintf('[%s/%s] 开始处理表 %s', $count++, $total, $table));

            $indexes = [];
            foreach (Db::query(sprintf('SHOW INDEX FROM `%s`', $table)) as $index) {
                $keyName = strval($index['Key_name'] ?? '');
                if ($keyName === '' || $keyName === 'PRIMARY') {
                    continue;
                }

                $indexes[$keyName]['non_unique'] = intval($index['Non_unique'] ?? 1);
                $indexes[$keyName]['columns'][intval($index['Seq_in_index'] ?? 0)] = strval($index['Column_name'] ?? '');
            }

            $existingNames = array_fill_keys(array_keys($indexes), true);
            foreach ($indexes as $keyName => $data) {
                $columns = $data['columns'] ?? [];
                ksort($columns);
                $columns = array_values(array_filter($columns, 'strlen'));
                if ($columns === []) {
                    continue;
                }

                $newName = IndexNameService::generate($table, $columns, intval($data['non_unique'] ?? 1) === 0);
                if ($keyName === $newName || isset($existingNames[$newName])) {
                    continue;
                }

                Db::execute(sprintf('ALTER TABLE `%s` RENAME INDEX `%s` TO `%s`', $table, $keyName, $newName));
                $existingNames[$newName] = true;
                ++$count;
            }
        }

        $this->output->writeln("完成索引处理，共计 {$count} 项。");
    }
}
