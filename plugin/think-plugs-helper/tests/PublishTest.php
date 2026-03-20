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

namespace plugin\helper\tests;

use PHPUnit\Framework\TestCase;
use plugin\helper\command\Publish;
use think\admin\service\RuntimeService;
use think\App;
use think\console\Command;
use think\console\Input;
use think\console\Output;

/**
 * @internal
 * @coversNothing
 */
class PublishTest extends TestCase
{
    private const MIGRATION_MANIFEST = '.xadmin-published.json';

    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/thinkadmin-helper-test-' . bin2hex(random_bytes(6));
        mkdir($this->root . '/vendor/composer', 0777, true);
        $this->createPluginPackage('demo', 'vendor/demo-plugin', 'plugin\demo\Service');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
    }

    public function testDiscoverWorkspacePackagesFindsLocalPluginComposer(): void
    {
        $command = new Publish();
        function_exists('test_reset_model_makers') && test_reset_model_makers();
        $app = new App($this->root);
        RuntimeService::init($app);
        $command->setApp($app);

        $method = new \ReflectionMethod($command, 'discoverWorkspacePackages');
        $method->setAccessible(true);
        $items = $method->invoke($command);

        $this->assertCount(1, $items);
        $this->assertSame('vendor/demo-plugin', $items[0]['name']);
        $this->assertSame(str_replace('\\', '/', $this->root . '/plugin/demo'), $items[0]['__path']);
    }

    public function testRunPublishesWorkspaceServicesAndMigrations(): void
    {
        $this->createPluginPackage(
            'system',
            'vendor/system-plugin',
            'plugin\system\Service',
            '20241010000001_install_system20241010.php'
        );
        $this->createPluginPackage(
            'storage',
            'vendor/storage-plugin',
            'plugin\storage\Service',
            '20241010000002_install_storage20241010.php'
        );
        $this->createPluginPackage(
            'worker',
            'vendor/worker-plugin',
            'plugin\worker\Service',
            '20241010000008_install_worker20241010.php'
        );

        $command = $this->newCommand();
        $code = $command->run(new Input([]), new Output('buffer'));

        $services = require $this->root . '/vendor/services.php';
        $versions = require $this->root . '/vendor/versions.php';
        $manifest = json_decode((string)file_get_contents($this->root . '/database/migrations/.xadmin-published.json'), true);

        $this->assertSame(0, $code);
        $this->assertContains('plugin\demo\Service', $services);
        $this->assertContains('plugin\system\Service', $services);
        $this->assertContains('plugin\storage\Service', $services);
        $this->assertContains('plugin\worker\Service', $services);
        $this->assertSame('System', $versions['vendor/system-plugin']['name']);
        $this->assertSame('plugin', $versions['vendor/storage-plugin']['type']);
        $this->assertFileExists($this->root . '/database/migrations/20241010000002_install_storage20241010.php');
        $this->assertFileExists($this->root . '/database/migrations/20241010000008_install_worker20241010.php');
        $this->assertFileExists($this->root . '/database/migrations/20241010000001_install_system20241010.php');
        $this->assertSame(
            'plugin/system/stc/database/20241010000001_install_system20241010.php',
            $manifest['20241010000001_install_system20241010.php']['source']
        );
    }

    public function testSyncMigrationsRemovesStalePublishedFiles(): void
    {
        $this->createPluginPackage(
            'system',
            'vendor/system-plugin',
            'plugin\system\Service',
            '20241010000001_install_system20241010.php'
        );

        $targetDir = $this->root . '/database/migrations';
        mkdir($targetDir, 0777, true);
        file_put_contents($targetDir . '/20241010000002_install_storage20241010.php', "<?php\n");
        file_put_contents($targetDir . '/20241010000001_install_system20241010.php', "<?php\n");
        file_put_contents($targetDir . '/' . self::MIGRATION_MANIFEST, json_encode([
            '20241010000002_install_storage20241010.php' => [
                'source' => 'plugin/storage/stc/database/20241010000002_install_storage20241010.php',
                'mtime' => 1,
            ],
            '20241010000001_install_system20241010.php' => [
                'source' => 'plugin/system/stc/database/20241010000001_install_system20241010.php',
                'mtime' => 1,
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $command = $this->newCommand();
        $this->invokeSyncMigrations($command, [
            '20241010000001_install_system20241010.php' => $this->root . '/plugin/system/stc/database/20241010000001_install_system20241010.php',
        ]);

        $manifest = json_decode((string)file_get_contents($targetDir . '/' . self::MIGRATION_MANIFEST), true);

        $this->assertFileDoesNotExist($targetDir . '/20241010000002_install_storage20241010.php');
        $this->assertFileExists($targetDir . '/20241010000001_install_system20241010.php');
        $this->assertArrayNotHasKey('20241010000002_install_storage20241010.php', $manifest);
        $this->assertArrayHasKey('20241010000001_install_system20241010.php', $manifest);
    }

    public function testSyncMigrationsRejectsDuplicateVersions(): void
    {
        $this->createPluginPackage('system-a', 'vendor/system-a', 'plugin\systema\Service', '20241010000011_install_system_a.php');
        $this->createPluginPackage('system-b', 'vendor/system-b', 'plugin\systemb\Service', '20241010000011_install_system_b.php');

        $command = $this->newCommand();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(
            'Duplicate migration version [20241010000011] between [20241010000011_install_system_a.php] and [20241010000011_install_system_b.php]'
        );

        $this->invokeSyncMigrations($command, [
            '20241010000011_install_system_a.php' => $this->root . '/plugin/system-a/stc/database/20241010000011_install_system_a.php',
            '20241010000011_install_system_b.php' => $this->root . '/plugin/system-b/stc/database/20241010000011_install_system_b.php',
        ]);
    }

    public function testSyncMigrationsRemovesConflictingLegacyVersionFile(): void
    {
        $this->createPluginPackage(
            'system',
            'vendor/system-plugin',
            'plugin\system\Service',
            '20241010000001_install_system20241010.php'
        );

        $targetDir = $this->root . '/database/migrations';
        mkdir($targetDir, 0777, true);
        file_put_contents($targetDir . '/20241010000001_install_legacy_system.php', "<?php\n// legacy\n");

        $command = $this->newCommand();
        $this->invokeSyncMigrations($command, [
            '20241010000001_install_system20241010.php' => $this->root . '/plugin/system/stc/database/20241010000001_install_system20241010.php',
        ]);

        $manifest = json_decode((string)file_get_contents($targetDir . '/' . self::MIGRATION_MANIFEST), true);

        $this->assertFileDoesNotExist($targetDir . '/20241010000001_install_legacy_system.php');
        $this->assertFileExists($targetDir . '/20241010000001_install_system20241010.php');
        $this->assertSame(
            'plugin/system/stc/database/20241010000001_install_system20241010.php',
            $manifest['20241010000001_install_system20241010.php']['source']
        );
    }

    private function createPluginPackage(string $code, string $name, string $service, ?string $migration = null): void
    {
        $path = "{$this->root}/plugin/{$code}";
        mkdir($path, 0777, true);
        file_put_contents($path . '/composer.json', json_encode([
            'name' => $name,
            'type' => 'think-admin-plugin',
            'version' => '1.0.0',
            'description' => ucfirst($code),
            'extra' => [
                'think' => ['services' => [$service]],
                'xadmin' => ['service' => ['code' => $code, 'name' => ucfirst($code)]],
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        if ($migration) {
            mkdir($path . '/stc/database', 0777, true);
            file_put_contents($path . '/stc/database/' . $migration, "<?php\n");
        }
    }

    private function newCommand(): Publish
    {
        $command = new Publish();
        function_exists('test_reset_model_makers') && test_reset_model_makers();
        $app = new App($this->root);
        RuntimeService::init($app);
        $app->config->set([
            'default' => 'file',
            'stores' => [
                'file' => ['type' => 'File', 'path' => $this->root . '/runtime/cache'],
            ],
        ], 'cache');
        $command->setApp($app);

        return $command;
    }

    /**
     * @param array<string, string> $sources
     */
    private function invokeSyncMigrations(Publish $command, array $sources, bool $force = false): void
    {
        $property = new \ReflectionProperty(Command::class, 'output');
        $property->setAccessible(true);
        $property->setValue($command, new Output('buffer'));

        $method = new \ReflectionMethod($command, 'syncMigrations');
        $method->setAccessible(true);
        $method->invoke($command, $sources, $force);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }

        rmdir($path);
    }
}
