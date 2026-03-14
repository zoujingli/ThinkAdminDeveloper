<?php

declare(strict_types=1);

namespace think\admin\tests;

use PHPUnit\Framework\TestCase;
use think\admin\service\ProcessService;

/**
 * @internal
 * @coversNothing
 */
class ProcessServiceTest extends TestCase
{
    public function testMessageFallsBackWithoutWorkerBinding(): void
    {
        ob_start();
        ProcessService::message('library-process-fallback');
        $output = ob_get_clean();

        $this->assertStringContainsString('library-process-fallback', (string) $output);
    }
}
