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
class SoftDeleteBoundaryTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = TEST_PROJECT_ROOT;
    }

    public function testLegacySoftDeleteSyncMigrationsHaveBeenRemoved(): void
    {
        $paths = [
            'plugin/think-plugs-account/stc/database/20260319000011_sync_account_soft_delete20260319.php',
            'plugin/think-plugs-payment/stc/database/20260319000012_sync_payment_soft_delete20260319.php',
            'plugin/think-plugs-wemall/stc/database/20260319000013_sync_wemall_soft_delete20260319.php',
            'plugin/think-plugs-wuma/stc/database/20260319000014_sync_wuma_soft_delete20260319.php',
            'database/migrations/20260319000011_sync_account_soft_delete20260319.php',
            'database/migrations/20260319000012_sync_payment_soft_delete20260319.php',
            'database/migrations/20260319000013_sync_wemall_soft_delete20260319.php',
            'database/migrations/20260319000014_sync_wuma_soft_delete20260319.php',
        ];

        foreach ($paths as $path) {
            $file = $this->path($path);
            $this->assertFileDoesNotExist($file);
        }
    }

    public function testWorkspaceNoLongerUsesDeleteTimeFalseAsSoftDeleteEscapeHatch(): void
    {
        $matches = [];
        foreach (glob($this->path('plugin/*/src/model/*.php')) ?: [] as $file) {
            $content = file_get_contents($file) ?: '';
            if (strpos($content, 'deleteTime = false') !== false) {
                $matches[] = $file;
            }
        }

        $this->assertSame([], $matches, 'Models should use plain bases instead of deleteTime=false: ' . json_encode($matches, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    public function testMigrationsDoNotReferenceLegacySoftDeleteColumns(): void
    {
        $matches = [];
        foreach (glob($this->path('plugin/*/stc/database/*.php')) ?: [] as $file) {
            $content = file_get_contents($file) ?: '';
            foreach (['deleted_at', 'deleted_time', "'deleted'", '"deleted"'] as $needle) {
                if (strpos($content, $needle) !== false) {
                    $matches[] = [$file, $needle];
                }
            }
        }

        $this->assertSame([], $matches, 'Legacy soft delete columns found in migrations: ' . json_encode($matches, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function path(string $relative): string
    {
        return $this->projectRoot . '/' . ltrim($relative, '/');
    }
}
