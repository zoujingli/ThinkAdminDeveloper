<?php

declare(strict_types=1);

namespace plugin\worker\tests;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

/**
 * @internal
 * @coversNothing
 */
class CommonFunctionsTest extends TestCase
{
    public function testSysqueueIsLoadedFromWorkerPackage(): void
    {
        $this->assertTrue(function_exists('sysqueue'));

        $reflection = new ReflectionFunction('sysqueue');

        $this->assertSame(
            realpath(WORKER_TEST_PACKAGE_ROOT . '/src/common.php'),
            realpath((string) $reflection->getFileName())
        );
    }
}
