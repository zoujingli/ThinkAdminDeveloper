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

final class PharRuntime
{
    /**
     * 生成 PHAR 运行时入口脚本。
     *
     * @param array<int, string> $extractDirs
     * @param array<int, string> $mountPaths
     */
    public static function buildEntry(string $main, array $extractDirs, array $mountPaths): string
    {
        $mountFiles = [];
        $mountDirs = [];

        foreach ($mountPaths as $path) {
            $path = str_replace('\\', '/', trim($path));
            if ($path === '') {
                continue;
            }

            if ($path === '.env' || pathinfo($path, PATHINFO_EXTENSION) !== '') {
                $mountFiles[] = trim($path, '/');
            } else {
                $mountDirs[] = trim($path, '/');
            }
        }

        $extractDirs = array_values(array_unique(array_map(static function (string $path): string {
            return trim(str_replace('\\', '/', $path), '/');
        }, array_filter($extractDirs, 'strlen'))));
        $mountFiles = array_values(array_unique($mountFiles));
        $mountDirs = array_values(array_unique($mountDirs));

        $exportMain = var_export($main, true);
        $exportExtract = var_export($extractDirs, true);
        $exportMountFiles = var_export($mountFiles, true);
        $exportMountDirs = var_export($mountDirs, true);

        return <<<PHP
<?php

declare(strict_types=1);

(static function (): void {
    \$archive = \\Phar::running(false);
    if (\$archive === '') {
        return;
    }
    // PHAR 体积较大时，加载阶段即可能超过 PHP 默认 128M，若仍为低限制则提示
    if (PHP_SAPI === 'cli') {
        \$ml = ini_get('memory_limit');
        \$n = (int) \$ml;
        \$u = \$ml !== '' && \$ml !== '-1' ? strtolower(ltrim(trim(\$ml), '0123456789')) : '';
        if (str_starts_with(\$u, 'g')) {
            \$n *= 1024 * 1024 * 1024;
        } elseif (str_starts_with(\$u, 'm')) {
            \$n *= 1024 * 1024;
        } elseif (str_starts_with(\$u, 'k')) {
            \$n *= 1024;
        }
        if (\$n > 0 && \$n < 256 * 1024 * 1024) {
            fwrite(STDERR, 'Warning: memory_limit may be too low for this PHAR. Run: php -d memory_limit=256M ' . basename(\$archive) . PHP_EOL);
        }
    }

    \$installRoot = dirname(\$archive);
    \$installRoot = rtrim(\$installRoot, '\\\\/') . DIRECTORY_SEPARATOR;
    \$archiveRoot = 'phar://' . \$archive;
    \$extractDirs = {$exportExtract};
    \$mountFiles = {$exportMountFiles};
    \$mountDirs = {$exportMountDirs};

    chdir(\$installRoot);

    \$normalize = static function (string \$path) use (\$installRoot): string {
        \$path = str_replace(['/', '\\\\'], DIRECTORY_SEPARATOR, \$path);
        return \$installRoot . ltrim(\$path, DIRECTORY_SEPARATOR);
    };

    \$ensureDir = static function (string \$path): void {
        if (!is_dir(\$path)) {
            mkdir(\$path, 0777, true);
        }
    };

    \$syncRuntimeEnv = static function () use (\$normalize, \$ensureDir): void {
        \$source = \$normalize('.env');
        \$target = \$normalize('runtime/.env');
        if (!is_file(\$source)) {
            return;
        }

        \$ensureDir(dirname(\$target));
        if (!is_file(\$target) || md5_file(\$target) !== md5_file(\$source)) {
            copy(\$source, \$target);
        }
    };

    \$copyMissing = static function (string \$source, string \$target) use (&\$copyMissing, \$ensureDir): void {
        if (is_dir(\$source)) {
            \$ensureDir(\$target);
            \$iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(\$source, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach (\$iterator as \$item) {
                \$relative = substr(str_replace(['/', '\\\\'], '/', \$item->getPathname()), strlen(str_replace(['/', '\\\\'], '/', \$source)));
                \$relative = ltrim(\$relative, '/');
                \$destination = rtrim(\$target, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, \$relative);
                if (\$item->isDir()) {
                    \$ensureDir(\$destination);
                } elseif (!is_file(\$destination)) {
                    \$ensureDir(dirname(\$destination));
                    copy(\$item->getPathname(), \$destination);
                }
            }
            return;
        }

        if (is_file(\$source) && !is_file(\$target)) {
            \$ensureDir(dirname(\$target));
            copy(\$source, \$target);
        }
    };

    if (!is_file(\$normalize('.env'))) {
        if (is_file(\$archiveRoot . '/.env.example')) {
            \$copyMissing(\$archiveRoot . '/.env.example', \$normalize('.env'));
        } else {
            \$ensureDir(dirname(\$normalize('.env')));
            file_put_contents(\$normalize('.env'), '');
        }
    }

    foreach (['public', 'runtime'] as \$dir) {
        \$ensureDir(\$normalize(\$dir));
    }
    \$syncRuntimeEnv();

    foreach (\$extractDirs as \$dir) {
        if (\$dir === '') {
            continue;
        }
        \$copyMissing(\$archiveRoot . '/' . \$dir, \$normalize(\$dir));
    }

    foreach (\$mountDirs as \$dir) {
        if (\$dir === '') {
            continue;
        }
        \$target = \$normalize(\$dir);
        \$ensureDir(\$target);
        \\Phar::mount(\$dir, \$target);
    }

    foreach (\$mountFiles as \$file) {
        if (\$file === '') {
            continue;
        }
        \$target = \$normalize(\$file);
        \$ensureDir(dirname(\$target));
        if (!is_file(\$target)) {
            file_put_contents(\$target, '');
        }
        \\Phar::mount(\$file, \$target);
    }

    \$syncRuntimeEnv();
})();

require __DIR__ . DIRECTORY_SEPARATOR . {$exportMain};
PHP;
    }
}
