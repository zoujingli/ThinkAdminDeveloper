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

namespace plugin\builder\command;

use plugin\builder\service\PharBuilder;
use think\admin\service\RuntimeService;
use think\console\Command;
use think\console\input\Option;

class Build extends Command
{
    /**
     * 配置 PHAR 打包命令。
     */
    public function configure(): void
    {
        $this->setName('xadmin:builder')
            ->addOption('name', '', Option::VALUE_OPTIONAL, '输出的 PHAR 文件名。', 'admin.phar')
            ->addOption('main', '', Option::VALUE_OPTIONAL, '项目在包内的主入口文件。', 'think')
            ->addOption('extract', '', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, '首次启动时解压到外部目录的路径。', ['public', 'database'])
            ->addOption('mount', '', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, '运行时挂载到 PHAR 外部的文件或目录。', ['.env', 'runtime', 'safefile', 'public', 'database'])
            ->addOption('exclude', '', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, '额外排除的文件或目录。')
            ->setDescription('将当前 ThinkAdmin 项目打包为可直接运行的 PHAR');
    }

    /**
     * 仅在调试模式下允许执行打包。
     */
    public function isEnabled(): bool
    {
        return RuntimeService::isDebug();
    }

    /**
     * 执行 PHAR 打包。
     */
    public function handle(): void
    {
        $builder = new PharBuilder($this->app, $this->output);
        $target = $builder->build(
            strval($this->input->getOption('name')),
            strval($this->input->getOption('main')),
            array_values(array_filter((array)$this->input->getOption('extract'), 'is_string')),
            array_values(array_filter((array)$this->input->getOption('mount'), 'is_string')),
            array_values(array_filter((array)$this->input->getOption('exclude'), 'is_string')),
        );

        $this->output->writeln("PHAR 打包完成: {$target}");
    }
}
