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
use think\admin\service\Storage;

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
