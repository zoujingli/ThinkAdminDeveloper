<?php

declare(strict_types=1);

namespace plugin\system\tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

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

            $reflection = new ReflectionFunction($name);

            $this->assertSame(
                realpath(SYSTEM_TEST_PACKAGE_ROOT . '/src/common.php'),
                realpath((string) $reflection->getFileName())
            );
        }
    }
}
