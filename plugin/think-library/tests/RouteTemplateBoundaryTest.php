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

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class RouteTemplateBoundaryTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = TEST_PROJECT_ROOT;
    }

    public function testPhpAndTemplateSourcesDoNotReferenceLegacyAdminRoutesOrViews(): void
    {
        $forbidden = [
            "sysuri('admin/",
            'sysuri("admin/',
            "url('admin/",
            'url("admin/',
            "auth('admin/",
            'auth("admin/',
            'admin/login/index',
            'admin/index/index',
            'admin/config/index',
            'admin/api.',
            'plugin/think-plugs-admin/src/view',
            'view_path' . "' => TEST_PROJECT_ROOT . '/plugin/think-plugs-admin/src/view",
            'view_path" => TEST_PROJECT_ROOT . \'/plugin/think-plugs-admin/src/view',
        ];

        $violations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path('plugin'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['php', 'html'], true)) {
                continue;
            }

            $path = $file->getPathname();
            if ($path === __FILE__) {
                continue;
            }
            $content = file_get_contents($path) ?: '';
            foreach ($forbidden as $needle) {
                if (strpos($content, $needle) !== false) {
                    $violations[] = [$needle, $path];
                }
            }
        }

        $appIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path('app'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($appIterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['php', 'html'], true)) {
                continue;
            }

            $path = $file->getPathname();
            if ($path === __FILE__) {
                continue;
            }
            $content = file_get_contents($path) ?: '';
            foreach ($forbidden as $needle) {
                if (strpos($content, $needle) !== false) {
                    $violations[] = [$needle, $path];
                }
            }
        }

        $testIterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path('tests'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($testIterator as $file) {
            if (!$file->isFile()) {
                continue;
            }

            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }

            $path = $file->getPathname();
            if ($path === __FILE__) {
                continue;
            }
            $content = file_get_contents($path) ?: '';
            foreach ($forbidden as $needle) {
                if (strpos($content, $needle) !== false) {
                    $violations[] = [$needle, $path];
                }
            }
        }

        $this->assertSame([], $violations, 'Legacy admin route or template references found: ' . json_encode($violations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function testSourcesDoNotReferenceLegacyAdminStaticAssets(): void
    {
        $forbidden = [
            'static/admin.js',
            'plugs/admin/',
            'window.taAdmin',
            'ta-system-access-token',
            '__FULL__/admin/',
        ];

        $violations = [];
        foreach ($this->scanFiles([
            $this->path('plugin'),
            $this->path('app'),
            $this->path('tests'),
            $this->path('public'),
            $this->path('docs'),
            $this->path('readme.md'),
            $this->path('composer.json'),
        ], ['php', 'html', 'js', 'md', 'json']) as $path => $content) {
            foreach ($forbidden as $needle) {
                if (strpos($content, $needle) !== false) {
                    $violations[] = [$needle, $path];
                }
            }
        }

        $this->assertSame([], $violations, 'Legacy admin static references found: ' . json_encode($violations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param list<string> $targets
     * @param list<string> $extensions
     * @return array<string, string>
     */
    private function scanFiles(array $targets, array $extensions): array
    {
        $items = [];
        foreach ($targets as $target) {
            if (is_file($target)) {
                if ($target !== __FILE__ && in_array(strtolower(pathinfo($target, PATHINFO_EXTENSION)), $extensions, true)) {
                    $items[$target] = file_get_contents($target) ?: '';
                }
                continue;
            }
            if (!is_dir($target)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($target, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();
                if ($path === __FILE__) {
                    continue;
                }
                if (!in_array(strtolower($file->getExtension()), $extensions, true)) {
                    continue;
                }
                $items[$path] = file_get_contents($path) ?: '';
            }
        }

        ksort($items);
        return $items;
    }

    private function path(string $relative): string
    {
        return $this->projectRoot . '/' . ltrim($relative, '/');
    }
}
