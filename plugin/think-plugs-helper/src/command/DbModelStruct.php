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

use Ergebnis\Classy\Constructs;
use plugin\helper\service\NormalizedModelGenerator;
use think\admin\extend\FileTools;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\ide\console\ModelCommand;

/**
 * 创建模型注释.
 * @class ModelGen
 */
class DbModelStruct extends ModelCommand
{
    public function handle()
    {
        $this->dirs = array_merge(['app', 'plugin'], $this->input->getOption('dir'));

        $model = $this->input->getArgument('model');
        $ignore = $this->input->getOption('ignore');

        $this->overwrite = $this->input->getOption('overwrite');

        $this->reset = $this->input->getOption('reset');

        $this->generateDocs($model, $ignore);
    }

    protected function configure()
    {
        $this->setName('xadmin:helper:model')
            ->addArgument('model', Argument::OPTIONAL | Argument::IS_ARRAY, 'Which models to include', [])
            ->addOption('dir', 'D', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'The model dir', [])
            ->addOption('ignore', 'I', Option::VALUE_OPTIONAL, 'Which models to ignore', '')
            ->addOption('reset', 'R', Option::VALUE_NONE, 'Remove the original phpdocs instead of appending')
            ->addOption('overwrite', 'O', Option::VALUE_NONE, 'Overwrite the phpdocs');
        $this->setDescription('自动生成用于IDE提示的模型注释');
    }

    protected function generateDocs($loadModels, $ignore = '')
    {
        if (empty($loadModels)) {
            $models = $this->loadModels();
        } else {
            $models = [];
            foreach ($loadModels as $model) {
                $models = array_merge($models, explode(',', $model));
            }
        }

        $ignore = explode(',', $ignore);
        foreach ($models as $name) {
            if (in_array($name, $ignore, true)) {
                if ($this->output->getVerbosity() >= Output::VERBOSITY_VERBOSE) {
                    $this->output->comment("Ignoring model '{$name}'");
                }
                continue;
            }

            if (!class_exists($name)) {
                continue;
            }

            try {
                (new NormalizedModelGenerator($this->app, $this->output, $name, $this->reset, $this->overwrite))->generate();
            } catch (\Exception $exception) {
                $this->output->error("Exception: {$exception->getMessage()}\nCould not analyze class {$name}.");
            }
        }
    }

    protected function loadModels(): array
    {
        $models = [];
        foreach ($this->dirs as $dir) {
            iterator_to_array(FileTools::findFilesYield($this->app->getRootPath() . $dir, null, function (\SplFileInfo $info) use (&$models) {
                if ($info->isDir() && $info->getFilename() === 'model') {
                    foreach (Constructs::fromDirectory($info->getRealPath()) as $construct) {
                        $models[] = $construct->name();
                    }
                    return false;
                }
                return true;
            }));
        }
        return $models;
    }
}
