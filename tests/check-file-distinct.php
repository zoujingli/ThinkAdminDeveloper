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
use app\admin\controller\File;
use think\admin\model\SystemFile;
use think\admin\service\RuntimeService;
use think\App;
use think\exception\HttpResponseException;
use think\Request;
use think\service\ModelService;

require __DIR__ . '/acceptance.php';
require dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/vendor/topthink/framework/src/helper.php';

/**
 * 通过真实控制器清理文件，核验原有 HTTP 响应契约。
 * @param App $app 隔离应用
 * @throws RuntimeException 未返回原有成功响应时失败
 */
function checkFileDistinct(App $app): void
{
    try {
        (new File($app))->distinct();
    } catch (HttpResponseException $exception) {
        $response = $exception->getResponse();
        $data = json_decode($response->getContent(), true);
        acceptanceCheck($response->getCode() === 200 && $data === ['code' => 1, 'info' => '清理重复文件成功！', 'data' => []], '文件去重响应发生变化：' . $response->getContent());
        return;
    }
    throw new RuntimeException('文件去重没有返回 HTTP 响应');
}

acceptanceCheck(getenv('THINKADMIN_ACCEPTANCE_CONTAINER') === '1' && getenv('THINKADMIN_TEST_DB') === ':memory:', '文件去重验收只能通过隔离 Docker 环境运行');
$driver = $argv[1] ?? '';
acceptanceCheck(in_array($driver, ['sqlite', 'mysql'], true), '用法：check-file-distinct.php sqlite|mysql');
if (!isset($argv[2])) {
    // 独立进程避免模型连接缓存使第二种表前缀误用第一种配置。
    $failed = false;
    foreach (['', 'tenant_'] as $prefix) {
        [$status, $output] = acceptanceRun(dirname(__DIR__), [PHP_BINARY, __FILE__, $driver, $prefix]);
        echo $output;
        $failed = $failed || $status !== 0;
    }
    exit($failed ? 1 : 0);
}
$prefix = $argv[2];
acceptanceCheck(in_array($prefix, ['', 'tenant_'], true), '不支持的验收表前缀');
$mysqlPort = (int)getenv('PHINX_TEST_MYSQL_PORT');
acceptanceCheck($driver !== 'mysql' || $mysqlPort > 0, 'MySQL 必须指定隔离实例 PHINX_TEST_MYSQL_PORT');
$id = bin2hex(random_bytes(8));
$directory = sys_get_temp_dir() . '/thinkadmin-file-distinct-' . $id;
$database = 'thinkadmin_file_distinct_' . $id;
$server = null;
$status = 0;
try {
    acceptanceWrite($directory . '/runtime/.env', "[RUNTIME]\nmode=product\n");
    if ($driver === 'mysql') {
        $server = new PDO('mysql:host=127.0.0.1;port=' . $mysqlPort . ';dbname=phinx_fixture', 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4");
    }
    $app = RuntimeService::init(new App($directory));
    foreach (glob(dirname(__DIR__) . '/plugin/think-plugs-static/stc/config/*.php') as $config) {
        $app->config->load($config, pathinfo($config, PATHINFO_FILENAME));
    }
    $app->config->set(['default' => 'file', 'stores' => ['file' => ['type' => 'file', 'path' => $directory . '/cache']]], 'cache');
    $app->config->set(['default' => $driver, 'connections' => [$driver => [
        'type' => $driver, 'hostname' => '127.0.0.1', 'hostport' => $mysqlPort,
        'database' => $driver === 'sqlite' ? ':memory:' : $database,
        'username' => 'root', 'password' => '', 'charset' => 'utf8mb4',
        'prefix' => $prefix, 'fields_strict' => true,
    ]]], 'database');
    (new ModelService($app))->boot();
    $app->config->set(['type' => 'file', 'name' => 'ssid', 'path' => $directory . '/session'], 'session');
    $app->session->set('user.id', 11);
    $app->instance('request', (new Request())->withServer(['REQUEST_METHOD' => 'POST', 'HTTP_HOST' => 'localhost'])->setAction('distinct'));
    $app->db->execute("CREATE TABLE {$prefix}system_file (id INTEGER PRIMARY KEY, uuid INTEGER, issafe INTEGER, type VARCHAR(50), xkey VARCHAR(255), status INTEGER)");
    $fields = ['id', 'uuid', 'issafe', 'type', 'xkey', 'status'];
    $rows = [
        [1, 11, 0, 'local', 'same', 2], [2, 11, 0, 'local', 'same', 1],
        [3, 22, 0, 'local', 'same', 2], [4, 11, 1, 'local', 'same', 2],
        [5, 11, 0, 'qiniu', 'same', 2], [6, 11, 0, 'local', 'other', 2],
        [7, 11, 0, 'local', 'other', 2], [8, 22, 0, 'local', 'same', 2],
        [9, 11, 1, 'local', 'same', 2], [10, 11, 0, 'local', 'unique', 2],
    ];
    foreach ($rows as $row) {
        $app->db->name('system_file')->insert(array_combine($fields, $row));
    }
    foreach (['首次清理', '重复清理'] as $scenario) {
        checkFileDistinct($app);
        $ids = array_map('intval', SystemFile::mk()->order('id')->column('id'));
        acceptanceCheck($ids === [2, 3, 4, 5, 7, 8, 9, 10], $scenario . '保留记录错误：' . json_encode($ids));
        foreach (SystemFile::mk()->field($fields)->order('id')->select()->toArray() as $record) {
            // 除应删除记录外，原有字段值也必须保持不变。
            acceptanceCheck(array_values($record) == $rows[(int)$record['id'] - 1], $scenario . '修改了保留记录：' . json_encode($record));
        }
    }
    $app->session->set('user.id', 33);
    checkFileDistinct($app);
    acceptanceCheck(SystemFile::mk()->count() === 8, '当前管理员没有文件时删除了其他记录');
    $app->db->name('system_file')->delete(true);
    checkFileDistinct($app);
    acceptanceCheck(SystemFile::mk()->count() === 0, '空表清理结果异常');
    echo 'PASS: ' . $driver . ' / prefix=' . var_export($prefix, true) . " / 分组保留最大 ID、管理员隔离、安全文件、重复执行、空集合及响应契约\n";
} catch (Throwable $error) {
    $status = 1;
    fwrite(STDERR, 'FAIL: ' . $driver . ' / prefix=' . var_export($prefix, true) . ' / ' . $error->getMessage() . "\n");
} finally {
    if ($server !== null) {
        $server->exec("DROP DATABASE IF EXISTS `{$database}`");
    }
    acceptanceRemove($directory);
}
exit($status);
