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

runInstallSmoke($projectRoot);
writeLine('install:ok');

runThinkListSmoke($projectRoot);
writeLine('think:list:ok');

writeLine('SMOKE_OK');

function runPublishSmoke(string $projectRoot): void
{
    $root = sys_get_temp_dir() . '/thinkadmin-smoke-' . bin2hex(random_bytes(6));

    try {
        mkdir($root . '/vendor/composer', 0777, true);
        mkdir($root . '/database/migrations', 0777, true);
        file_put_contents($root . '/database/migrations/20241011000001_install_wechat20241011.php', "<?php\n");
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

        assertTrue(
            !is_file($root . '/database/migrations/20241011000001_install_wechat20241011.php'),
            'stale unique migration should be removed'
        );

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

function runInstallSmoke(string $projectRoot): void
{
    $root = sys_get_temp_dir() . '/thinkadmin-install-' . bin2hex(random_bytes(6));

    try {
        mkdir($root, 0777, true);
        copyTree($projectRoot . '/app', $root . '/app');
        copyTree($projectRoot . '/config', $root . '/config');
        copyTree($projectRoot . '/plugin', $root . '/plugin');
        copyTree($projectRoot . '/vendor', $root . '/vendor');
        copyFile($projectRoot . '/think', $root . '/think');
        copyFile($projectRoot . '/composer.json', $root . '/composer.json');

        foreach (['database', 'public', 'runtime'] as $path) {
            mkdir($root . '/' . $path, 0777, true);
        }

        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($root . '/think') . ' xadmin:publish --migrate';
        exec($command . ' 2>&1', $output, $status);

        assertSameValue(0, $status, "install smoke failed:\n" . implode("\n", $output));

        foreach ([
            'database/migrations/20241010000001_install_system20241010.php',
            'database/migrations/20241010000002_install_storage20241010.php',
            'database/migrations/20241010000008_install_worker20241010.php',
            'public/static/system.js',
            'config/database.php',
        ] as $path) {
            assertTrue(is_file($root . '/' . $path), "missing installed artifact {$path}");
        }

        $db = new PDO('sqlite:' . $root . '/database/sqlite.db');
        foreach ([
            'system_auth',
            'system_auth_node',
            'system_menu',
            'system_user',
            'system_config',
            'system_data',
            'system_base',
            'system_oplog',
            'system_file',
            'system_queue',
        ] as $table) {
            $count = $db->query("select count(*) from sqlite_master where type='table' and name='{$table}'")->fetchColumn();
            assertTrue(!empty($count), "missing installed table {$table}");
        }

        $configRows = intval($db->query('select count(*) from system_config')->fetchColumn());
        assertTrue($configRows >= 8, 'system_config seed rows should be initialized');
    } finally {
        removeTree($root);
    }
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

function copyTree(string $source, string $target): void
{
    if (!is_dir($source)) {
        return;
    }

    mkdir($target, 0777, true);
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($items as $item) {
        $relative = substr($item->getPathname(), strlen($source) + 1);
        $pathname = $target . '/' . $relative;
        if (is_link($item->getPathname())) {
            $real = realpath($item->getPathname());
            if ($real === false) {
                continue;
            }
            if (is_dir($real)) {
                copyTree($real, $pathname);
            } else {
                copyFile($real, $pathname);
            }
            continue;
        }
        if ($item->isDir()) {
            is_dir($pathname) || mkdir($pathname, 0777, true);
        } else {
            if (is_dir($item->getPathname())) {
                copyTree($item->getPathname(), $pathname);
                continue;
            }
            is_dir(dirname($pathname)) || mkdir(dirname($pathname), 0777, true);
            copy($item->getPathname(), $pathname);
        }
    }
}

function copyFile(string $source, string $target): void
{
    if (is_dir($source)) {
        copyTree($source, $target);
        return;
    }
    is_dir(dirname($target)) || mkdir(dirname($target), 0777, true);
    copy($source, $target);
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
