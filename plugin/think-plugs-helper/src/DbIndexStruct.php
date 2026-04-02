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

namespace plugin\helper;

use think\admin\extend\IndexNameService;
use think\admin\service\SystemService;
use think\console\Command;
use think\facade\Db;

class DbIndexStruct extends Command
{
    /**
     * 配置命令参数。
     */
    public function configure(): void
    {
        $this->setName('xadmin:helper:index');
        $this->setDescription('刷新数据库的结构索引');
    }

    /**
     * 检查是否允许执行任务
     * @return bool 返回true表示允许执行
     */
    public function isEnabled(): bool
    {
        return SystemService::isDebug();
    }

    /**
     * 命令执行入口
     * 遍历数据库表并重命名索引.
     */
    public function handle(): void
    {
        [$tables, $total] = SystemService::getTables();
        $number = 1;
        $renamed = 0;
        foreach ($tables as $table) {
            $this->output->writeln(sprintf("[%s/%s] 开始处理表 %s", $number++, $total, $table));
            $indexes = [];
            foreach (Db::query(sprintf('SHOW INDEX FROM `%s`', $table)) as $index) {
                $keyName = strval($index['Key_name'] ?? '');
                if ($keyName === '' || $keyName === 'PRIMARY') {
                    continue;
                }
                $indexes[$keyName]['unique'] = intval($index['Non_unique'] ?? 1) === 0;
                $indexes[$keyName]['columns'][intval($index['Seq_in_index'] ?? 0)] = strval($index['Column_name'] ?? '');
            }
            $exists = array_fill_keys(array_keys($indexes), true);
            foreach ($indexes as $keyName => $index) {
                $columns = $index['columns'] ?? [];
                ksort($columns);
                $columns = array_values(array_filter($columns, 'strlen'));
                if (empty($columns)) {
                    continue;
                }
                $newName = $this->genIndexName($table, $columns, !empty($index['unique']));
                if ($keyName === $newName || isset($exists[$newName])) {
                    continue;
                }
                Db::execute(sprintf('ALTER TABLE `%s` RENAME INDEX `%s` TO `%s`', $table, $keyName, $newName));
                $exists[$newName] = true;
                ++$renamed;
            }
        }
        $this->output->writeln("✅ 完成 {$renamed} 个索引重命名");
    }

    /**
     * 生成索引名称.
     * @param string $table 表名
     * @param array<int, string>|string $columns 索引字段
     * @return string 生成的索引名称
     */
    private function genIndexName(string $table, array|string $columns, bool $unique = false): string
    {
        return IndexNameService::generate($table, $columns, $unique);
    }
}
