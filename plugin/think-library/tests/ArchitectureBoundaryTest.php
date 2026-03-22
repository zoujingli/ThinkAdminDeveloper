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

/**
 * @internal
 * @coversNothing
 */
class ArchitectureBoundaryTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = TEST_PROJECT_ROOT;
    }

    public function testLibraryServiceFilesStayInServiceDirectory(): void
    {
        $this->assertFileExists($this->path('plugin/think-library/src/service/CacheSession.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/QueueService.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/RuntimeService.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/JwtToken.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/NodeService.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/AppService.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/helper/Helper.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/runtime/RequestTokenService.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/helper/FormBuilder.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/helper/PageBuilder.php'));

        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/Helper.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/service/FormBuilder.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/service/PageBuilder.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/service/FormBuilder.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/service/PageBuilder.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/auth/CacheSession.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/auth/RequestTokenService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/system/NodeService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/AppService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/QueueService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/service/ProcessService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/ProcessService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/RuntimeService.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/FaviconBuilder.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/ImageSliderVerify.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/JsonRpcHttpClient.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/service/JsonRpcHttpServer.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/extend/JwtToken.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/extend/FaviconBuilder.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/extend/ImageSliderVerify.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/extend/JsonRpcHttpClient.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/extend/JsonRpcHttpServer.php'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-library/src/system'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-library/src/auth'));
    }

    public function testLibrarySourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-library/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-library/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['contract', 'extend', 'helper', 'middleware', 'model', 'route', 'runtime', 'service'], $dirs);
    }

    public function testLibraryRouteFilesStayInRouteDirectory(): void
    {
        $this->assertFileExists($this->path('plugin/think-library/src/route/Route.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/route/Url.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/Route.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/Url.php'));
    }

    public function testLibraryMiddlewareFilesStayInMiddlewareDirectory(): void
    {
        $this->assertFileExists($this->path('plugin/think-library/src/middleware/MultAccess.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/MultAccess.php'));
    }

    public function testLibraryRuntimeFilesStayContextFocused(): void
    {
        $files = array_values(array_filter(scandir($this->path('plugin/think-library/src/runtime')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_file($this->path("plugin/think-library/src/runtime/{$name}"));
        }));
        sort($files);

        $this->assertSame(['NullSystemContext.php', 'RequestContext.php', 'RequestTokenService.php', 'SystemContext.php'], $files);
    }

    public function testLibraryExtendFilesStayUtilityOnly(): void
    {
        $files = array_values(array_filter(scandir($this->path('plugin/think-library/src/extend')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_file($this->path("plugin/think-library/src/extend/{$name}"));
        }));
        sort($files);

        $this->assertSame(['ArrayTree.php', 'CodeToolkit.php', 'FileTools.php', 'HttpClient.php', 'README.md'], $files);
    }

    public function testCaptchaServiceLivesInSystemServiceDirectory(): void
    {
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/service/CaptchaService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/service/bin/captcha.ttf'));

        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/auth/CaptchaService.php'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-library/src/auth/bin'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-system/src/auth'));
    }

    public function testSystemContextAndAuthSymbolsUseSystemNaming(): void
    {
        $this->assertFileExists($this->path('plugin/think-library/src/contract/SystemContextInterface.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/runtime/SystemContext.php'));
        $this->assertFileExists($this->path('plugin/think-library/src/runtime/NullSystemContext.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/service/SystemContext.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/service/SystemService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/service/SystemAuthService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/middleware/JwtTokenAuth.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/middleware/RbacAccess.php'));

        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/contract/AdminContextInterface.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/AdminContext.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-library/src/runtime/NullAdminContext.php'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-system/src/runtime'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-system/src/system'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/runtime/AdminContext.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/auth/AdminService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/auth/SystemAuthService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/service/JwtTokenAuth.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/service/RbacAccess.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/runtime/SystemContext.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/system/SystemService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-system/src/service/FaviconBuilder.php'));
    }

    public function testSystemPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-system/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-system/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['controller', 'lang', 'middleware', 'model', 'route', 'service', 'storage', 'view'], $dirs);
    }

    /**
     * 存储能力已合并至 system 插件的 src/storage，独立 think-plugs-storage 包已移除。
     */
    public function testSystemPluginStorageSourceLayout(): void
    {
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-storage'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/storage/StorageConfig.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/storage/StorageManager.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/storage/StorageAuthorize.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-system/src/storage/LocalStorage.php'));
    }

    public function testAccountPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-account/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-account/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['controller', 'lang', 'model', 'service', 'view'], $dirs);
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-account/src/service/ImageSliderVerify.php'));
    }

    public function testWorkerPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-worker/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-worker/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['command', 'model', 'service'], $dirs);
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-worker/src/queue'));
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-worker/src/support'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-worker/src/Server.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-worker/src/service/Server.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-worker/src/service/QueueService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-worker/src/service/ProcessService.php'));
    }

    public function testHelperPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-helper/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-helper/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['command', 'database', 'integration', 'migration', 'model', 'plugin', 'service'], $dirs);
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-helper/src/support'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-helper/src/command/DbMigrateStruct.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-helper/src/command/DbModelStruct.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-helper/src/command/DbBackupStruct.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/command/database/MigrateCommand.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/command/database/ModelCommand.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/command/database/BackupCommand.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/plugin/PluginMenuService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/plugin/PluginRegistry.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/migration/PhinxExtend.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-helper/src/integration/ExpressService.php'));
    }

    public function testWechatServicePluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-wechat-service/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-wechat-service/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['command', 'controller', 'lang', 'model', 'service', 'view'], $dirs);
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-wechat-service/src/handle'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wechat-service/src/AuthService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wechat-service/src/ConfigService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-wechat-service/src/service/AuthService.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-wechat-service/src/service/ConfigService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wechat-service/src/service/JsonRpcHttpServer.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-wechat-service/src/service/PublishHandle.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-wechat-service/src/service/ReceiveHandle.php'));
    }

    public function testWechatClientPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-wechat-client/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-wechat-client/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['command', 'controller', 'lang', 'model', 'service', 'view'], $dirs);
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wechat-client/src/service/JsonRpcHttpClient.php'));
    }

    public function testPaymentPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-payment/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-payment/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['controller', 'lang', 'model', 'service', 'view'], $dirs);
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-payment/src/queue'));
        $this->assertFileExists($this->path('plugin/think-plugs-payment/src/service/Recount.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-payment/src/queue/Recount.php'));
    }

    public function testWemallPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-wemall/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-wemall/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['command', 'controller', 'lang', 'model', 'service', 'view'], $dirs);
        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-wemall/src/integration'));
        $this->assertFileExists($this->path('plugin/think-plugs-wemall/src/service/OpenApiService.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wemall/src/integration/OpenApiService.php'));
    }

    public function testWumaPluginSourceDirectoriesStayStandardized(): void
    {
        $dirs = array_values(array_filter(scandir($this->path('plugin/think-plugs-wuma/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_dir($this->path("plugin/think-plugs-wuma/src/{$name}"));
        }));
        sort($dirs);

        $this->assertSame(['command', 'controller', 'lang', 'model', 'service', 'view'], $dirs);
    }

    public function testPluginSourceRootFilesStayMinimal(): void
    {
        $allowed = [
            'think-plugs-account' => ['Service.php'],
            'think-plugs-helper' => ['Service.php'],
            'think-plugs-payment' => ['Service.php'],
            'think-plugs-system' => ['Service.php', 'common.php'],
            'think-plugs-wechat-client' => ['Service.php'],
            'think-plugs-wechat-service' => ['Service.php'],
            'think-plugs-wemall' => ['Service.php', 'common.php'],
            'think-plugs-worker' => ['Service.php', 'common.php'],
            'think-plugs-wuma' => ['Service.php'],
        ];

        foreach ($allowed as $plugin => $expected) {
            $files = array_values(array_filter(scandir($this->path("plugin/{$plugin}/src")) ?: [], function ($name) use ($plugin) {
                return $name !== '.' && $name !== '..' && is_file($this->path("plugin/{$plugin}/src/{$name}"));
            }));
            sort($files);
            sort($expected);
            $this->assertSame($expected, $files, "{$plugin} has unexpected source root files");
        }

        $this->assertDirectoryDoesNotExist($this->path('plugin/think-plugs-center'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wemall/src/helper.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wuma/src/Query.php'));
        $this->assertFileDoesNotExist($this->path('plugin/think-plugs-wuma/src/Script.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-wemall/src/common.php'));
        $this->assertFileExists($this->path('plugin/think-plugs-wuma/src/controller/Query.php'));
    }

    public function testLibrarySourceRootFilesStayFocused(): void
    {
        $files = array_values(array_filter(scandir($this->path('plugin/think-library/src')) ?: [], function ($name) {
            return $name !== '.' && $name !== '..' && is_file($this->path("plugin/think-library/src/{$name}"));
        }));
        sort($files);

        $this->assertSame(['Command.php', 'Controller.php', 'Exception.php', 'Library.php', 'Model.php', 'Plugin.php', 'Service.php', 'Storage.php', 'common.php'], $files);
    }

    public function testPhpSourcesDoNotReferenceLegacyBoundaryNamespaces(): void
    {
        $forbidden = [
            'think\admin\system\NodeService',
            'think\admin\runtime\AppService',
            'think\admin\runtime\ModuleService',
            'think\admin\runtime\PluginService',
            'think\admin\runtime\MultAccess',
            'think\admin\runtime\QueueService',
            'think\admin\runtime\ProcessService',
            'think\admin\runtime\RuntimeService',
            'think\admin\runtime\RuntimeTools',
            'think\admin\runtime\Route',
            'think\admin\runtime\Url',
            'think\admin\service\PluginService',
            'think\admin\service\ProcessService',
            'think\admin\auth\CaptchaService',
            'think\admin\auth\CacheSession',
            'think\admin\auth\RequestTokenService',
            'think\admin\extend\JwtToken',
            'think\admin\extend\FaviconBuilder',
            'think\admin\extend\ImageSliderVerify',
            'think\admin\extend\JsonRpcHttpClient',
            'think\admin\extend\JsonRpcHttpServer',
            'think\admin\Helper',
            'think\admin\contract\AdminContextInterface',
            'think\admin\runtime\AdminContext',
            'think\admin\runtime\NullAdminContext',
            'plugin\system\runtime\AdminContext',
            'plugin\system\runtime\SystemContext',
            'plugin\system\auth\AdminService',
            'plugin\system\auth\\',
            'plugin\system\system\SystemService',
            'plugin\storage\StorageConfig',
            'plugin\storage\StorageManager',
            'plugin\storage\support\StorageAuthorize',
            'think\admin\storage\\',
            'plugin\worker\queue\\',
            'plugin\worker\support\\',
            'plugin\worker\Server',
            'plugin\helper\support\\',
            'plugin\helper\integration\\',
            'plugin\helper\DbBackupStruct',
            'plugin\helper\DbIndexStruct',
            'plugin\helper\DbMigrateStruct',
            'plugin\helper\DbModelStruct',
            'plugin\helper\DbRestoreStruct',
            'plugin\wechat\service\handle\\',
            'plugin\wechat\service\AuthService',
            'plugin\wechat\service\ConfigService',
            'plugin\payment\queue\\',
            'plugin\wemall\integration\\',
            'plugin\wuma\Query',
            'plugin\wuma\Script',
            'usession(',
            'user_session_store',
            'user_session_touch',
            'user_session_expire',
            'user_session_gc_interval',
            'admin_user(',
            'admuri(',
        ];

        $violations = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path('plugin'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace('\\', '/', $file->getPathname());
            if ($path === str_replace('\\', '/', __FILE__) || str_contains($path, '/tests/')) {
                continue;
            }
            $content = file_get_contents($path) ?: '';
            foreach ($forbidden as $legacy) {
                if (strpos($content, $legacy) !== false) {
                    $violations[] = [$legacy, $path];
                }
            }
        }

        $this->assertSame([], $violations, 'Legacy namespace references found: ' . json_encode($violations, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function path(string $relative): string
    {
        return $this->projectRoot . '/' . ltrim($relative, '/');
    }
}
