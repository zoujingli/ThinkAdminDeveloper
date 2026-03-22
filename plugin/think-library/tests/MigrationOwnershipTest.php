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

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class MigrationOwnershipTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = TEST_PROJECT_ROOT;
    }

    public function testSystemTablesAreOwnedBySystemPluginMigration(): void
    {
        $owner = $this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php');
        $this->assertFileExists($owner);

        $content = $this->read($owner);
        foreach (['system_base', 'system_data', 'system_oplog', 'system_auth', 'system_auth_node', 'system_menu', 'system_user'] as $table) {
            $this->assertStringContainsString($table, $content);
        }
    }

    public function testSharedTablesAreOwnedBySystemAndWorkerPlugins(): void
    {
        $system = $this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php');
        $worker = $this->path('plugin/think-plugs-worker/stc/database/20241010000008_install_worker20241010.php');

        $this->assertFileExists($system);
        $this->assertFileExists($worker);

        $this->assertStringContainsString('system_file', $this->read($system));
        $this->assertStringContainsString('system_queue', $this->read($worker));
    }

    public function testSystemPluginUsesSingleInstallMigration(): void
    {
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system_manage20241010.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/stc/database/20241010000011_install_system20241010.php'));
    }

    public function testEveryPluginKeepsOnlyOnePrimaryMigrationFile(): void
    {
        $plugins = [
            'account',
            'payment',
            'system',
            'wechat-client',
            'wechat-service',
            'wemall',
            'worker',
            'wuma',
        ];

        foreach ($plugins as $plugin) {
            $files = glob($this->path("plugin/think-plugs-{$plugin}/stc/database/*.php")) ?: [];
            sort($files);
            $this->assertCount(1, $files, "plugin {$plugin} should keep exactly one migration file");
            $this->assertStringContainsString('_install_', basename($files[0]));
        }
    }

    public function testSharedMigrationTablesDoNotLeakToOtherPlugins(): void
    {
        $owners = [
            'system_base' => $this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php'),
            'system_data' => $this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php'),
            'system_oplog' => $this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php'),
            'system_file' => $this->path('plugin/think-plugs-system/stc/database/20241010000001_install_system20241010.php'),
            'system_queue' => $this->path('plugin/think-plugs-worker/stc/database/20241010000008_install_worker20241010.php'),
        ];

        $migrations = $this->migrationFiles();
        $violations = [];

        foreach ($migrations as $file) {
            $content = $this->read($file);
            foreach ($owners as $table => $owner) {
                if ($file === $owner) {
                    continue;
                }
                if (strpos($content, $table) !== false) {
                    $violations[] = [$table, $file];
                }
            }
        }

        $this->assertSame([], $violations, 'Unexpected migration ownership violations: ' . json_encode($violations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob($this->path('plugin/*/stc/database/*.php')) ?: [];
        sort($files);
        return array_values($files);
    }

    private function read(string $path): string
    {
        return file_get_contents($path) ?: '';
    }

    private function path(string $relative): string
    {
        return $this->projectRoot . '/' . ltrim($relative, '/');
    }
}
