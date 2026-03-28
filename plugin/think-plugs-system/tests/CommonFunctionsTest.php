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

namespace plugin\system\tests;

use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
class CommonFunctionsTest extends TestCase
{
    public function testSystemHelpersAreLoadedFromSystemPackage(): void
    {
        foreach (['sysconf', 'sysdata', 'sysoplog'] as $name) {
            $this->assertTrue(function_exists($name), "{$name} should be autoloaded");

            $reflection = new \ReflectionFunction($name);

            $this->assertSame(
                realpath(SYSTEM_TEST_PACKAGE_ROOT . '/src/common.php'),
                realpath((string)$reflection->getFileName())
            );
        }
    }

    public function testInputRightIconStopsMouseAndTouchStartPropagation(): void
    {
        $files = [
            TEST_PROJECT_ROOT . '/public/static/system.js',
            TEST_PROJECT_ROOT . '/plugin/think-plugs-static/stc/public/static/system.js',
        ];

        foreach ($files as $file) {
            $this->assertFileExists($file);

            $content = file_get_contents($file) ?: '';

            $this->assertStringContainsString("$.base.onEvent('mousedown', '.input-right-icon', function (event) {", $content);
            $this->assertStringContainsString("$.base.onEvent('touchstart', '.input-right-icon', function (event) {", $content);
            $this->assertStringContainsString('event.preventDefault();', $content);
            $this->assertStringContainsString('event.stopPropagation();', $content);
        }
    }
}
