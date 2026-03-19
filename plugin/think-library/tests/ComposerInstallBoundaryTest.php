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
class ComposerInstallBoundaryTest extends TestCase
{
    public function testRootComposerKeepsServiceDiscoverHook(): void
    {
        $json = json_decode((string)file_get_contents(TEST_PROJECT_ROOT . '/composer.json'), true);
        $this->assertIsArray($json);

        $scripts = $json['scripts'] ?? [];
        $this->assertIsArray($scripts);
        $this->assertArrayHasKey('post-autoload-dump', $scripts);

        $commands = $scripts['post-autoload-dump'];
        $commands = is_array($commands) ? $commands : [$commands];

        $this->assertContains('@php think service:discover', $commands);
    }
}
