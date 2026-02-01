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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use think\admin\Library;
use think\admin\service\SystemService;
use think\console\Command;
use think\console\input\Option;

/**
 * 数据库备份.
 */
class DbBackupStruct extends Command
{
    /**
     * 配置命令参数。
     */
    public function configure(): void
    {
        $this->setName('xadmin:helper:backup');
        $this->addOption('all', 'a', Option::VALUE_NONE, 'Backup All Tables');
        $this->setDescription('恢复数据前是否强制清空所有表数据');
    }

    /**
     * 指令执行入口.
     */
    public function handle(): void
    {
        $this->backupSchema() && $this->backupTables();
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
     * 备份数据库结构，使用 gzip 压缩保存.
     */
    protected function backupSchema(): bool
    {
        try {
            $outputFile = $this->getSchemaPath();
            if (!($gz = gzopen($outputFile, 'w9'))) {
                $this->output->error("❌ 无法打开压缩文件写入数据库结构：{$outputFile}");
                return false;
            }
            $schema = $this->makeConnect()->createSchemaManager();
            gzwrite($gz, serialize($schema->introspectSchema()));
            gzclose($gz);
            $this->output->info("✅ 数据库结构已压缩保存至：{$outputFile}");
            return true;
        } catch (\Throwable $throwable) {
            $this->output->error("❌ 数据库结构导出失败：{$throwable->getMessage()}");
            return false;
        }
    }

    /**
     * 备份数据表数据，gzip 压缩写入.
     */
    protected function backupTables(): bool
    {
        $backupPath = $this->getBackupPath();
        is_dir(dirname($backupPath)) || mkdir(dirname($backupPath), 0755, true);
        if (!($gz = gzopen($backupPath, 'w9'))) {
            $this->output->error("❌ 无法打开压缩文件写入数据表数据：{$backupPath}");
            return false;
        }
        $force = (bool)$this->input->getOption('all');
        foreach ($this->getBkTables($force) as $table) {
            $total = 0;
            if (!empty($fields = $this->app->db->getFields($table))) {
                $query = $this->app->db->table($table)->order(in_array('id', $fields) ? 'id' : array_values($fields)[0]);
                in_array('ssid', $fields) && $query = $query->where('ssid', '0');
                in_array('deleted_at', $fields) && $query = $query->whereNull('deleted_at');
                $query->chunk(10000, function ($rows) use ($gz, $table, &$total) {
                    foreach ($rows as $row) {
                        $record = ['table' => $table, 'data' => (array)$row];
                        gzwrite($gz, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
                        ++$total;
                    }
                });
            }
            $this->output->writeln("✅ 表 {$table} 备份完成，共 {$total} 行");
        }

        gzclose($gz);
        $this->output->info("📂 表数据已压缩写入：{$backupPath}");
        return true;
    }

    /**
     * 获取需要备份的表.
     */
    protected function getBkTables(bool $all = true): array
    {
        // 接收指定打包数据表
        if ($all) {
            [$tables] = SystemService::getTables();
        } elseif (empty($tables = Library::$sapp->config->get('phinx.tables', []))) {
            $this->output->error('❌ 配置文件未定义数据表列表，请检查配置项：phinx.tables');
            return [];
        }
        return $tables;
    }

    /**
     * 创建连接对接.
     */
    protected function makeConnect(): Connection
    {
        $config = $this->app->db->connect()->getConfig();
        $config['host'] = $config['hostname'] ?? '';
        $config['user'] = $config['username'] ?? '';
        $config['dbname'] = $config['database'] ?? '';
        if (in_array($config['type'], ['mysql', 'sqlite', 'oci'])) {
            $config['driver'] = 'pdo_' . $config['type'];
        }
        return DriverManager::getConnection($config);
    }

    /**
     * 结构文件路径，压缩格式.
     */
    protected function getSchemaPath(): string
    {
        return syspath('database/backup.schema.gz');
    }

    /**
     * 数据备份文件路径，压缩格式.
     */
    protected function getBackupPath(): string
    {
        return syspath('database/backup.data.gz');
    }
}
