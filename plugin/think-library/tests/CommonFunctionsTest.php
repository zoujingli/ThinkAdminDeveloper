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
use think\admin\route\Url;
use think\admin\runtime\RequestContext;
use think\admin\service\AppService;
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

    public function testSysuriAndUrlBuildSupportShortWebPaths(): void
    {
        $this->assertSame('/system', Url::normalizeWebTarget('system/index/index'));
        $this->assertSame('/center?from=force', Url::normalizeWebTarget('/center/index/index?from=force'));
        $this->assertSame('/center/layout?encode=test', Url::normalizeWebTarget('/center/layout?encode=test'));
        $this->assertSame('/system.html', sysuri('system/index/index'));
        $this->assertSame('/system.html', sysuri('/system/index/index'));
        $this->assertSame('/system/login.html', sysuri('/system/login/index'));
        $this->assertSame('/center/layout?encode=test', sysuri('/center/layout', ['encode' => 'test'], false));
        $this->assertSame('/center.html?from=force', sysuri('/center/index/index', ['from' => 'force']));
        $this->assertSame('/center.html', sysuri('/center/index/index'));
        $this->assertSame('/center.html', url('center/index/index')->build());
        $this->assertSame('/center.html', url('/center/index/index')->build());

        AppService::activatePlugin('system', 'system');

        $this->assertSame('/api/system/upload/file', Url::normalizeApiTarget('upload/file'));
        $this->assertSame('/api/system/upload/file?from=test', Url::normalizeApiTarget('/api/system/upload/file?from=test'));
        $this->assertSame('/api/system/upload/index', Url::normalizeApiTarget('/system/upload'));
        $this->assertSame('/api/system/upload/file', Url::normalizeApiTarget('api/upload/file'));
        $this->assertSame('/api/system/upload/file.html', apiuri('upload/file'));
        $this->assertSame('/api/system/upload/file.html', apiuri('/api/system/upload/file'));
        $this->assertSame('/api/system/upload/index.html', apiuri('/system/upload'));
        $this->assertSame('/api/system/upload/file.html', apiuri('api/upload/file'));
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
        function_exists('test_reset_model_makers') && test_reset_model_makers();
    }
}
