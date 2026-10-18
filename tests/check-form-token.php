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
use app\admin\controller\Config;
use app\admin\controller\User;
use app\admin\tests\support\TestDatabase;
use think\admin\helper\TokenHelper;
use think\admin\Library;
use think\contract\SessionHandlerInterface;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\Request;
use think\service\ValidateService;
use think\Session;
use think\session\Store;

// 复用后台测试的内存 SQLite；不启动 HTTP 服务或连接业务数据库。
require dirname(__DIR__) . '/plugin/think-plugs-admin/tests/.bootstrap.php';
$app = Library::$sapp;
$temporary = sys_get_temp_dir() . '/thinkadmin-csrf-' . bin2hex(random_bytes(8));
mkdir($temporary, 0700);
register_shutdown_function(static function () use ($temporary): void {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($temporary, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($temporary);
});
$app->setRuntimePath($temporary . '/');
$app->config->set(['default' => 'csrf', 'stores' => [
    'csrf' => ['type' => 'File', 'path' => $temporary . '/cache/'],
]], 'cache');
$app->db->setCache($app->cache->store());
Db::connect()->setCache($app->cache->store());
$app->config->set(array_merge($app->config->get('view'), [
    'view_path' => dirname(__DIR__) . '/plugin/think-plugs-admin/src/view/',
    'cache_path' => $temporary . '/view/', 'tpl_cache' => false,
]), 'view');
$app->http->name('admin');
(new ValidateService($app))->boot();

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$checks = $failures = 0;
$check = static function (string $name, callable $test) use (&$checks, &$failures): void {
    ++$checks;
    try {
        $test();
    } catch (Throwable $exception) {
        ++$failures;
        echo 'FAIL: ', $name, ': ', $exception->getMessage(), PHP_EOL;
    }
};

// 使用真实 Session Store，只有持久化驱动替换为进程内的空存储。
$prepare = static function (array $method, string $token, array $payload = [], string $host = 'localhost') use ($app): array {
    $session = new class($app) extends Session {
        protected function createDriver(string $name)
        {
            return new Store('ssid', new class implements SessionHandlerInterface {
                public function read(string $sessionId): string
                {
                    return '';
                }

                public function write(string $sessionId, string $data): bool
                {
                    return true;
                }

                public function delete(string $sessionId): bool
                {
                    return true;
                }
            });
        }
    };
    $session->set('user', ['id' => 10000, 'username' => 'admin']);
    if ($token !== 'expired') {
        $session->set('_token_', 'fixture-token');
    }
    [$original, $formOverride, $headerOverride] = $method;
    $query = in_array($original, ['GET', 'HEAD', 'OPTIONS'], true) ? $payload : [];
    $post = $query ? [] : $payload;
    $headers = ['content-type' => 'application/x-www-form-urlencoded'];
    if ($token === 'valid' || $token === 'expired' || $token === 'wrong') {
        $post['_token_'] = $token === 'wrong' ? 'wrong-token' : 'fixture-token';
    } elseif ($token === 'form-header') {
        $headers['User-Form-Token'] = 'fixture-token';
    } elseif ($token === 'csrf-header') {
        $headers['X-CSRF-TOKEN'] = 'fixture-token';
    }
    if ($formOverride !== '') {
        $post['_method'] = $formOverride;
    }
    $server = ['REQUEST_METHOD' => $original, 'HTTP_HOST' => $host, 'SERVER_NAME' => $host, 'REQUEST_TIME_FLOAT' => microtime(true)];
    if ($headerOverride !== '') {
        $server['HTTP_X_HTTP_METHOD_OVERRIDE'] = $headerOverride;
        $headers['X-HTTP-Method-Override'] = $headerOverride;
    }
    $request = (new Request())->withHeader($headers)->withServer($server)
        ->withInput(http_build_query($post))->withPost($post)->withGet($query)
        ->withSession($session)->setController('User')->setAction('pass');
    $app->instance('request', $request);
    $app->instance('session', $session);
    return [$request, $session];
};

$unsafe = [
    'POST' => ['POST', '', ''],
    'PUT' => ['PUT', '', ''],
    'PATCH' => ['PATCH', '', ''],
    'DELETE' => ['DELETE', '', ''],
    'TRACE' => ['TRACE', '', ''],
];
foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
    $unsafe['form ' . $method] = ['POST', strtolower($method), ''];
    $unsafe['header ' . $method] = ['POST', '', $method];
}
$tokens = ['missing', 'wrong', 'expired', 'valid', 'form-header', 'csrf-header'];
foreach ($unsafe as $label => $method) {
    foreach ($tokens as $token) {
        $check('TokenHelper ' . $label . ' / ' . $token, static function () use ($app, $prepare, $assert, $method, $token): void {
            [, $session] = $prepare($method, $token);
            $controller = new User($app);
            $helper = new TokenHelper($app, $controller);
            $expected = in_array($token, ['valid', 'form-header', 'csrf-header'], true);
            $assert($helper->init(true) === $expected, '令牌校验结果不符');
            $assert($controller->csrf_state, '未启用表单令牌输出');
            $assert(!$session->has('_token_'), '写请求校验后仍保留令牌');
            $assert($helper->init(true) === false, '已使用的令牌可重复提交');
        });
    }
}
foreach (['GET', 'HEAD', 'OPTIONS'] as $method) {
    $check('TokenHelper safe ' . $method, static function () use ($app, $prepare, $assert, $method): void {
        [, $session] = $prepare([$method, '', ''], 'missing');
        $helper = new TokenHelper($app, new User($app));
        $assert($helper->init(true) === true, '安全方法被错误拒绝');
        $assert($session->get('_token_') === 'fixture-token', '安全方法消耗了令牌');
        $session->delete('_token_');
        $assert($helper->init(true) === true, '安全方法要求会话令牌');
    });
}
$check('TokenHelper custom error', static function () use ($app, $prepare, $assert): void {
    $prepare(['POST', 'PUT', ''], 'missing');
    $controller = new User($app);
    $controller->csrf_message = '自定义令牌错误';
    try {
        (new TokenHelper($app, $controller))->init();
        throw new RuntimeException('无令牌请求未抛出错误响应');
    } catch (HttpResponseException $exception) {
        $data = $exception->getResponse()->getData();
        $assert($data['code'] === 0 && $data['info'] === '自定义令牌错误', '自定义错误返回不兼容');
    }
});

$events = [];
$app->event->listen('PluginAdminChangePassword', static function ($event) use (&$events): void {
    $events[] = $event;
});
$reset = static function () use ($app, &$events): void {
    TestDatabase::reset();
    Db::table('system_oplog')->delete(true);
    Db::table('system_config')->delete(true);
    Db::table('system_user')->insertAll([
        ['id' => 10000, 'username' => 'admin', 'password' => md5('original-fixture-password')],
        ['id' => 10001, 'username' => 'fixture-user', 'password' => md5('original-fixture-password')],
    ]);
    Db::table('system_config')->insertAll([
        ['type' => 'storage', 'name' => 'allow_exts', 'value' => 'gif,jpg,png'],
        ['type' => 'storage', 'name' => 'type', 'value' => 'local'],
        ['type' => 'storage', 'name' => 'local_http_protocol', 'value' => 'follow'],
    ]);
    sysvar('think.admin.config', []);
    $app->cache->clear();
    $events = [];
};
$snapshot = static function () use (&$events): array {
    return [
        'passwords' => Db::table('system_user')->order('id')->column('password', 'id'),
        'config' => Db::table('system_config')->order('id')->select()->toArray(),
        'logs' => Db::table('system_oplog')->count(), 'events' => $events,
    ];
};
$invoke = static function (string $class, string $action) use ($app) {
    try {
        (new $class($app))->{$action}();
        throw new RuntimeException('控制器未返回响应');
    } catch (HttpResponseException $exception) {
        return $exception->getResponse();
    }
};

$methods = $unsafe + ['GET' => ['GET', '', ''], 'HEAD' => ['HEAD', '', ''], 'OPTIONS' => ['OPTIONS', '', ''],
    'header HEAD' => ['POST', '', 'HEAD'], 'header OPTIONS' => ['POST', '', 'OPTIONS']];
$targets = [
    'user/super-admin' => [User::class, 'pass', ['id' => 10000, 'password' => 'new-fixture-password', 'repassword' => 'new-fixture-password']],
    'user/other-user' => [User::class, 'pass', ['id' => 10001, 'password' => 'new-fixture-password', 'repassword' => 'new-fixture-password']],
    'config/storage' => [Config::class, 'storage', ['storage' => ['allow_exts' => 'png,jpg']]],
];
foreach ($targets as $target => [$class, $action, $payload]) {
    foreach ($methods as $label => $method) {
        $tokenCases = $label === 'GET' ? ['missing'] : ($label === 'POST' ? $tokens : ['missing', 'valid']);
        foreach ($tokenCases as $token) {
            $check($target . ' / ' . $label . ' / ' . $token, static function () use ($prepare, $reset, $snapshot, $invoke, $assert, $class, $action, $payload, $method, $token): void {
                $reset();
                [$request, $session] = $prepare($method, $token, $payload);
                $request->setController($class === User::class ? 'User' : 'Config')->setAction($action);
                $before = $snapshot();
                if (!in_array($request->method(), ['GET', 'POST'], true)) {
                    try {
                        $invoke($class, $action);
                        throw new RuntimeException('未拒绝不支持的方法；数据变化=' . ($snapshot() === $before ? '否' : '是'));
                    } catch (HttpException $exception) {
                        $assert($exception->getStatusCode() === 405, '不支持的方法没有返回 HTTP 405');
                        $assert($snapshot() === $before, '被拒绝的请求产生了写入或事件');
                    }
                    return;
                }
                $response = $invoke($class, $action);
                if ($request->isGet()) {
                    $html = $response->getContent();
                    $assert($response->getCode() === 200 && strpos($html, '<form') !== false, 'GET 未正常展示表单');
                    $assert(strpos($html, "name='_token_' value='" . $session->get('_token_') . "'") !== false, 'GET 表单未包含会话令牌');
                    $assert($snapshot() === $before, 'GET 产生了业务数据写入');
                } elseif (in_array($token, ['valid', 'form-header', 'csrf-header'], true)) {
                    $data = $response->getData();
                    $assert($data['code'] === 1, '合法 POST 未成功');
                    $after = $snapshot();
                    if ($class === User::class) {
                        $expected = $before['passwords'];
                        $expected[$payload['id']] = md5($payload['password']);
                        $assert($after['passwords'] === $expected, '未只修改目标账号密码');
                        $assert($after['events'] === [['uuid' => $payload['id'], 'pass' => $payload['password']]], '改密事件不兼容');
                    } else {
                        $assert(Db::table('system_config')->where(['type' => 'storage', 'name' => 'allow_exts'])->value('value') === 'png,jpg', '存储配置未保存');
                    }
                    $assert($after['logs'] === 1, '合法提交未记录操作日志');
                    $assert(!$session->has('_token_'), '合法提交后未消耗令牌');
                } else {
                    $data = $response->getData();
                    $assert($data['code'] === 0 && $data['info'] === '表单令牌验证失败！', '无效令牌错误响应不兼容');
                    $assert($snapshot() === $before, '无效令牌请求产生了写入或事件');
                }
            });
        }
    }
}
foreach (['POST' => ['POST', '', ''], 'form PUT' => ['POST', 'PUT', ''], 'HEAD' => ['HEAD', '', '']] as $label => $method) {
    $check('demo storage / ' . $label, static function () use ($prepare, $reset, $snapshot, $invoke, $assert, $method): void {
        $reset();
        [$request] = $prepare($method, 'valid', ['storage' => ['allow_exts' => 'png']], 'v6.thinkadmin.top');
        $request->setController('Config')->setAction('storage');
        $before = $snapshot();
        $data = $invoke(Config::class, 'storage')->getData();
        $assert($data['code'] === 0 && $data['info'] === '演示环境禁止修改系统配置！', '演示环境拦截优先级改变');
        $assert($snapshot() === $before, '演示环境请求产生了写入');
    });
}
echo 'Form token checks: ', $checks - $failures, '/', $checks, ' passed', PHP_EOL;
exit($failures ? 1 : 0);
