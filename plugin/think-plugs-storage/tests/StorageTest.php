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
use think\admin\Storage;

/**
 * @internal
 * @coversNothing
 */
class StorageTest extends TestCase
{
    public function testNameBuildsDeterministicPath(): void
    {
        $url = 'https://example.com/static/logo.png';
        $hash = md5($url);

        $this->assertSame(
            'image/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 30) . '.png',
            Storage::name($url, '', 'image')
        );
    }

    public function testNameSupportsCustomExtensionAndHashFunction(): void
    {
        $source = 'plain-content';
        $hash = sha1($source);

        $this->assertSame(
            'avatar/' . substr($hash, 0, 2) . '/' . substr($hash, 2, 30) . '.jpg',
            Storage::name($source, 'jpg', 'avatar', 'sha1')
        );
    }
}
