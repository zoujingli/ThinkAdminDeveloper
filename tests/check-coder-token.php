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
use plugin\wuma\controller\api\Coder;
use think\admin\Library;
use think\App;
use think\Db;
use think\exception\HttpResponseException;
use think\Model;
use think\Request;
use think\service\ValidateService;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/topthink/framework/src/helper.php';

// No application bootstrap: fixtures exist only in this process's SQLite connection.
$app = new App(__DIR__);
Library::$sapp = $app;
$app->instance('http', new class {
    public function getName(): string
    {
        return 'plugin-wuma';
    }
});
$cache = new class {
    public $token;

    public function get(string $key, $default = null)
    {
        return $key === 'create_auth_test-batch' ? $this->token : $default;
    }
};
$app->instance('cache', $cache);
$app->config->set(['default' => 'sqlite', 'connections' => ['sqlite' => [
    'type' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'debug' => true,
]]], 'database');
$db = new Db();
$db->setConfig($app->config);
$db->setEvent($app->event);
$app->instance('db', $db);
Model::setDb($db);
(new ValidateService($app))->boot();
$db->execute('CREATE TABLE plugin_wuma_code_rule (id INTEGER PRIMARY KEY, batch TEXT, remark TEXT)');
$db->execute('CREATE TABLE plugin_wuma_code_rule_range (id INTEGER PRIMARY KEY, batch TEXT, code_type TEXT, range_start INTEGER, range_after INTEGER)');
$db->table('plugin_wuma_code_rule')->insert(['id' => 1, 'batch' => 'test-batch', 'remark' => 'fixture']);
$db->table('plugin_wuma_code_rule_range')->insert(['id' => 1, 'batch' => 'test-batch', 'code_type' => 'min', 'range_start' => 1, 'range_after' => 100]);

$cases = [
    'valid' => ['expected-token', 'expected-token', 1],
    'wrong' => ['expected-token', 'wrong-token', 0],
    'missing' => ['expected-token', '', 0],
    'expired' => [null, 'expected-token', 0],
    'non-string' => ['expected-token', ['expected-token'], 0],
];
$failures = 0;
foreach (['batch', 'query'] as $action) {
    foreach ($cases as $label => [$cached, $submitted, $expected]) {
        $cache->token = $cached;
        $request = (new Request())->withPost([
            'batch' => 'test-batch', 'code' => '50', 'type' => 'min', 'token' => $submitted,
        ])->withServer(['REQUEST_METHOD' => 'POST'])->setController('api.Coder')->setAction($action);
        $app->instance('request', $request);
        try {
            (new Coder($app))->{$action}();
            $result = false;
        } catch (HttpResponseException $exception) {
            $result = $exception->getResponse()->getData()['code'] === $expected;
        } catch (Throwable $exception) {
            $result = false;
            echo 'ERROR: ', $action, ' ', $label, ' ', get_class($exception), ': ', $exception->getMessage(), PHP_EOL;
        }
        $failures += $result ? 0 : 1;
        echo $result ? 'PASS: ' : 'FAIL: ', $action, ' ', $label, ' token', PHP_EOL;
    }
}
exit($failures ? 1 : 0);
