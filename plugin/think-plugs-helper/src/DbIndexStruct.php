<?php

// +----------------------------------------------------------------------
// | Developer Tools for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-helper
// | github 代码仓库：https://github.com/zoujingli/think-plugs-helper
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\helper;

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
        $this->setName("xadmin:helper:index");
        $this->setDescription("刷新数据库的结构索引");
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
        [$tables, $total, $count] = SystemService::getTables();
        foreach ($tables as $table) {
            $this->output->writeln(sprintf("[%s/%s] 开始处理表 {$table}", $count++, $total));
            foreach (Db::query(sprintf('SHOW INDEX FROM `%s`', $table)) as $index) {
                $keyName = $index['Key_name'] ?? '';
                if ($keyName === 'PRIMARY') {
                    continue;
                }
                $newName = $this->genIndexName($table, (array)$index);
                if ($keyName === $newName) {
                    continue;
                }
                Db::execute(sprintf('ALTER TABLE `%s` RENAME INDEX `%s` TO `%s`', $table, $keyName, $newName));
                ++$count;
            }
        }
        $this->output->writeln("✅ 完成 {$count} 个索引重命名");
    }

    /**
     * 生成索引名称.
     * @param string $table 表名
     * @param array $index 索引信息
     * @return string 生成的索引名称
     */
    private function genIndexName(string $table, array $index): string
    {
        $abbr = implode('', array_map(function ($word) {
            return $word[0];
        }, explode('_', $table)));
        return ($index['Non_unique'] ? 'idx_' : 'uni_') . $abbr . '_' . substr(md5($table), -4) . '_' . $index['Column_name'];
    }
}
