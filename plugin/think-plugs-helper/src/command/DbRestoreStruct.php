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

namespace plugin\helper\command;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use plugin\system\model\SystemUser;
use think\admin\service\RuntimeService;
use think\console\input\Option;

/**
 * 数据库结构与数据恢复命令。
 * 支持恢复 Doctrine Schema 结构及按行压缩备份的数据。
 */
class DbRestoreStruct extends DbBackupStruct
{
    /**
     * 配置命令参数。
     */
    public function configure(): void
    {
        $this->setName('xadmin:helper:restore');
        $this->addOption('force', 'f', Option::VALUE_NONE, 'Force All Update');
        $this->setDescription('恢复数据前是否强制清空所有表数据');
    }

    /**
     * 命令执行入口。
     * @throws Exception
     */
    public function handle(): void
    {
        $this->restoreSchema() && $this->restoreBackup();
    }

    /**
     * 检查是否允许执行任务
     * @return bool 返回true表示允许执行
     */
    public function isEnabled(): bool
    {
        return RuntimeService::isDebug();
    }

    /**
     * 恢复数据库结构。
     * @throws Exception
     */
    protected function restoreSchema(): bool
    {
        if (!is_file($gzPath = self::getSchemaPath())) {
            $this->output->error("❌ 结构文件不存在：{$gzPath}");
            return false;
        }

        $content = @gzdecode(file_get_contents($gzPath));
        if (empty($content) || !($backupSchema = @unserialize($content)) instanceof Schema) {
            $this->output->error("❌ 解压或反序列化失败：{$gzPath}");
            return false;
        }

        $connect = self::makeConnect();
        $platform = $connect->getDatabasePlatform();
        $diff = (new Comparator($platform))->compareSchemas(
            $connect->createSchemaManager()->introspectSchema(),
            $backupSchema
        );

        $sqls = [];
        foreach ($diff->getCreatedTables() as $t) {
            $sqls = [...$sqls, ...$platform->getCreateTableSQL($t)];
        }
        foreach ($diff->getAlteredTables() as $t) {
            $sqls = [...$sqls, ...$platform->getAlterTableSQL($t)];
        }
        foreach ($diff->getDroppedTables() as $t) {
            $sqls[] = $platform->getDropTableSQL($t->getName());
        }

        if (!$sqls) {
            $this->output->info('✅ 数据库结构已一致，无需变更。');
            return true;
        }

        try {
            foreach ($sqls as $sql) {
                $this->output->writeln("🔧 执行 SQL：{$sql}");
                $connect->executeStatement($sql);
            }
            $this->output->info('✅ 数据库结构同步完成。');
            return true;
        } catch (\Throwable $throwable) {
            $this->output->error('❌ 结构同步失败：' . $throwable->getMessage());
            return false;
        }
    }

    /**
     * 恢复业务数据。
     * @throws Exception
     */
    protected function restoreBackup(): bool
    {
        $force = (bool)$this->input->getOption('force');
        if (empty($tables = $this->getBkTables($force))) {
            $this->output->error('❌ 未定义需恢复的数据表');
            return false;
        }

        if (!file_exists($path = $this->getBackupPath())) {
            $this->output->error("❌ 备份数据文件不存在：{$path}");
            return false;
        }

        copy($path, $tmp = syspath('runtime/backup_data_tmp.gz'));
        $schemaManager = $this->makeConnect()->createSchemaManager();

        // 先清空 forceCleanTables 表，无需 count 检查
        $forceCleanTables = ['system_menu', 'system_dict_data', 'system_dict_type'];
        foreach (array_intersect($forceCleanTables, $tables) as $table) {
            _query($table)->empty();
            $this->output->writeln("🧹 强制清空表：<info>{$table}</info>");
        }

        if ($force) {
            // 如果是强制恢复，需要清空所有表（非 forceCleanTables 表）
            foreach ($schemaManager->listTableNames() as $table) {
                if (!in_array($table, $forceCleanTables, true)) {
                    _query($table)->empty();
                    $this->output->writeln("✅ 已经清空表：<info>{$table}</info>");
                }
            }
        }

        // 计算需要恢复的数据表
        $restoreTableFlags = [];
        foreach ($tables as $table) {
            $restoreTableFlags[$table] = $force || in_array($table, $forceCleanTables, true) || $this->app->db->table($table)->count() === 0;
        }

        if (!($fp = gzopen($tmp, 'rb'))) {
            $this->output->writeln("❌ 无法打开备份文件：{$tmp}");
            return false;
        }

        $totalLines = 0;
        $currentLine = 0;
        $batchInsert = [];
        $insertCount = array_fill_keys($tables, 0);

        try {
            while (!gzeof($fp)) {
                ++$totalLines;
                $row = json_decode(trim(gzgets($fp)), true);
                $table = $row['table'] ?? '-';

                // 判断是否跳过恢复，非强制恢复时，根据 tableFlags 决定是否插入
                if (empty($restoreTableFlags[$table]) || empty($row['data']) || !is_array($row['data'])) {
                    continue;
                }

                ++$currentLine;
                ++$insertCount[$table];
                $batchInsert[$table][] = $row['data'];
                if (count($batchInsert[$table]) >= 1000) {
                    $this->flushBatchInsert($table, $batchInsert[$table]);
                    $this->output->writeln("📥 表 {$table} 批量插入 1000 行，已读取 {$totalLines} 行");
                }
            }

            // 插入剩余数据
            foreach ($batchInsert as $table => $rows) {
                if ($count = count($rows)) {
                    $this->flushBatchInsert($table, $rows);
                    $this->output->writeln("📥 表 {$table} 批量插入 {$count} 行，已读取 {$totalLines} 行");
                }
            }
            $this->output->writeln("✅ 数据恢复完成，共插入 {$currentLine} 行（读取 {$totalLines} 行）");
            foreach ($insertCount as $table => $count) {
                $count > 0 && $this->output->writeln("✅ 表 {$table} 插入 {$count} 行");
            }
            @unlink($tmp);

            // 恢复管理员数据
            $this->insertSuperUser();

            // 清理系统运行缓存
            return RuntimeService::clear(false);
        } catch (\Throwable $throwable) {
            trace_file($throwable);
            $this->output->error('❌ 数据恢复失败：' . $throwable->getMessage());
            return false;
        } finally {
            gzclose($fp);
        }
    }

    /**
     * 批量插入数据。
     */
    private function flushBatchInsert(string $table, array &$rows): void
    {
        if (!$rows) {
            return;
        }

        try {
            $this->app->db->table($table)->insertAll($rows);
        } catch (\Throwable $throwable) {
            $this->output->writeln("⚠️ 表 {$table} 插入失败：{$throwable->getMessage()}");
        } finally {
            $rows = [];
        }
    }

    /**
     * 插入默认管理员。
     */
    private function insertSuperUser(): void
    {
        $model = SystemUser::mk()->whereRaw('1=1')->findOrEmpty();
        $model->isEmpty() && $model->save([
            'id' => '10000',
            'username' => 'admin',
            'nickname' => '超级管理员',
            'password' => '21232f297a57a5a743894a0e4a801fc3',
            'headimg' => 'https://thinkadmin.top/static/img/head.png',
        ], true);
        $this->output->writeln('✅ 管理员账号恢复成功');
    }
}
