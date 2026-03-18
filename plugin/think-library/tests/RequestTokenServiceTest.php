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
