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

namespace plugin\helper\service;

use think\admin\service\PluginService;

final class PluginRegistry
{
    /**
     * @return array<string, array{
     *     title:string,
     *     target:string,
     *     file:string,
     *     class:string,
     *     name:string,
     *     tables:array<int, string>,
     *     export_empty:bool
     * }>
     */
    public static function all(): array
    {
        $items = [];
        foreach (PluginService::all(true, true) as $code => $plugin) {
            if (!is_array($config = self::detect($plugin))) {
                continue;
            }
            $items[$code] = $config;
        }

        ksort($items);
        return $items;
    }

    /**
     * @return array<string, array{
     *     title:string,
     *     target:string,
     *     file:string,
     *     class:string,
     *     name:string,
     *     tables:array<int, string>,
     *     export_empty:bool
     * }>
     */
    public static function selected(array $plugins = []): array
    {
        $items = self::all();
        if (empty($plugins)) {
            return $items;
        }

        $selected = [];
        foreach ($plugins as $plugin) {
            $plugin = trim(strval($plugin));
            if ($plugin === '') {
                continue;
            }
            foreach ($items as $code => $config) {
                if (self::matchSelectedPlugin($plugin, $code)) {
                    $selected[$code] = $config;
                }
            }
        }

        return $selected;
    }

    public static function matchPlugin(string $table): ?string
    {
        foreach (self::all() as $plugin => $config) {
            if (in_array($table, $config['tables'], true)) {
                return $plugin;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $plugin
     * @return null|array{
     *     title:string,
     *     target:string,
     *     file:string,
     *     class:string,
     *     name:string,
     *     tables:array<int, string>,
     *     export_empty:bool
     * }
     */
    private static function detect(array $plugin): ?array
    {
        $base = rtrim(strval($plugin['path'] ?? ''), DIRECTORY_SEPARATOR);
        if ($base === '') {
            return null;
        }

        $meta = self::resolveMigrateMeta($base);
        $dir = dirname($base) . DIRECTORY_SEPARATOR . 'stc' . DIRECTORY_SEPARATOR . 'database';
        if (!is_dir($dir)) {
            return null;
        }

        $files = array_values(array_filter(glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [], 'is_file'));
        sort($files);
        if (empty($files)) {
            return null;
        }

        $primary = self::resolvePrimaryFile($files, $meta);
        $root = rtrim(app()->getRootPath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $target = str_starts_with($dir, $root) ? substr($dir, strlen($root)) : $dir;
        $tables = self::extractTables($files);

        return [
            'title' => strval($plugin['name'] ?? ($plugin['code'] ?? '')),
            'target' => str_replace(DIRECTORY_SEPARATOR, '/', trim($target, DIRECTORY_SEPARATOR)),
            'file' => strval($meta['file'] ?? basename($primary)),
            'class' => strval($meta['class'] ?? self::extractClass($primary)),
            'name' => strval($meta['name'] ?? self::extractName($primary)),
            'tables' => $tables,
            'export_empty' => !empty($meta['export_empty']),
        ];
    }

    /**
     * @param string[] $files
     */
    private static function resolvePrimaryFile(array $files, array $meta = []): string
    {
        if (!empty($meta['file'])) {
            foreach ($files as $file) {
                if (basename($file) === $meta['file']) {
                    return $file;
                }
            }
        }

        foreach ($files as $file) {
            if (str_contains(strtolower(basename($file)), 'install')) {
                return $file;
            }
        }

        return $files[0];
    }

    private static function extractClass(string $file): string
    {
        $content = strval(@file_get_contents($file));
        if (preg_match('/class\s+([A-Za-z_][A-Za-z0-9_]*)\s+extends\s+Migrator/', $content, $match)) {
            return $match[1];
        }

        return pathinfo($file, PATHINFO_FILENAME);
    }

    private static function extractName(string $file): string
    {
        $content = strval(@file_get_contents($file));
        if (preg_match("/function\\s+getName\\s*\\(\\)\\s*:\\s*string\\s*\\{.*?return\\s+'([^']+)'/s", $content, $match)) {
            return $match[1];
        }

        return self::extractClass($file);
    }

    /**
     * @param string[] $files
     * @return string[]
     */
    private static function extractTables(array $files): array
    {
        $tables = [];
        foreach ($files as $file) {
            $content = strval(@file_get_contents($file));
            if ($content === '') {
                continue;
            }

            if (preg_match_all('/@table\s+([a-zA-Z0-9_]+)/', $content, $matches)) {
                foreach ($matches[1] as $table) {
                    $tables[] = $table;
                }
            }

            if (preg_match_all('/(?:->table|table)\(\s*(?:\$this\s*,\s*)?\'([a-zA-Z0-9_]+)\'/', $content, $matches)) {
                foreach ($matches[1] as $table) {
                    $tables[] = $table;
                }
            }
        }

        $tables = array_values(array_unique(array_filter(array_map('strval', $tables))));
        sort($tables);
        return $tables;
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveMigrateMeta(string $base): array
    {
        $composer = dirname($base) . DIRECTORY_SEPARATOR . 'composer.json';
        if (!is_file($composer)) {
            return [];
        }

        $manifest = json_decode(strval(@file_get_contents($composer)), true);
        return is_array($manifest) ? (array)($manifest['extra']['xadmin']['migrate'] ?? []) : [];
    }

    private static function matchSelectedPlugin(string $plugin, string $code): bool
    {
        $alias = preg_replace('/^plugin-/', '', $code) ?: $code;
        return in_array($plugin, [$code, $alias], true);
    }
}
