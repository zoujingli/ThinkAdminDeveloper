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
use think\admin\extend\CodeToolkit;

/**
 * @internal
 * @coversNothing
 */
class CodeTest extends TestCase
{
    public function testUuidCreate()
    {
        $uuid = CodeToolkit::uuid();
        $this->assertNotEmpty(preg_match('|^[a-z0-9]{8}-([a-z0-9]{4}-){3}[a-z0-9]{12}$|i', $uuid));
    }

    public function testEncode()
    {
        $value = '235215321351235123dasfdasfasdfas';
        $encode = CodeToolkit::encrypt($value, 'thinkadmin');
        $this->assertEquals($value, CodeToolkit::decrypt($encode, 'thinkadmin'), '验证加密解密');
    }
}
