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

use plugin\helper\migration\MigrationExporter;
use think\admin\service\RuntimeService;
use think\console\Command;
use think\console\input\Option;

/**
 * 根据当前数据库生成插件迁移脚本命令。
 */
class MigrateCommand extends Command
{
    public function configure(): void
    {
        $this->setName('xadmin:helper:migrate');
        $this->addOption('plugin', 'P', Option::VALUE_OPTIONAL, 'Only export specified plugins', '');
        $this->addOption('table', 'T', Option::VALUE_OPTIONAL, 'Only export specified tables', '');
        $this->addOption('model', 'M', Option::VALUE_NONE, 'Refresh model phpdocs after export');
        $this->setDescription('根据当前数据库生成插件迁移脚本');
    }

    public function isEnabled(): bool
    {
        return RuntimeService::isDebug();
    }

    public function handle(): void
    {
        $plugins = str2arr(str_replace('|', ',', strval($this->input->getOption('plugin'))));
        $tables = str2arr(str_replace('|', ',', strval($this->input->getOption('table'))));
        $exporter = new MigrationExporter($this->app, $this->output);
        $result = $exporter->export($plugins, $tables);

        if ($this->input->getOption('model')) {
            $this->output->writeln('Refreshing model annotations...');
            $this->app->console->call('xadmin:helper:model', ['--reset' => true, '--overwrite' => true], 'console');
        }

        $this->output->writeln(sprintf('Generated %d plugin migration file(s).', count($result)));
    }
}
