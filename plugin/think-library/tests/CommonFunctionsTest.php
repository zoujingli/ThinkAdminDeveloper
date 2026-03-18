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
use think\admin\runtime\RequestContext;
use think\admin\service\AppService;
use think\admin\service\PluginService;
use think\admin\service\RuntimeService;
use think\App;

/**
 * @internal
 * @coversNothing
 */
class CommonFunctionsTest extends TestCase
{
    private App $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetRuntimeContext();
        $this->app = new App(TEST_PROJECT_ROOT);
        RuntimeService::init($this->app);
        $this->app->initialize();
        $this->app->config->set(['with_route' => true], 'app');
        $this->app->config->set(['default_app' => 'index'], 'route');

        $request = $this->app->make('request', [], true);
        $request->setRoot('');
        $request->setPathinfo('system/login/index');
        $this->app->instance('request', $request);
    }

    protected function tearDown(): void
    {
        $this->resetRuntimeContext();
        parent::tearDown();
    }

    public function testLibraryHelpersAreLoadedFromLibraryPackage(): void
    {
        foreach (['sysuri', 'apiuri', 'xss_safe', 'format_bytes'] as $name) {
            $this->assertTrue(function_exists($name), "{$name} should be autoloaded");

            $reflection = new \ReflectionFunction($name);

            $this->assertSame(
                realpath(TEST_PROJECT_ROOT . '/plugin/think-library/src/common.php'),
                realpath((string)$reflection->getFileName())
            );
        }
    }

    public function testSysuriSupportsAbsolutePathsAndApiuriBuildsPluginApiUrls(): void
    {
        $this->assertSame('/system/login/index.html', sysuri('/system/login/index'));

        AppService::activate('system', 'system');

        $this->assertSame('/api/system/upload/file.html', apiuri('upload/file'));
        $this->assertSame('/api/system/upload/file.html', apiuri('/api/system/upload/file'));
    }

    public function testXssSafeStripsScriptsAndNeutralizesInlineEvents(): void
    {
        $safe = xss_safe('<div onclick="alert(1)"><script>alert(2)</script>safe</div>');

        $this->assertStringNotContainsString('<script', strtolower($safe));
        $this->assertStringContainsString('data-on-click=', strtolower($safe));
        $this->assertStringContainsString('safe</div>', strtolower($safe));
    }

    public function testFormatBytesSupportsLargeUnits(): void
    {
        $this->assertSame('1 PB', format_bytes(1024 ** 5));
        $this->assertSame('2 KB', format_bytes(2048));
        $this->assertSame('plain', format_bytes('plain'));
    }

    public function testStr2arrAndArr2strSupportArrayAndStringInputs(): void
    {
        $this->assertSame(['a', 'b', 'c'], str2arr('a,b,c'));
        $this->assertSame(['a', 'b', 'c'], str2arr(['a', ' b ', 'c']));
        $this->assertSame(['a', 'b', 'c'], str2arr(['a,b', ['c']]));
        $this->assertSame([1, 2], str2arr([1, 2], ',', [1, 2]));
        $this->assertSame(['a', 'c'], str2arr('a,b,c', ',', ['a', 'c']));

        $this->assertSame(',a,b,c,', arr2str(['a', ' b ', 'c']));
        $this->assertSame(',a,b,c,', arr2str('a,b,c'));
        $this->assertSame(',a,c,', arr2str(['a', 'b', 'c'], ',', ['a', 'c']));
        $this->assertSame('', arr2str(''));
    }

    private function resetRuntimeContext(): void
    {
        AppService::clear();
        RequestContext::clear();
        if (function_exists('sysvar')) {
            sysvar('', '');
        }
    }
}
