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
use app\admin\controller\api\Plugs;
use app\admin\controller\api\Upload;
use app\admin\controller\Login;
use app\index\controller\Index;
use app\wechat\controller\api\Js;
use app\wechat\controller\api\Push;
use app\wechat\controller\api\Test;
use app\wechat\controller\api\View;
use plugin\account\controller\api\Auth;
use plugin\account\controller\api\auth\Center;
use plugin\account\controller\api\Wechat;
use plugin\account\controller\api\Wxapp;
use plugin\payment\controller\api\auth\Address;
use plugin\payment\controller\api\auth\Balance;
use plugin\payment\controller\api\auth\Integral;
use plugin\wechat\service\controller\api\Client;
use plugin\wemall\controller\api\auth\action\Collect;
use plugin\wemall\controller\api\auth\action\History;
use plugin\wemall\controller\api\auth\action\Search;
use plugin\wemall\controller\api\auth\Cart;
use plugin\wemall\controller\api\auth\Checkin;
use plugin\wemall\controller\api\auth\Coupon;
use plugin\wemall\controller\api\auth\Order;
use plugin\wemall\controller\api\auth\Rebate;
use plugin\wemall\controller\api\auth\Refund;
use plugin\wemall\controller\api\auth\Spread;
use plugin\wemall\controller\api\auth\Transfer;
use plugin\wemall\controller\api\Data;
use plugin\wemall\controller\api\Goods;
use plugin\wemall\controller\api\help\Feedback;
use plugin\wemall\controller\api\help\Problem;
use plugin\wemall\controller\api\help\Question;
use plugin\wuma\controller\api\Coder;
use think\admin\Controller;
use think\admin\Library;
use think\admin\service\AdminService;
use think\admin\service\NodeService;
use think\exception\HttpResponseException;
use think\Request;

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/vendor/topthink/framework/src/helper.php';

// Explicit exceptions keep new backend actions from silently becoming public.
// These endpoints serve public content or authenticate using tokens/signatures.
$exceptions = [
    Index::class => ['index'],
    app\admin\controller\Index::class => ['index'],
    Login::class => ['index', 'captcha', 'out'],
    Plugs::class => ['script'],
    Upload::class => ['index', 'image', 'state', 'done', 'file'],
    Js::class => ['index', 'sdk'],
    app\wechat\controller\api\Login::class => ['qrc', 'oauth', 'query'],
    Push::class => ['geoip', 'index'],
    Test::class => ['oauth', 'jssdk', 'scanOneNotify', 'jsapi', 'notify'],
    View::class => ['news', 'item', 'text', 'image', 'video', 'voice', 'music'],
    plugin\account\controller\api\Login::class => ['in', 'auto', 'pass', 'forget', 'register', 'send', 'image', 'verify'],
    Wechat::class => ['jssdk', 'oauth'],
    Wxapp::class => ['session', 'decode', 'phone', 'qrcode', 'getLiveList', 'getLiveInfo'],
    Center::class => ['get', 'set', 'forbid', 'bind', 'unbind'],
    Address::class => ['set', 'get', 'state', 'remove'],
    Balance::class => ['get'],
    Integral::class => ['get'],
    Client::class => ['yar', 'soap', 'jsonrpc'],
    plugin\wechat\service\controller\api\Push::class => ['notify', 'ticket', 'oauth', 'auth'],
    Data::class => ['get', 'spread', 'layout', 'slider', 'agreement'],
    Goods::class => ['get', 'cate', 'comments', 'region', 'express', 'hotkeys'],
    Cart::class => ['get', 'set'],
    plugin\wemall\controller\api\auth\Center::class => ['get', 'levels', 'discount'],
    Checkin::class => ['add', 'get', 'config'],
    Coupon::class => ['get', 'add', 'mine', 'query'],
    Order::class => ['get', 'add', 'express', 'perfect', 'channel', 'payment', 'cancel', 'remove', 'confirm', 'comment', 'total', 'track'],
    Rebate::class => ['get', 'prize', 'prizes'],
    Refund::class => ['get', 'add', 'express', 'cancel', 'confirm', 'reasons'],
    Spread::class => ['get', 'bind', 'poster'],
    Transfer::class => ['add', 'get', 'cancel', 'confirm', 'config'],
    Collect::class => ['set', 'get', 'del', 'clear'],
    History::class => ['set', 'get', 'del', 'clear'],
    Search::class => ['set', 'get'],
    Feedback::class => ['get', 'set'],
    Problem::class => ['get'],
    Question::class => ['get', 'set', 'reply', 'confirm'],
    Coder::class => ['batch', 'query'],
    plugin\wuma\controller\api\Login::class => ['login', 'logout'],
];

$errors = [];
$assert = static function (bool $condition, string $message) use (&$errors): void {
    if (!$condition) {
        $errors[] = $message;
    }
};
$root = dirname(__DIR__);
$files = [];
foreach (['app', 'plugin'] as $directory) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/' . $directory, FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php' && !preg_match('~/(vendor|tests)/~', $file->getPathname())) {
            $files[] = $file->getPathname();
        }
    }
}
sort($files);
$classes = [];
foreach ($files as $file) {
    $tokens = token_get_all(file_get_contents($file));
    foreach ($tokens as $token) {
        if (!is_array($token) || !in_array($token[0], [T_DOC_COMMENT, T_COMMENT], true)) {
            continue;
        }
        foreach (preg_split('/\R/', $token[1]) as $offset => $line) {
            if (!preg_match('~^\s*(?:/\*+|\*|//)\s*(@?\s*\w+)\s+(true|false)\b~i', $line, $match)) {
                continue;
            }
            $tag = strtolower(ltrim(trim($match[1]), '@'));
            foreach (['auth', 'login', 'menu'] as $expected) {
                if (levenshtein(trim($tag), $expected) <= 1) {
                    $assert(
                        strtolower($match[1]) === '@' . $expected && $token[0] === T_DOC_COMMENT,
                        substr($file, strlen($root) + 1) . ':' . ($token[2] + $offset) . ' invalid RBAC annotation: ' . trim($line)
                    );
                    break;
                }
            }
        }
    }
    if (strpos($file, '/controller/') === false) {
        continue;
    }
    $namespace = '';
    foreach ($tokens as $index => $token) {
        if (is_array($token) && $token[0] === T_NAMESPACE) {
            for ($i = $index + 1; isset($tokens[$i]) && $tokens[$i] !== ';'; ++$i) {
                $namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            }
            break;
        }
    }
    $class = new ReflectionClass(trim($namespace) . '\\' . basename($file, '.php'));
    $assert(realpath($class->getFileName()) === realpath($file), 'Controller autoload mismatch: ' . $file);
    $classes[] = $class;
}

// Build only an in-process container. Never load .env, boot services, or connect to a database.
$app = new think\App(__DIR__);
Library::$sapp = $app;
$app->config->set(['rbac_ignore' => [], 'rbac_login' => '/admin/login/index'], 'app');
$session = new class {
    public $user = [];

    public function get(string $key, $default = null)
    {
        return $this->user[$key] ?? $default;
    }
};
$app->instance('session', $session);
$app->instance('http', new class {
    public function getName(): string
    {
        return 'audit';
    }
});
$app->instance('db', new class {
    public function __call(string $name, array $arguments)
    {
        throw new RuntimeException('RBAC tests must not access a database');
    }
});

$parse = new ReflectionMethod(NodeService::class, '_parseClass');
$parse->setAccessible(true);
$ignored = get_class_methods(Controller::class);
$methods = [];
$actions = [];
foreach ($classes as $class) {
    [$space, $controller] = explode('\controller\\', $class->getName(), 2);
    $controller = str_replace('\\', '/', $controller);
    $module = strpos($space, 'app\\') === 0 ? substr($space, 4) : str_replace('\\', '-', $space);
    $args = [$module, $space, $controller, $ignored, &$methods];
    $parse->invokeArgs(null, $args);
    foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        $name = $method->getName();
        if (in_array($name, $ignored, true)) {
            continue;
        }
        $node = strtolower($module . '/' . NodeService::nameTolower($controller) . '/' . $name);
        if (!isset($methods[$node])) {
            throw new RuntimeException('Node parsing failed: ' . $node);
        }
        $actions[$node] = [$class, $method];
    }
}
sysvar('think.admin.methods', $methods);

foreach ($actions as $node => [$class, $method]) {
    $label = $class->getName() . '::' . $method->getName();
    $flags = $methods[$node];
    $isExempt = in_array($method->getName(), $exceptions[$class->getName()] ?? [], true);
    $assert($method->getName()[0] !== '_', $label . ' exposes an internal callback');
    $assert($isExempt || $flags['isauth'] || $flags['islogin'], $label . ' is missing an RBAC annotation');

    if ($flags['isauth'] || $flags['islogin']) {
        $session->user = [];
        $assert(!AdminService::check($node), $label . ' allows anonymous access');
        $session->user = ['user.id' => 1, 'user.username' => 'auditor'];
        $assert(AdminService::check($node) === !$flags['isauth'], $label . ' has incorrect role enforcement');
        $session->user['user.nodes'] = [$node];
        $assert(AdminService::check($node), $label . ' rejects an authorized user');
    }

    if ($isExempt && $class->isSubclassOf(Auth::class)) {
        $session->user = [];
        $app->instance('request', (new Request())->setController('Audit')->setAction($method->getName()));
        try {
            $class->newInstance($app);
            $assert(false, $label . ' accepts a missing member token');
        } catch (HttpResponseException $exception) {
            $assert($exception->getResponse()->getData()['code'] === 401, $label . ' does not reject a missing member token');
        }
    }
}

foreach ($exceptions as $class => $names) {
    foreach ($names as $name) {
        $method = new ReflectionMethod($class, $name);
        $assert($method->isPublic(), $class . '::' . $name . ' has a stale RBAC exception');
        $assert(!preg_match('/@(auth|login)\s*true/i', $method->getDocComment() ?: ''), $class . '::' . $name . ' no longer needs an RBAC exception');
    }
}

echo sprintf("Scanned %d PHP files, %d controllers, %d actions.\n", count($files), count($classes), count($actions));
foreach ($errors as $error) {
    echo 'FAIL: ', $error, PHP_EOL;
}
echo $errors ? 'RBAC checks failed.' : 'RBAC annotation, authorization, and member-token checks passed.', PHP_EOL;
exit($errors ? 1 : 0);
