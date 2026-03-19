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
use think\admin\runtime\RequestTokenService;
use think\admin\service\RuntimeService;
use think\App;

/**
 * @internal
 * @coversNothing
 */
class RequestTokenServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $app = new App(TEST_PROJECT_ROOT);
        RuntimeService::init($app);
    }

    public function testDecodeCookieTokenSupportsWorkerEncodedCookie(): void
    {
        $token = 'worker-token';
        $cookie = RequestTokenService::encodeCookieToken($token);

        $this->assertSame($token, RequestTokenService::decodeCookieToken(rawurlencode($cookie)));
    }

    public function testDecodeCookieTokenKeepsPlainTokenUntouched(): void
    {
        $this->assertSame('plain-token', RequestTokenService::decodeCookieToken('plain-token'));
    }
}
