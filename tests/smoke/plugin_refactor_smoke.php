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
use plugin\helper\command\Publish;
use think\admin\service\RuntimeService;
use think\App;
use think\console\Input;
use think\console\Output;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/vendor/topthink/framework/src/helper.php';

$projectRoot = dirname(__DIR__, 2);

assertHelperOwner('sysconf', $projectRoot . '/plugin/think-plugs-system/src/common.php');
assertHelperOwner('sysdata', $projectRoot . '/plugin/think-plugs-system/src/common.php');
assertHelperOwner('sysoplog', $projectRoot . '/plugin/think-plugs-system/src/common.php');
assertHelperOwner('sysqueue', $projectRoot . '/plugin/think-plugs-worker/src/common.php');
writeLine('helpers:ok');

runPublishSmoke($projectRoot);
writeLine('publish:ok');

runThinkListSmoke($projectRoot);
writeLine('think:list:ok');

writeLine('SMOKE_OK');

function runPublishSmoke(string $projectRoot): void
{
    $root = sys_get_temp_dir() . '/thinkadmin-smoke-' . bin2hex(random_bytes(6));

    try {
        mkdir($root . '/vendor/composer', 0777, true);
        createPluginPackage($root, 'demo', 'vendor/demo-plugin', 'plugin\demo\Service');
        createPluginPackage(
            $root,
            'system',
            'vendor/system-plugin',
            'plugin\system\Service',
            '20241010000001_install_system20241010.php'
        );
        createPluginPackage(
            $root,
            'storage',
            'vendor/storage-plugin',
            'plugin\storage\Service',
            '20241010000002_install_storage20241010.php'
        );
        createPluginPackage(
            $root,
            'worker',
            'vendor/worker-plugin',
            'plugin\worker\Service',
            '20241010000008_install_worker20241010.php'
        );

        $app = new App($root);
        RuntimeService::init($app);
        $app->config->set([
            'default' => 'file',
            'stores' => [
                'file' => ['type' => 'File', 'path' => $root . '/runtime/cache'],
            ],
        ], 'cache');

        $command = new Publish();
        $command->setApp($app);

        $code = $command->run(new Input([]), new Output('buffer'));
        assertSameValue(0, $code, 'publish command should exit with code 0');

        $services = require $root . '/vendor/services.php';
        $versions = require $root . '/vendor/versions.php';
        $manifest = json_decode((string)file_get_contents($root . '/database/migrations/.xadmin-published.json'), true, 512, JSON_THROW_ON_ERROR);

        foreach (['plugin\demo\Service', 'plugin\system\Service', 'plugin\storage\Service', 'plugin\worker\Service'] as $service) {
            assertTrue(in_array($service, $services, true), "missing published service {$service}");
        }

        assertSameValue('System', $versions['vendor/system-plugin']['name'] ?? null, 'system plugin name should be published');
        assertSameValue('plugin', $versions['vendor/storage-plugin']['type'] ?? null, 'storage plugin type should be published');

        foreach ([
            '20241010000001_install_system20241010.php',
            '20241010000002_install_storage20241010.php',
            '20241010000008_install_worker20241010.php',
        ] as $migration) {
            assertTrue(is_file($root . '/database/migrations/' . $migration), "missing migration {$migration}");
        }

        assertSameValue(
            'plugin/system/stc/database/20241010000001_install_system20241010.php',
            $manifest['20241010000001_install_system20241010.php']['source'] ?? null,
            'system migration manifest should point to plugin source'
        );
    } finally {
        removeTree($root);
    }
}

function runThinkListSmoke(string $projectRoot): void
{
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($projectRoot . '/think') . ' list';
    exec($command . ' 2>&1', $output, $status);

    assertSameValue(0, $status, "think list failed:\n" . implode("\n", $output));
}

function assertHelperOwner(string $name, string $expectedFile): void
{
    assertTrue(function_exists($name), "{$name} should be defined");

    $reflection = new ReflectionFunction($name);

    assertSameValue(
        realpath($expectedFile),
        realpath((string)$reflection->getFileName()),
        "{$name} should be loaded from {$expectedFile}"
    );
}

function createPluginPackage(string $root, string $code, string $name, string $service, ?string $migration = null): void
{
    $path = "{$root}/plugin/{$code}";
    mkdir($path, 0777, true);
    file_put_contents($path . '/composer.json', json_encode([
        'name' => $name,
        'type' => 'think-admin-plugin',
        'version' => '1.0.0',
        'description' => ucfirst($code),
        'extra' => [
            'think' => ['services' => [$service]],
            'xadmin' => ['service' => ['code' => $code, 'name' => ucfirst($code)]],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    if ($migration) {
        mkdir($path . '/stc/database', 0777, true);
        file_put_contents($path . '/stc/database/' . $migration, "<?php\n");
    }
}

function removeTree(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($path);
}

function assertTrue(bool $condition, string $message): void
{
    if ($condition) {
        return;
    }

    throw new RuntimeException($message);
}

function assertSameValue($expected, $actual, string $message): void
{
    if ($expected === $actual) {
        return;
    }

    throw new RuntimeException(
        $message . ' | expected: ' . var_export($expected, true) . ' actual: ' . var_export($actual, true)
    );
}

function writeLine(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}
