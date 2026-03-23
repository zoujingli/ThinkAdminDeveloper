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
use think\admin\service\ImageSliderVerify;
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

    public function testPlguriIsLoadedFromSystemPackage(): void
    {
        $this->assertTrue(function_exists('plguri'));

        $reflection = new \ReflectionFunction('plguri');

        $this->assertSame(
            realpath(TEST_PROJECT_ROOT . '/plugin/think-plugs-system/src/common.php'),
            realpath((string)$reflection->getFileName())
        );
    }

    public function testSysuriAndUrlBuildSupportShortWebPaths(): void
    {
        $this->assertSame('/system', Url::normalizeWebTarget('system/index/index'));
        $this->assertSame('/system/plugin?from=force', Url::normalizeWebTarget('/system/plugin/index?from=force'));
        $this->assertSame('/system/plugin/layout?encode=test', Url::normalizeWebTarget('/system/plugin/layout?encode=test'));
        $this->assertSame('/system.html', sysuri('system/index/index'));
        $this->assertSame('/system.html', sysuri('/system/index/index'));
        $this->assertSame('/system/login.html', sysuri('/system/login/index'));
        $this->assertSame('/system/plugin/layout?encode=test', sysuri('/system/plugin/layout', ['encode' => 'test'], false));
        $this->assertSame('/system/plugin.html?from=force', sysuri('/system/plugin/index', ['from' => 'force']));
        $this->assertSame('/system/plugin.html', sysuri('/system/plugin/index'));
        $this->assertSame('/system/plugin.html', url('system/plugin/index')->build());
        $this->assertSame('/system/plugin.html', url('/system/plugin/index')->build());

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

    public function testImageSliderVerifyFallsBackWhenSourceImageIsMissing(): void
    {
        $image = ImageSliderVerify::render(TEST_PROJECT_ROOT . '/runtime/missing-slider-' . uniqid('', true) . '.jpg', 60);
        $background = base64_decode(substr($image['bgimg'], strlen('data:image/png;base64,')), true);
        $piece = base64_decode(substr($image['water'], strlen('data:image/png;base64,')), true);

        $this->assertStringStartsWith('V', $image['code']);
        $this->assertSame(600, $image['width']);
        $this->assertSame(300, $image['height']);
        $this->assertSame(100, $image['piece_width']);
        $this->assertNotFalse($background);
        $this->assertNotFalse($piece);
        $this->assertSame([600, 300], array_slice(getimagesizefromstring($background) ?: [0, 0], 0, 2));
        $this->assertSame([100, 300], array_slice(getimagesizefromstring($piece) ?: [0, 0], 0, 2));
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
