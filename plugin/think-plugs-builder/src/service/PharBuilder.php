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

namespace plugin\builder\service;

use Phar;
use think\App;
use think\console\Output;

final class PharBuilder
{
    private const BOOTSTRAP_FILE = 'phar-bootstrap.php';

    /** @var string[] */
    private array $includeFiles = ['think', 'composer.json', 'composer.lock', '.env.example'];

    /** @var string[] 按目录名排除，仅用于项目根及非 vendor 路径，避免误排除 vendor 内同名目录（如 think-library/src/runtime） */
    private array $excludeNames = ['.git', '.github', '.idea', '.vscode', 'build', 'docs', 'safefile', 'tests'];

    /** @var string[] 按路径前缀排除；含项目根 runtime，不含 vendor 内 runtime */
    private array $excludePaths = ['.env', 'public/upload', 'runtime'];

    public function __construct(
        private readonly App $app,
        private readonly Output $output,
    ) {}

    /**
     * 使用临时目录构建 PHAR，避免直接遍历项目目录导致路径仓库扫描过慢。
     *
     * @param array<int, string> $extractDirs
     * @param array<int, string> $mountPaths
     * @param array<int, string> $extraExcludes
     */
    public function build(string $name, string $main, array $extractDirs = [], array $mountPaths = [], array $extraExcludes = []): string
    {
        if (ini_get('phar.readonly') === '1') {
            throw new \RuntimeException('phar.readonly is enabled, please run with -d phar.readonly=0');
        }

        $root = rtrim($this->app->getRootPath(), DIRECTORY_SEPARATOR);
        if (!is_dir($root . DIRECTORY_SEPARATOR . 'vendor')) {
            throw new \RuntimeException('The vendor directory is missing, please run composer install first.');
        }

        $main = trim($main, '\/');
        if (!is_file($root . DIRECTORY_SEPARATOR . $main)) {
            throw new \RuntimeException("Main entry file not found: {$main}");
        }

        $target = $this->normalizeTarget($name, $root);
        [$excludeNames, $excludePaths] = $this->collectExcludes($extraExcludes);
        $workspace = $this->prepareStage($root, $excludeNames, $excludePaths);
        $temp = $target . '.tmp';
        $alias = basename($target);
        $stageRoot = $workspace . DIRECTORY_SEPARATOR . 'project';

        try {
            @unlink($temp);
            @unlink($target);
            is_dir(dirname($target)) || mkdir(dirname($target), 0777, true);

            $this->output->writeln("<info>开始打包 PHAR</info> {$target}");
            $phar = new \Phar($temp, 0, $alias);
            $phar->startBuffering();

            $this->addDirectory($phar, $stageRoot);
            $phar->addFromString(self::BOOTSTRAP_FILE, PharRuntime::buildEntry($main, $extractDirs, $mountPaths));
            $phar->setStub("#!/usr/bin/env php\n" . $phar->createDefaultStub(self::BOOTSTRAP_FILE));
            $phar->setSignatureAlgorithm(\Phar::SHA256);
            $phar->stopBuffering();

            rename($temp, $target);
            @chmod($target, 0755);

            return $target;
        } finally {
            $this->removePath($workspace);
        }
    }

    /**
     * 准备临时分发目录。
     */
    private function prepareStage(string $root, array $excludeNames, array $excludePaths): string
    {
        $workspace = $this->createWorkspace();
        $stageRoot = $workspace . DIRECTORY_SEPARATOR . 'project';
        is_dir($stageRoot) || mkdir($stageRoot, 0777, true);

        $this->output->writeln("<info>准备临时目录</info> {$stageRoot}");
        $this->stageRootFiles($root, $stageRoot, $excludeNames, $excludePaths);
        $this->stageProjectDirectories($root, $stageRoot, $excludeNames, $excludePaths);
        $this->stageVendor($root, $stageRoot, $excludeNames, $excludePaths);
        $this->patchStageFiles($stageRoot);

        return $workspace;
    }

    /**
     * 拷贝根目录下需要打包的文件。
     */
    private function stageRootFiles(string $root, string $stageRoot, array $excludeNames, array $excludePaths): void
    {
        foreach ($this->includeFiles as $file) {
            if ($this->shouldExclude($file, $excludeNames, $excludePaths)) {
                continue;
            }

            $source = $root . DIRECTORY_SEPARATOR . $file;
            if (!is_file($source)) {
                continue;
            }

            $this->output->writeln("  - 复制文件 {$file}");
            $this->copyFile($source, $stageRoot . DIRECTORY_SEPARATOR . $file);
        }
    }

    /**
     * 拷贝项目根目录下的业务目录。
     */
    private function stageProjectDirectories(string $root, string $stageRoot, array $excludeNames, array $excludePaths): void
    {
        foreach (scandir($root) ?: [] as $name) {
            if ($name === '.' || $name === '..' || $name === 'vendor') {
                continue;
            }

            $source = $root . DIRECTORY_SEPARATOR . $name;
            if (!is_dir($source) || $this->shouldExclude($name, $excludeNames, $excludePaths)) {
                continue;
            }

            $this->output->writeln("  - 复制目录 {$name}");
            $this->copyDirectory($source, $stageRoot . DIRECTORY_SEPARATOR . $name, $excludeNames, $excludePaths, $name);
        }
    }

    /**
     * 拷贝 Composer 自动加载文件与依赖目录。
     */
    private function stageVendor(string $root, string $stageRoot, array $excludeNames, array $excludePaths): void
    {
        $vendorRoot = $root . DIRECTORY_SEPARATOR . 'vendor';
        $stageVendorRoot = $stageRoot . DIRECTORY_SEPARATOR . 'vendor';
        $composerRoot = $vendorRoot . DIRECTORY_SEPARATOR . 'composer';

        $this->output->writeln('  - 复制 Composer 自动加载文件');
        $this->copyFile($vendorRoot . DIRECTORY_SEPARATOR . 'autoload.php', $stageVendorRoot . DIRECTORY_SEPARATOR . 'autoload.php');
        $this->copyFile($vendorRoot . DIRECTORY_SEPARATOR . 'services.php', $stageVendorRoot . DIRECTORY_SEPARATOR . 'services.php');

        foreach (scandir($composerRoot) ?: [] as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }

            $source = $composerRoot . DIRECTORY_SEPARATOR . $name;
            if (!is_file($source)) {
                continue;
            }

            $logical = "vendor/composer/{$name}";
            if ($this->shouldExclude($logical, $excludeNames, $excludePaths)) {
                continue;
            }

            $this->copyFile($source, $stageVendorRoot . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . $name);
        }

        if (is_dir($vendorRoot . DIRECTORY_SEPARATOR . 'bin')) {
            $this->output->writeln('  - 复制 vendor/bin');
            $this->copyDirectory(
                $vendorRoot . DIRECTORY_SEPARATOR . 'bin',
                $stageVendorRoot . DIRECTORY_SEPARATOR . 'bin',
                $excludeNames,
                $excludePaths,
                'vendor/bin',
            );
        }

        $copied = [];
        foreach ($this->readInstalledPackages($composerRoot . DIRECTORY_SEPARATOR . 'installed.json') as $package) {
            $installPath = strval($package['install-path'] ?? '');
            if ($installPath === '') {
                continue;
            }

            $sourcePath = $this->resolvePath($composerRoot, $installPath);
            $targetPath = $this->resolvePath($stageVendorRoot . DIRECTORY_SEPARATOR . 'composer', $installPath);
            $logical = $this->relativeTo($stageRoot, $targetPath);

            if ($logical === '' || isset($copied[$logical]) || $this->shouldExclude($logical, $excludeNames, $excludePaths)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                $realSource = realpath($sourcePath) ?: $sourcePath;
                $this->output->writeln("  - 复制依赖 {$package['name']}");
                $this->copyDirectory($realSource, $targetPath, $excludeNames, $excludePaths, $logical);
                $copied[$logical] = true;
            } elseif (is_file($sourcePath)) {
                $this->output->writeln("  - 复制依赖文件 {$package['name']}");
                $this->copyFile($sourcePath, $targetPath);
                $copied[$logical] = true;
            }
        }
    }

    /**
     * 将临时目录中的全部文件写入 PHAR。
     */
    private function addDirectory(\Phar $phar, string $root): void
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        $phar->buildFromIterator($iterator, $root);
    }

    /**
     * 读取 Composer 已安装依赖清单。
     */
    private function readInstalledPackages(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('The vendor/composer/installed.json file is missing.');
        }

        $installed = json_decode(file_get_contents($path) ?: '[]', true, 512, JSON_THROW_ON_ERROR);
        $packages = isset($installed['packages']) && is_array($installed['packages']) ? $installed['packages'] : $installed;

        return array_values(array_filter($packages, static fn ($package): bool => is_array($package)));
    }

    /**
     * 拷贝目录，支持基于逻辑路径的排除规则。
     */
    private function copyDirectory(
        string $source,
        string $target,
        array $excludeNames,
        array $excludePaths,
        string $logicalPrefix = '',
    ): void {
        $logicalPrefix = trim($this->normalizeRelativePath($logicalPrefix), '/');
        if ($logicalPrefix !== '' && $this->shouldExclude($logicalPrefix, $excludeNames, $excludePaths)) {
            return;
        }

        is_dir($target) || mkdir($target, 0777, true);
        $source = rtrim($source, DIRECTORY_SEPARATOR);

        $filter = new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            function (\SplFileInfo $item) use ($source, $logicalPrefix, $excludeNames, $excludePaths): bool {
                $relative = $this->relativeTo($source, $item->getPathname());
                $logical = trim($logicalPrefix === '' ? $relative : "{$logicalPrefix}/{$relative}", '/');

                return $logical === '' || !$this->shouldExclude($logical, $excludeNames, $excludePaths);
            }
        );

        $iterator = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $relative = $this->relativeTo($source, $item->getPathname());
            if ($relative === '') {
                continue;
            }

            $destination = $target . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if ($item->isDir()) {
                is_dir($destination) || mkdir($destination, 0777, true);
                continue;
            }

            $this->copyFile($item->getPathname(), $destination);
        }
    }

    /**
     * 拷贝单个文件。
     */
    private function copyFile(string $source, string $target): void
    {
        if (!is_file($source)) {
            return;
        }

        is_dir(dirname($target)) || mkdir(dirname($target), 0777, true);
        copy($source, $target);
    }

    /**
     * 修补已知的 PHAR 兼容性问题。
     */
    private function patchStageFiles(string $stageRoot): void
    {
        $patches = [
            'vendor/topthink/framework/src/think/App.php' => [
                '$this->thinkPath   = realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR;' => '$this->thinkPath   = (realpath(dirname(__DIR__)) ?: dirname(__DIR__)) . DIRECTORY_SEPARATOR;',
                '$this->rootPath = $this->getRootPath();' => '$this->rootPath = runpath();',
                '$this->runtimePath = $this->rootPath . \'runtime\' . DIRECTORY_SEPARATOR;' => '$this->runtimePath = rtrim(runpath(\'runtime\'), \'\\\/\') . DIRECTORY_SEPARATOR;',
                "        if (is_dir(\$configPath)) {\n            \$files = glob(\$configPath . '*' . \$this->configExt);\n        }\n" => "        if (is_dir(\$configPath)) {\n            foreach (new \\DirectoryIterator(\$configPath) as \$item) {\n                if (\$item->isFile() && \$item->getExtension() === ltrim(\$this->configExt, '.')) {\n                    \$files[] = \$item->getPathname();\n                }\n            }\n        }\n",
            ],
            'vendor/topthink/framework/src/think/Http.php' => [
                "        if (is_dir(\$routePath)) {\n            \$files = glob(\$routePath . '*.php');\n            foreach (\$files as \$file) {\n                include \$file;\n            }\n        }\n" => "        if (is_dir(\$routePath)) {\n            foreach (new \\DirectoryIterator(\$routePath) as \$item) {\n                if (\$item->isFile() && \$item->getExtension() === 'php') {\n                    include \$item->getPathname();\n                }\n            }\n        }\n",
            ],
            'vendor/topthink/framework/src/think/Lang.php' => [
                "        \$files = glob(\$this->app->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . \$langset . '.*');\n        \$this->load(\$files);\n" => "        \$files = [];\n        \$langPath = \$this->app->getAppPath() . 'lang' . DIRECTORY_SEPARATOR;\n        if (is_dir(\$langPath)) {\n            foreach (new \\DirectoryIterator(\$langPath) as \$item) {\n                if (\$item->isFile() && str_starts_with(\$item->getFilename(), \$langset . '.')) {\n                    \$files[] = \$item->getPathname();\n                }\n            }\n        }\n        \$this->load(\$files);\n",
            ],
            'vendor/topthink/framework/src/think/route/Domain.php' => [
                "        if (is_dir(\$routePath)) {\n            \$dirs = glob(\$routePath . '*', GLOB_ONLYDIR);\n            foreach (\$dirs as \$dir) {\n" => "        if (is_dir(\$routePath)) {\n            \$dirs = [];\n            foreach (new \\DirectoryIterator(\$routePath) as \$item) {\n                if (\$item->isDir() && !\$item->isDot()) {\n                    \$dirs[] = \$item->getPathname();\n                }\n            }\n            foreach (\$dirs as \$dir) {\n",
            ],
            'vendor/topthink/framework/src/think/route/RuleGroup.php' => [
                "        if (is_dir(\$routePath)) {\n            // 动态加载分组路由\n            \$files = glob(\$routePath . '*.php');\n            foreach (\$files as \$file) {\n                include_once \$file;\n            }\n\n            // 自动扫描下级分组\n            \$dirs = \$this->config('route_auto_group') ? glob(\$routePath . '*', GLOB_ONLYDIR) : [];\n            foreach (\$dirs as \$dir) {\n" => "        if (is_dir(\$routePath)) {\n            // 动态加载分组路由\n            \$files = [];\n            \$dirs = [];\n            foreach (new \\DirectoryIterator(\$routePath) as \$item) {\n                if (\$item->isFile() && \$item->getExtension() === 'php') {\n                    \$files[] = \$item->getPathname();\n                }\n                if (\$item->isDir() && !\$item->isDot()) {\n                    \$dirs[] = \$item->getPathname();\n                }\n            }\n            foreach (\$files as \$file) {\n                include_once \$file;\n            }\n\n            // 自动扫描下级分组\n            if (!\$this->config('route_auto_group')) {\n                \$dirs = [];\n            }\n            foreach (\$dirs as \$dir) {\n",
            ],
        ];

        foreach ($patches as $relative => $replaces) {
            $file = $stageRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
            if (!is_file($file)) {
                continue;
            }

            $content = file_get_contents($file);
            if (!is_string($content) || $content === '') {
                continue;
            }

            $patched = str_replace(array_keys($replaces), array_values($replaces), $content, $count);
            if ($count > 0) {
                $this->output->writeln("  - 修补兼容文件 {$relative}");
                file_put_contents($file, $patched);
            }
        }
    }

    /**
     * 收集排除规则，兼容按名称排除和按路径排除两种写法。
     *
     * @param array<int, string> $extraExcludes
     * @return array{0:array<int, string>,1:array<int, string>}
     */
    private function collectExcludes(array $extraExcludes): array
    {
        $excludeNames = $this->excludeNames;
        $excludePaths = $this->excludePaths;

        foreach ($extraExcludes as $rule) {
            $rule = trim($this->normalizeRelativePath($rule), '/');
            if ($rule === '') {
                continue;
            }

            if (str_contains($rule, '/')) {
                $excludePaths[] = $rule;
            } else {
                $excludeNames[] = $rule;
            }
        }

        return [
            array_values(array_unique($excludeNames)),
            array_values(array_unique($excludePaths)),
        ];
    }

    /**
     * 判断逻辑路径是否需要排除。
     */
    private function shouldExclude(string $relative, array $excludeNames, array $excludePaths): bool
    {
        $relative = trim($this->normalizeRelativePath($relative), '/');
        if ($relative === '') {
            return false;
        }

        $parts = array_values(array_filter(explode('/', $relative), 'strlen'));
        foreach ($parts as $part) {
            if (in_array($part, $excludeNames, true)) {
                return true;
            }
        }

        foreach ($excludePaths as $path) {
            $path = trim($this->normalizeRelativePath($path), '/');
            if ($path !== '' && ($relative === $path || str_starts_with($relative, "{$path}/"))) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解析相对路径并返回绝对路径。
     */
    private function resolvePath(string $base, string $path): string
    {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (preg_match('#^(?:[A-Za-z]:\\\|/)#', $path) === 1) {
            return $path;
        }

        $full = $base . DIRECTORY_SEPARATOR . $path;
        $prefix = '';
        if (preg_match('#^[A-Za-z]:\\\#', $full) === 1) {
            $prefix = substr($full, 0, 3);
            $full = substr($full, 3);
        } elseif (str_starts_with($full, DIRECTORY_SEPARATOR)) {
            $prefix = DIRECTORY_SEPARATOR;
            $full = ltrim($full, DIRECTORY_SEPARATOR);
        }

        $parts = [];
        foreach (explode(DIRECTORY_SEPARATOR, $full) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }
            if ($part === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $part;
        }

        return $prefix . implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * 计算目标路径相对于基准目录的逻辑路径。
     */
    private function relativeTo(string $root, string $path): string
    {
        $root = rtrim($this->normalizePath($root), '/');
        $path = $this->normalizePath($path);

        return ltrim(substr($path, strlen($root)), '/');
    }

    /**
     * 标准化相对路径分隔符。
     */
    private function normalizeRelativePath(string $path): string
    {
        return str_replace('\\', '/', trim($path));
    }

    /**
     * 标准化绝对路径分隔符。
     */
    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * 创建临时工作目录。
     */
    private function createWorkspace(): string
    {
        $workspace = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'thinkadmin-phar-' . uniqid('', true);
        is_dir($workspace) || mkdir($workspace, 0777, true);

        return $workspace;
    }

    /**
     * 递归删除目录或文件。
     */
    private function removePath(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);
            return;
        }

        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }

        @rmdir($path);
    }

    /**
     * 标准化输出文件名。
     */
    private function normalizeTarget(string $name, string $root): string
    {
        $name = trim($name);
        if ($name === '') {
            $name = 'admin.phar';
        }

        if (preg_match('#^(?:[A-Za-z]:[\\\/]|/)#', $name) === 1) {
            return $name;
        }

        return $root . DIRECTORY_SEPARATOR . $name;
    }
}
