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
use think\admin\middleware\MultAccess;
use think\admin\runtime\RequestContext;
use think\admin\service\AppService;
use think\admin\service\PluginService;
use think\admin\service\RuntimeService;
use think\App;
use think\Request;
use think\Response;

/**
 * @internal
 * @coversNothing
 */
class MultAccessDispatchTest extends TestCase
{
    private string $projectRoot;

    /** @var list<string> */
    private array $createdFiles = [];

    /** @var list<string> */
    private array $createdDirectories = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = TEST_PROJECT_ROOT;
        $this->resetRuntimeContext();
    }

    protected function tearDown(): void
    {
        $this->cleanupPaths();
        $this->resetRuntimeContext();
        parent::tearDown();
    }

    public function testLocalAppsAreDiscoveredWithoutTreatingSharedDirectoriesAsApps(): void
    {
        $this->createLocalApp('demoapp');
        $this->bootApplication();

        $locals = AppService::local(true);
        $this->assertArrayHasKey('index', $locals);
        $this->assertArrayHasKey('demoapp', $locals);
        $this->assertArrayNotHasKey('controller', $locals);
        $this->assertArrayNotHasKey('model', $locals);

        $payload = $this->dispatchPath($this->bootApplication(), 'demoapp/dashboard/index');
        $this->assertSame('demoapp', $payload['app']);
        $this->assertSame('', $payload['plugin']);
        $this->assertSame(RequestContext::ENTRY_WEB, $payload['entry']);
        $this->assertSame('/demoapp', $payload['root']);
        $this->assertSame('dashboard/index', $payload['pathinfo']);
    }

    public function testExplicitPluginPrefixStillWinsOverGlobalRoutes(): void
    {
        $this->createLocalApp('demoapp');
        $this->createRouteFile('multaccess_explicit_plugin.php', <<<'PHP'
<?php

use think\facade\Route;

Route::bindApp('system/login', 'dashboard/index', 'demoapp');
PHP);

        $payload = $this->dispatchPath($this->bootApplication(), 'system/login');
        $this->assertSame('system', $payload['app']);
        $this->assertSame('system', $payload['plugin']);
        $this->assertSame(RequestContext::ENTRY_WEB, $payload['entry']);
        $this->assertSame('/system', $payload['root']);
        $this->assertSame('login', $payload['pathinfo']);
    }

    public function testGlobalRoutesCanBindLocalAndPluginTargets(): void
    {
        $this->createLocalApp('demoapp');
        $this->createRouteFile('multaccess_targets.php', <<<'PHP'
<?php

use think\admin\runtime\RequestContext;
use think\facade\Route;

Route::bindApp('shortcut', 'dashboard/index', 'demoapp');
Route::bindPlugin('open-upload', 'api.upload/file', 'system', RequestContext::ENTRY_API);
PHP);

        $local = $this->dispatchPath($this->bootApplication(), 'shortcut');
        $this->assertSame('demoapp', $local['app']);
        $this->assertSame('', $local['plugin']);
        $this->assertSame(RequestContext::ENTRY_WEB, $local['entry']);
        $this->assertSame('shortcut', $local['pathinfo']);

        $plugin = $this->dispatchPath($this->bootApplication(), 'open-upload');
        $this->assertSame('system', $plugin['app']);
        $this->assertSame('system', $plugin['plugin']);
        $this->assertSame(RequestContext::ENTRY_API, $plugin['entry']);
        $this->assertSame('open-upload', $plugin['pathinfo']);
    }

    public function testGlobalRouteGroupsCanDeclareDispatchTarget(): void
    {
        $this->createLocalApp('demoapp');
        $this->createRouteFile('multaccess_groups.php', <<<'PHP'
<?php

use think\admin\runtime\RequestContext;
use think\facade\Route;

Route::appGroup('demoapp', function () {
    Route::get('group-shortcut', 'dashboard/index');
});

Route::pluginGroup('system', function () {
    Route::get('group-open-upload', 'api.upload/file');
}, RequestContext::ENTRY_API);
PHP);

        $local = $this->dispatchPath($this->bootApplication(), 'group-shortcut');
        $this->assertSame('demoapp', $local['app']);
        $this->assertSame('', $local['plugin']);
        $this->assertSame(RequestContext::ENTRY_WEB, $local['entry']);

        $plugin = $this->dispatchPath($this->bootApplication(), 'group-open-upload');
        $this->assertSame('system', $plugin['app']);
        $this->assertSame('system', $plugin['plugin']);
        $this->assertSame(RequestContext::ENTRY_API, $plugin['entry']);
    }

    public function testLegacyModuleStyleGlobalRoutesCanStillInferDispatchTarget(): void
    {
        $this->createLocalApp('demoapp');
        $this->createRouteFile('multaccess_legacy_targets.php', <<<'PHP'
<?php

use think\facade\Route;

Route::rule('legacy-demo', 'demoapp/dashboard/index');
Route::rule('legacy-system', 'system/login/index');
PHP);

        $local = $this->dispatchPath($this->bootApplication(), 'legacy-demo');
        $this->assertSame('demoapp', $local['app']);
        $this->assertSame('', $local['plugin']);
        $this->assertSame(RequestContext::ENTRY_WEB, $local['entry']);

        $plugin = $this->dispatchPath($this->bootApplication(), 'legacy-system');
        $this->assertSame('system', $plugin['app']);
        $this->assertSame('system', $plugin['plugin']);
        $this->assertSame(RequestContext::ENTRY_WEB, $plugin['entry']);
    }

    private function bootApplication(): App
    {
        $this->resetRuntimeContext();

        $app = new App($this->projectRoot);
        RuntimeService::init($app);
        $app->initialize();
        $app->config->set(['with_route' => true], 'app');
        $app->config->set(['default_app' => 'index'], 'route');

        return $app;
    }

    /**
     * @return array<string, string>
     */
    private function dispatchPath(App $app, string $pathinfo): array
    {
        $request = $app->make('request', [], true);
        $request->setRoot('');
        $request->setPathinfo($pathinfo);
        $app->instance('request', $request);

        $response = (new MultAccess($app))->handle($request, static function (Request $request) use ($app): Response {
            return Response::create([
                'app' => $app->http->getName(),
                'plugin' => RequestContext::instance()->pluginCode(),
                'entry' => RequestContext::instance()->entryType(),
                'root' => $request->root(),
                'pathinfo' => $request->pathinfo(),
            ], 'json');
        });

        return (array)json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function createLocalApp(string $code): void
    {
        $base = $this->projectRoot . '/app/' . $code;
        $controllerPath = $base . '/controller';
        if (!is_dir($controllerPath)) {
            mkdir($controllerPath, 0777, true);
        }

        $this->createdDirectories[] = $base;

        $file = $controllerPath . '/Index.php';
        file_put_contents($file, str_replace('__CODE__', $code, <<<'PHP'
<?php

declare(strict_types=1);

namespace app\__CODE__\controller;

class Index
{
}
PHP));
        $this->createdFiles[] = $file;
    }

    private function createRouteFile(string $name, string $content): void
    {
        $file = $this->projectRoot . '/route/' . $name;
        file_put_contents($file, $content);
        $this->createdFiles[] = $file;
    }

    private function cleanupPaths(): void
    {
        foreach (array_reverse($this->createdFiles) as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        foreach (array_reverse(array_unique($this->createdDirectories)) as $directory) {
            $this->removeDirectory($directory);
        }
    }

    private function removeDirectory(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($target)) {
                $this->removeDirectory($target);
            } elseif (is_file($target)) {
                @unlink($target);
            }
        }

        @rmdir($path);
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
