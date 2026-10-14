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
require __DIR__ . '/acceptance.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use think\admin\extend\ToolsExtend;

acceptanceCheck(getenv('THINKADMIN_TEST_DB') === ':memory:', '必须显式启用隔离验收');
$root = dirname(__DIR__);
$directory = sys_get_temp_dir() . '/thinkadmin-console-' . bin2hex(random_bytes(8));
$failures = [];
try {
    acceptanceWrite($directory . '/vendor/autoload.php', '<?php require ' . var_export($root . '/vendor/autoload.php', true) . ';');
    acceptanceWrite($directory . '/runtime/.env', "[RUNTIME]\nmode=product\n");
    acceptanceWrite($directory . '/config/cache.php', "<?php return ['default'=>'file','stores'=>['file'=>['type'=>'File']]];");
    acceptanceWrite(
        $directory . '/config/console.php',
        <<<'PHP'
<?php
if (getenv('ACCEPTANCE_INIT_FAILURE') === 'config') {
    fwrite(STDERR, "INIT_FAILURE\n");
    throw new RuntimeException('INIT_FAILURE');
}
return ['commands' => ['acceptance:probe' => AcceptanceConsoleProbe::class]];
PHP
    );
    acceptanceWrite(
        $directory . '/app/common.php',
        <<<'PHP'
<?php
class AcceptanceBootFailure extends \think\Service
{
    public function boot()
    {
        if (getenv('ACCEPTANCE_INIT_FAILURE') === 'boot') {
            throw new RuntimeException('BOOT_FAILURE');
        }
    }
}
class AcceptanceConsoleProbe extends \think\console\Command
{
    protected function configure()
    {
        $this->setName('acceptance:probe')->addArgument('scenario', \think\console\input\Argument::OPTIONAL, '', 'success');
    }
    protected function execute(\think\console\Input $input, \think\console\Output $output)
    {
        switch ($input->getArgument('scenario')) {
            case 'exception': throw new RuntimeException('COMMAND_EXCEPTION');
            case 'error': throw new Error('COMMAND_ERROR');
            case 'type-error': throw new TypeError('COMMAND_TYPE_ERROR');
            case 'status': return 7;
            case 'nested':
                $this->app->console->find('list')->run(new \think\console\Input(['list', '--raw']), new \think\console\Output('buffer'));
                throw new RuntimeException('NESTED_EXCEPTION');
        }
        $output->writeln('COMMAND_OK');
        return 0;
    }
}
PHP
    );
    // 仅注入隔离应用根目录，其他内容使用仓库和静态插件的实际入口。
    foreach (['think', 'plugin/think-plugs-static/stc/think', 'framework'] as $source) {
        if ($source === 'framework') {
            // 原生框架对照不注册 Library，用于区分本仓逻辑和外部依赖缺陷。
            $entry = '<?php require __DIR__ . "/vendor/autoload.php"; (new \think\App(__DIR__))->console->run();';
            $services = ['AcceptanceBootFailure'];
        } else {
            $entry = str_replace('RuntimeService::doConsoleInit()', 'RuntimeService::doConsoleInit(new \think\App(__DIR__))', file_get_contents($root . '/' . $source), $count);
            acceptanceCheck($count === 1, '入口结构变化，需要重新确认隔离根目录');
            $services = ['think\admin\Library', 'AcceptanceBootFailure'];
        }
        acceptanceWrite($directory . '/vendor/services.php', '<?php return ' . var_export($services, true) . ';');
        acceptanceWrite($directory . '/think', $entry);
        foreach (['success' => 0, 'status' => 7, 'exception' => 1, 'nested' => 1, 'error' => 1, 'type-error' => 1, 'config' => 1, 'boot' => 1] as $scenario => $expected) {
            putenv('ACCEPTANCE_INIT_FAILURE=' . $scenario);
            [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'think', 'acceptance:probe', $scenario]);
            $passed = $expected === 0 ? $status === 0 && strpos($output, 'COMMAND_OK') !== false : $status !== 0;
            if ($scenario === 'status') {
                $passed = $status === 7;
            }
            $markers = ['exception' => 'COMMAND_EXCEPTION', 'nested' => 'NESTED_EXCEPTION', 'error' => 'COMMAND_ERROR', 'type-error' => 'COMMAND_TYPE_ERROR', 'config' => 'INIT_FAILURE', 'boot' => 'BOOT_FAILURE'];
            if (isset($markers[$scenario])) {
                $passed = $passed && strpos($output, $markers[$scenario]) !== false;
            }
            echo ($passed ? 'PASS' : 'FAIL') . ": {$source} / {$scenario} / exit={$status}\n";
            if (!$passed) {
                $failures[] = "{$source}/{$scenario}: exit={$status}\n{$output}";
            }
        }
    }
    putenv('ACCEPTANCE_INIT_FAILURE');
    acceptanceWrite($directory . '/vendor/services.php', '<?php return [\think\admin\Library::class];');
    acceptanceWrite(
        $directory . '/embedded.php',
        <<<'PHP'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = \think\admin\service\RuntimeService::init(new \think\App(__DIR__));
$app->console->setAutoExit(false);
if (getenv('ACCEPTANCE_PROPAGATE')) {
    $app->console->setCatchExceptions(false);
}
try {
    $status = \think\admin\service\RuntimeService::doConsoleInit($app);
    echo "RETURNED:{$status}\n";
} catch (\Throwable $error) {
    echo 'FORWARDED:' . get_class($error) . ':' . $error->getMessage() . "\n";
}
echo "CALLER_CONTINUED\n";
PHP
    );
    foreach (['status' => 'RETURNED:7', 'exception' => 'RETURNED:1'] as $scenario => $marker) {
        [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'embedded.php', 'acceptance:probe', $scenario]);
        acceptanceCheck($status === 0 && strpos($output, $marker) !== false && strpos($output, 'CALLER_CONTINUED') !== false, '关闭自动退出后的返回约定失败：' . $output);
    }
    putenv('ACCEPTANCE_PROPAGATE=1');
    foreach (['exception' => 'RuntimeException:COMMAND_EXCEPTION', 'error' => 'Error:COMMAND_ERROR', 'type-error' => 'TypeError:COMMAND_TYPE_ERROR'] as $scenario => $marker) {
        [$status, $output] = acceptanceRun($directory, [PHP_BINARY, 'embedded.php', 'acceptance:probe', $scenario]);
        acceptanceCheck($status === 0 && strpos($output, 'FORWARDED:' . $marker) !== false && strpos($output, 'CALLER_CONTINUED') !== false, '关闭异常捕获后的传递约定失败：' . $output);
    }
    echo "PASS: 调用方关闭自动退出及关闭异常捕获\n";
} finally {
    putenv('ACCEPTANCE_INIT_FAILURE');
    putenv('ACCEPTANCE_PROPAGATE');
    ToolsExtend::remove($directory);
}
if ($failures) {
    fwrite(STDERR, implode("\n", $failures));
    exit(1);
}
