<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库: https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\support\command;

use think\admin\extend\ToolsExtend;
use think\admin\service\AppService;
use think\admin\service\RuntimeService;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 组件发布指令。
 * @class Publish
 */
class Publish extends Command
{
    /**
     * 已安装组件目录。
     * @var array<int, string>
     */
    protected array $packages = [];

    /**
     * 配置指令参数。
     */
    public function configure()
    {
        $this->setName('xadmin:publish');
        $this->addOption('force', 'f', Option::VALUE_NONE, 'Overwrite any existing files');
        $this->addOption('migrate', 'm', Option::VALUE_NONE, 'Execute phinx database script');
        $this->setDescription('Publish Plugs and Config Assets for ThinkAdmin');
    }

    /**
     * 执行发布任务。
     * @return null|void
     */
    public function execute(Input $input, Output $output)
    {
        RuntimeService::clear(false);
        $this->parse()->plugin()->output->writeln('<info>Succeed!</info>');
    }

    /**
     * 发布应用和插件资源。
     * @return $this
     */
    private function plugin(): self
    {
        $force = boolval($this->input->getOption('force'));
        $plugins = AppService::plugins();

        foreach (AppService::local() as $appName => $app) {
            if (isset($plugins[$appName])) {
                continue;
            }
            $this->copy($app['path'], $force);
        }

        foreach (array_unique($this->packages) as $installPath) {
            $this->copy($installPath, $force);
        }

        if ($this->input->getOption('migrate')) {
            $this->app->console->call('migrate:run', [], 'console');
        }

        return $this;
    }

    /**
     * 复制组件资源文件。
     * @param string $copy 组件目录
     * @param bool $force 是否强制覆盖
     */
    private function copy(string $copy, bool $force = false): void
    {
        $copy = rtrim($copy, '\/');

        ToolsExtend::copy($copy . DIRECTORY_SEPARATOR . 'config', syspath('config'), [], $force, false);
        ToolsExtend::copy($copy . DIRECTORY_SEPARATOR . 'public', syspath('public'), [], true, false);

        // 同步两类迁移目录：
        // 1. 应用模块自带的 database
        // 2. 插件包自带的 stc/database
        ToolsExtend::copy($copy . DIRECTORY_SEPARATOR . 'database', syspath('database/migrations'), [], $force, false);
        ToolsExtend::copy($copy . DIRECTORY_SEPARATOR . 'stc' . DIRECTORY_SEPARATOR . 'database', syspath('database/migrations'), [], $force, false);
    }

    /**
     * 解析已安装组件信息。
     * @return $this
     */
    private function parse(): self
    {
        [$services, $versions] = [[], []];

        if (is_file($file = syspath('vendor/composer/installed.json'))) {
            $packages = json_decode((string)@file_get_contents($file), true);
            foreach ($packages['packages'] ?? $packages as $package) {
                $installPath = syspath("vendor/{$package['name']}/");
                $type = $package['type'] ?? '';
                $config = $package['extra']['config'] ?? [];
                if ($type === 'think-admin-plugin' || !empty($package['extra']['plugin'])) {
                    $this->packages[] = rtrim($installPath, '\/');
                }
                $versions[$package['name']] = [
                    'type' => $config['type'] ?? ($type === 'think-admin-plugin' ? 'plugin' : 'library'),
                    'name' => $config['name'] ?? ($package['name'] ?? ''),
                    'icon' => $config['icon'] ?? '',
                    'cover' => $config['cover'] ?? '',
                    'super' => $config['super'] ?? false,
                    'license' => (array)($config['license'] ?? ($package['license'] ?? [])),
                    'version' => $config['version'] ?? ($package['version'] ?? ''),
                    'homepage' => $config['homepage'] ?? ($package['homepage'] ?? ''),
                    'document' => $config['document'] ?? ($package['document'] ?? ''),
                    'platforms' => $config['platforms'] ?? [],
                    'description' => $config['description'] ?? ($package['description'] ?? ''),
                ];

                if (!empty($package['extra']['think']['services'])) {
                    $services = array_merge($services, (array)$package['extra']['think']['services']);
                }

                if (!empty($package['extra']['think']['config'])) {
                    $configPath = $this->app->getConfigPath();
                    foreach ((array)$package['extra']['think']['config'] as $name => $file) {
                        if (is_file($target = $configPath . $name . '.php')) {
                            $this->output->info("File {$target} exist!");
                            continue;
                        }
                        if (!is_file($source = $installPath . $file)) {
                            $this->output->info("File {$source} not exist!");
                            continue;
                        }
                        copy($source, $target);
                    }
                }
            }
        }

        $header = '// Automatically Generated At: ' . date('Y-m-d H:i:s') . PHP_EOL . 'declare(strict_types=1);';
        $content = '<?php' . PHP_EOL . $header . PHP_EOL . 'return ' . var_export($services, true) . ';';
        @file_put_contents(syspath('vendor/services.php'), $content);

        $content = '<?php' . PHP_EOL . $header . PHP_EOL . 'return ' . var_export($versions, true) . ';';
        @file_put_contents(syspath('vendor/versions.php'), preg_replace('#\s+=>\s+array\s+\(#m', ' => array (', $content));

        return $this;
    }
}
