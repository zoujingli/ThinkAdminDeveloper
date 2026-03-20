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
use plugin\worker\service\WorkerExceptionHandle;
use think\admin\service\RuntimeService;
use think\App;

/**
 * @internal
 * @coversNothing
 */
class WorkerExceptionHandleTest extends TestCase
{
    public function testDebugJsonResponseSanitizesUnsupportedTraceTypes(): void
    {
        $app = new App(WORKER_TEST_PROJECT_ROOT);
        RuntimeService::init($app);
        $app->debug(true);

        $request = $app->request->withServer(['HTTP_ACCEPT' => 'application/json']);
        $app->instance('request', $request);

        $handler = new WorkerExceptionHandle($app);
        $previous = ini_get('zend.exception_ignore_args');
        ini_set('zend.exception_ignore_args', '0');
        $stream = fopen('php://memory', 'r');

        try {
            try {
                $this->throwWithResource($stream);
                $this->fail('Expected runtime exception was not thrown.');
            } catch (\RuntimeException $exception) {
                if (empty($exception->getTrace()[0]['args'] ?? [])) {
                    $this->markTestSkipped('Exception arguments are unavailable in this PHP configuration.');
                }

                $content = $handler->render($request, $exception)->getContent();
            }
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
            if ($previous !== false) {
                ini_set('zend.exception_ignore_args', $previous);
            }
        }

        $this->assertJson($content);
        $this->assertStringContainsString('"message":"worker boom"', $content);
        $this->assertStringContainsString('[resource(stream)]', $content);
    }

    private function throwWithResource($stream): void
    {
        throw new \RuntimeException('worker boom');
    }
}
