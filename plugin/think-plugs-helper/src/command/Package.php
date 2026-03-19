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

use plugin\helper\service\PhinxExtend;
use plugin\system\service\SystemService;
use think\admin\Command;
use think\admin\Exception;
use think\admin\Library;
use think\console\input\Option;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 生成数据安装包.
 * @class Package
 */
class Package extends Command
{
    /**
     * 系统指定配置.
     */
    public function configure()
    {
        $this->setName('xadmin:package');
        $this->addOption('all', 'a', Option::VALUE_NONE, 'Backup All Tables');
        $this->addOption('force', 'f', Option::VALUE_NONE, 'Force All Update');
        $this->addOption('table', 't', Option::VALUE_OPTIONAL, 'Package Tables Scheme', '');
        $this->addOption('backup', 'b', Option::VALUE_OPTIONAL, 'Package Tables Backup', '');
        $this->setDescription('Generate System Install Package for ThinkAdmin');
    }

    /**
     * 生成系统安装数据包.
     * @throws Exception
     */
    public function handle()
    {
        try {
            $dirname = runpath('database/migrations');
            is_dir($dirname) || mkdir($dirname, 0777, true);
            $this->output->writeln('--- 开始创建数据库迁移脚本 ---');
            if ($this->createBackup() && $this->createScheme()) {
                $this->setQueueSuccess('--- 数据库迁移脚本创建成功 ---');
            } else {
                $this->setQueueError('--- 数据库迁移脚本创建失败 ---');
            }
        } catch (Exception $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            trace_file($exception);
            $this->setQueueError($exception->getMessage());
        }
    }

    /**
     * 创建数据表.
     * @throws \Exception
     */
    private function createScheme(): bool
    {
        $force = boolval($this->input->getOption('force'));
        $option = trim(strval($this->input->getOption('table')));
        if ($option !== '') {
            $tables = str2arr(strtr($option, '|', ','));
        } elseif ($this->input->getOption('all')) {
            [$tables] = SystemService::getTables();
        } else {
            $tables = Library::$sapp->config->get('phinx.tables', []);
            if (empty($tables)) {
                [$tables] = SystemService::getTables();
            }
        }

        $ignore = Library::$sapp->config->get('phinx.ignore', []);
        $tables = array_unique(array_diff($tables, $ignore, ['migrations']));

        [$prefix, $groups] = ['', []];
        foreach ($tables as $table) {
            $attr = explode('_', $table);
            if ($attr[0] === 'plugin') {
                array_shift($attr);
            }
            if (empty($prefix) || $prefix !== $attr[0]) {
                $prefix = $attr[0];
            }
            $groups[$prefix][] = $table;
        }

        [$total, $count] = [count($groups), 0];
        $this->setQueueMessage($total, 0, '开始创建数据表创建脚本！');
        foreach ($groups as $key => $tbs) {
            $name = 'Install' . ucfirst($key) . 'Table';
            $phinx = PhinxExtend::create2table($tbs, $name, $force);
            $target = runpath("database/migrations/{$phinx['file']}");
            if (file_put_contents($target, $phinx['text']) !== false) {
                $this->setQueueMessage($total, ++$count, "创建数据库 {$name} 安装脚本成功！");
            } else {
                $this->setQueueMessage($total, ++$count, "创建数据库 {$name} 安装脚本失败！");
                return false;
            }
        }

        return true;
    }

    /**
     * 创建数据包.
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function createBackup(): bool
    {
        $option = trim(strval($this->input->getOption('backup')));
        if ($option !== '') {
            $tables = str2arr(strtr($option, '|', ','));
        } elseif ($this->input->getOption('all')) {
            [$tables] = SystemService::getTables();
        } else {
            [$tables] = SystemService::getTables();
            $tables = array_intersect($tables, Library::$sapp->config->get('phinx.backup', []));
        }

        $ignore = Library::$sapp->config->get('phinx.ignore', []);
        if (empty($ignore)) {
            $ignore = ['system_queue', 'system_oplog'];
        }
        $tables = array_unique(array_diff($tables, $ignore, ['migrations']));

        $this->setQueueMessage(4, 1, '开始创建数据包安装脚本！');
        $phinx = PhinxExtend::create2backup($tables);
        $target = runpath("database/migrations/{$phinx['file']}");
        if (file_put_contents($target, $phinx['text']) !== false) {
            $this->setQueueMessage(4, 2, '成功创建数据包安装脚本！');
            return true;
        }

        $this->setQueueMessage(4, 2, '创建数据包安装脚本失败！');
        return false;
    }
}
