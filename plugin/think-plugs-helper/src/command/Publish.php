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

use think\admin\extend\FileTools;
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
     * 插件迁移发布清单文件。
     */
    private const MIGRATION_MANIFEST = '.xadmin-published.json';

    /**
     * 已安装组件目录。
     * @var array<int, array{path:string,publish:array}>
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
        $migrationSources = [];

        foreach (AppService::local() as $appName => $app) {
            if (isset($plugins[$appName])) {
                continue;
            }
            $migrationSources = array_merge($migrationSources, $this->collectMigrationSources($app['path']));
            $this->copy($app['path'], $force);
        }

        $packages = [];
        foreach ($this->packages as $package) {
            $packages[$package['path']] = $package;
        }

        foreach ($packages as $package) {
            $migrationSources = array_merge($migrationSources, $this->collectMigrationSources($package['path']));
            $this->copy($package['path'], $force, $package['publish'] ?? []);
        }

        $this->syncMigrations($migrationSources, $force);

        if ($this->input->getOption('migrate')) {
            $this->app->console->call('migrate:run', [], 'console');
        }

        return $this;
    }

    /**
     * 复制组件资源文件。
     * @param string $copy 组件目录
     * @param bool $force 是否强制覆盖
     * @param array $publish 包级发布规则
     */
    private function copy(string $copy, bool $force = false, array $publish = []): void
    {
        $copy = rtrim($copy, '\/');

        FileTools::copy($copy . DIRECTORY_SEPARATOR . 'config', runpath('config'), [], $force, false);
        FileTools::copy($copy . DIRECTORY_SEPARATOR . 'public', runpath('public'), [], $force, false);
        FileTools::copy($copy . DIRECTORY_SEPARATOR . 'stc' . DIRECTORY_SEPARATOR . 'config', runpath('config'), [], $force, false);
        $this->copyByManifest($copy, $publish, 'init', $force);
        $this->copyByManifest($copy, $publish, 'copy', $force);
    }

    /**
     * 收集组件迁移脚本。
     * @return array<string, string>
     */
    private function collectMigrationSources(string $basePath): array
    {
        $items = [];
        foreach ([
            rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'database',
            rtrim($basePath, '\/') . DIRECTORY_SEPARATOR . 'stc' . DIRECTORY_SEPARATOR . 'database',
        ] as $path) {
            if (!is_dir($path)) {
                continue;
            }
            foreach (glob($path . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                $items[basename($file)] = $file;
            }
        }

        return $items;
    }

    /**
     * 同步插件迁移到项目目录。
     * @param array<string, string> $sources
     */
    private function syncMigrations(array $sources, bool $force = false): void
    {
        $targetDir = runpath('database/migrations');
        is_dir($targetDir) || mkdir($targetDir, 0777, true);

        $normalized = [];
        $versions = [];
        foreach ($sources as $name => $source) {
            if (!preg_match('/^(\d+)_/', $name, $match)) {
                continue;
            }
            $version = $match[1];
            if (isset($versions[$version]) && $versions[$version] !== $name) {
                throw new \RuntimeException("Duplicate migration version [{$version}] between [{$versions[$version]}] and [{$name}]");
            }
            $versions[$version] = $name;
            $normalized[$name] = $source;
        }

        $manifestFile = $targetDir . DIRECTORY_SEPARATOR . self::MIGRATION_MANIFEST;
        $manifest = $this->readMigrationManifest($manifestFile);

        foreach ($normalized as $name => $source) {
            $target = $targetDir . DIRECTORY_SEPARATOR . $name;
            $mtime = filemtime($source) ?: 0;
            $record = $manifest[$name] ?? [];
            $changed = !is_file($target)
                || ($record['mtime'] ?? null) !== $mtime
                || (is_file($target) && sha1_file($target) !== sha1_file($source));
            if ($force || $changed) {
                copy($source, $target);
                $this->output->info("Publish migration {$name}");
            }
        }

        foreach ($manifest as $name => $record) {
            if (isset($normalized[$name])) {
                continue;
            }
            $target = $targetDir . DIRECTORY_SEPARATOR . $name;
            if (is_file($target)) {
                unlink($target);
                $this->output->info("Remove stale migration {$name}");
            }
        }

        foreach (glob($targetDir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $target) {
            $name = basename($target);
            if (isset($normalized[$name])) {
                continue;
            }
            unlink($target);
            $this->output->info("Remove stale migration {$name}");
        }

        $records = [];
        foreach ($normalized as $name => $source) {
            $records[$name] = [
                'source' => str_replace(str_replace('\\', '/', $this->app->getRootPath()), '', str_replace('\\', '/', $source)),
                'mtime' => filemtime($source) ?: 0,
            ];
        }
        file_put_contents($manifestFile, json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }

    /**
     * 读取迁移发布清单。
     * @return array<string, array{source:string,mtime:int}>
     */
    private function readMigrationManifest(string $file): array
    {
        if (!is_file($file)) {
            return [];
        }
        $data = json_decode((string)file_get_contents($file), true);
        return is_array($data) ? $data : [];
    }

    /**
     * 按发布清单复制资源。
     */
    private function copyByManifest(string $packagePath, array $publish, string $mode, bool $force = false): void
    {
        foreach ($this->normalizePublishItems($publish[$mode] ?? []) as $item) {
            $source = rtrim($packagePath, '\/') . DIRECTORY_SEPARATOR . ltrim($item['source'], '\/');
            $target = syspath($item['target']);
            if ($mode === 'init') {
                $this->copyInitPath($source, $target, $force);
            } else {
                $this->copyPath($source, $target, $force);
            }
        }
    }

    /**
     * 标准化发布清单项。
     * @param mixed $items
     * @return array<int, array{source:string,target:string}>
     */
    private function normalizePublishItems($items): array
    {
        $result = [];
        if (!is_array($items)) {
            return $result;
        }

        foreach ($items as $source => $target) {
            if (is_array($target)) {
                $source = strval($target['from'] ?? $target['source'] ?? '');
                $target = strval($target['to'] ?? $target['target'] ?? '');
            } else {
                $source = strval($source);
                $target = strval($target);
            }
            if ($source !== '' && $target !== '') {
                $result[] = ['source' => $source, 'target' => $target];
            }
        }

        return $result;
    }

    /**
     * 复制初始化资源，默认仅在目标不存在时写入。
     */
    private function copyInitPath(string $source, string $target, bool $force = false): void
    {
        if (!file_exists($source)) {
            return;
        }
        if (!$force && file_exists($target)) {
            return;
        }
        $this->copyPath($source, $target, true);
    }

    /**
     * 复制资源文件或目录。
     */
    private function copyPath(string $source, string $target, bool $force = false): void
    {
        if (!file_exists($source)) {
            return;
        }
        if (is_dir($source)) {
            FileTools::copy($source, $target, [], $force, false);
            return;
        }
        if ($force || !is_file($target)) {
            is_dir($dir = dirname($target)) || mkdir($dir, 0777, true);
            copy($source, $target);
        }
    }

    /**
     * 解析已安装组件信息。
     * @return $this
     */
    private function parse(): self
    {
        [$services, $versions, $known] = [[], [], []];

        if (is_file($file = syspath('vendor/composer/installed.json'))) {
            $packages = json_decode((string)@file_get_contents($file), true);
            foreach ($packages['packages'] ?? $packages as $package) {
                $name = strval($package['name'] ?? '');
                if ($name === '') {
                    continue;
                }
                $known[$name] = true;
                $this->mergePackage($package, syspath("vendor/{$name}/"), $services, $versions);
            }
        }

        foreach ($this->discoverWorkspacePackages() as $package) {
            $name = strval($package['name'] ?? '');
            if ($name === '' || isset($known[$name])) {
                continue;
            }
            $known[$name] = true;
            $this->mergePackage($package, strval($package['__path'] ?? ''), $services, $versions);
        }

        $services = array_values(array_unique(array_filter(array_map('strval', $services))));

        is_dir($dir = runpath('vendor')) || @mkdir($dir, 0777, true);

        $header = '// Automatically Generated At: ' . date('Y-m-d H:i:s') . PHP_EOL . 'declare(strict_types=1);';
        $content = '<?php' . PHP_EOL . $header . PHP_EOL . 'return ' . var_export($services, true) . ';';
        @file_put_contents(runpath('vendor/services.php'), $content);

        $content = '<?php' . PHP_EOL . $header . PHP_EOL . 'return ' . var_export($versions, true) . ';';
        @file_put_contents(runpath('vendor/versions.php'), preg_replace('#\s+=>\s+array\s+\(#m', ' => array (', $content));

        return $this;
    }

    /**
     * 合并单个组件元数据。
     * @param array<string, mixed> $package
     * @param array<int, string> $services
     * @param array<string, array<string, mixed>> $versions
     */
    private function mergePackage(array $package, string $installPath, array &$services, array &$versions): void
    {
        $installPath = rtrim($installPath, '\/');
        $type = strval($package['type'] ?? '');
        $meta = $this->normalizePackageMeta($package);
        if ($installPath !== '' && ($type === 'think-admin-plugin' || !empty($package['extra']['plugin']))) {
            $this->packages[] = [
                'path' => $installPath,
                'publish' => (array)($package['extra']['xadmin']['publish'] ?? []),
            ];
        }
        if (!empty($package['name'])) {
            $versions[strval($package['name'])] = $meta;
        }

        if (!empty($package['extra']['think']['services'])) {
            $services = array_merge($services, (array)$package['extra']['think']['services']);
        }

        if ($installPath !== '' && !empty($package['extra']['think']['config'])) {
            $configPath = $this->app->getConfigPath();
            foreach ((array)$package['extra']['think']['config'] as $name => $file) {
                if (is_file($target = $configPath . $name . '.php')) {
                    $this->output->info("File {$target} exist!");
                    continue;
                }
                if (!is_file($source = $installPath . DIRECTORY_SEPARATOR . ltrim(strval($file), '\/'))) {
                    $this->output->info("File {$source} not exist!");
                    continue;
                }
                copy($source, $target);
            }
        }
    }

    /**
     * 扫描当前工作区的本地插件包。
     * @return array<int, array<string, mixed>>
     */
    private function discoverWorkspacePackages(): array
    {
        $items = [];
        $pluginRoot = syspath('plugin');
        if (!is_dir($pluginRoot)) {
            return $items;
        }

        foreach (glob($pluginRoot . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'composer.json') ?: [] as $file) {
            $manifest = json_decode((string)@file_get_contents($file), true);
            if (!is_array($manifest) || empty($manifest['name'])) {
                continue;
            }
            $manifest['__path'] = dirname($file);
            $items[] = $manifest;
        }

        return $items;
    }

    /**
     * 标准化组件元数据。
     */
    private function normalizePackageMeta(array $package): array
    {
        $type = strval($package['type'] ?? '');
        $config = (array)($package['extra']['config'] ?? []);
        $service = (array)($package['extra']['xadmin']['service'] ?? []);

        return [
            'type' => strval($service['type'] ?? ($config['type'] ?? ($type === 'think-admin-plugin' ? 'plugin' : 'library'))),
            'name' => strval($service['name'] ?? ($config['name'] ?? ($package['name'] ?? ''))),
            'icon' => strval($config['icon'] ?? ''),
            'cover' => strval($config['cover'] ?? ''),
            'super' => boolval($config['super'] ?? false),
            'license' => (array)($service['license'] ?? ($config['license'] ?? ($package['license'] ?? []))),
            'version' => strval($service['version'] ?? ($config['version'] ?? ($package['version'] ?? ''))),
            'homepage' => strval($service['homepage'] ?? ($config['homepage'] ?? ($package['homepage'] ?? ''))),
            'document' => strval($service['document'] ?? ($config['document'] ?? ($package['document'] ?? ''))),
            'platforms' => (array)($service['platforms'] ?? ($config['platforms'] ?? [])),
            'description' => strval($service['description'] ?? ($config['description'] ?? ($package['description'] ?? ''))),
        ];
    }
}
