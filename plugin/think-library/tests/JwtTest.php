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
use think\admin\service\JwtToken;

/**
 * @internal
 * @coversNothing
 */
class JwtTest extends TestCase
{
    public function testJwtCreateAndVerify()
    {
        $jwtkey = 'thinkadmin';
        $testdata = ['user' => 'admin' . mt_rand(0, 1000), 'iss' => 'thinkadmin.top', 'exp' => time() + 30];
        $token = JwtToken::token($testdata, $jwtkey);
        $result = JwtToken::verify($token, $jwtkey);
        $this->assertEquals($testdata['user'], $result['user']);
    }
}
