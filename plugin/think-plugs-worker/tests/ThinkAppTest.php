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

namespace plugin\worker\tests;

use PHPUnit\Framework\TestCase;
use plugin\worker\service\ThinkApp;
use Workerman\Protocols\Http\Response as WorkerResponse;

/**
 * @internal
 * @coversNothing
 */
class ThinkAppTest extends TestCase
{
    public function testFallbackExceptionResponseReturnsStablePlainTextPayload(): void
    {
        $app = new ThinkApp(WORKER_TEST_PROJECT_ROOT);
        $app->debug(true);

        $origin = new \RuntimeException('origin failure');
        $render = new \InvalidArgumentException('render failure');

        $method = new \ReflectionMethod(ThinkApp::class, 'fallbackExceptionResponse');
        $method->setAccessible(true);

        /** @var WorkerResponse $response */
        $response = $method->invoke($app, $origin, $render);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('text/plain; charset=utf-8', $response->getHeader('Content-Type'));
        $this->assertSame('x-server', $response->getHeader('Server'));
        $this->assertStringContainsString('RuntimeException: origin failure', $response->rawBody());
        $this->assertStringContainsString('While rendering exception: InvalidArgumentException: render failure', $response->rawBody());
    }
}
