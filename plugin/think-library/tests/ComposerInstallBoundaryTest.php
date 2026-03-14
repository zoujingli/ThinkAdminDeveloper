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
