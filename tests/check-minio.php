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
use app\admin\controller\api\Upload;
use app\admin\controller\Config;
use think\admin\extend\HttpExtend;
use think\admin\Library;
use think\admin\Storage;
use think\admin\storage\LocalStorage;
use think\admin\storage\MinioStorage;
use think\exception\HttpResponseException;
use think\Request;

require __DIR__ . '/acceptance.php';
acceptanceCheck(getenv('THINKADMIN_ACCEPTANCE_CONTAINER') === '1', 'MinIO 验收只能在隔离 Docker 中运行');
require dirname(__DIR__) . '/plugin/think-plugs-admin/tests/.bootstrap.php';

foreach (['minio_http_domain' => getenv('THINKADMIN_MINIO_ENDPOINT') ?: '127.0.0.1:9000', 'minio_http_protocol' => 'http', 'minio_region' => 'acceptance-1',
    'minio_bucket' => 'thinkadmin-test', 'minio_access_key' => 'thinkadmin', 'minio_secret_key' => 'acceptance-secret'] as $name => $value) {
    sysconf('storage.' . $name, $value);
}
acceptanceCheck(isset(Storage::types()['minio']), '存储注册缺少 MinIO');
$storage = Storage::instance('minio');
acceptanceCheck($storage instanceof MinioStorage, '没有通过存储公共入口取得 MinIO');
$key = 'acceptance/' . bin2hex(random_bytes(8)) . '.txt';
$postKey = str_replace('.txt', '-post.gif', $key);
$safeKey = str_replace('.txt', '-safe.gif', $key);
$public = null;
$temp = tempnam(sys_get_temp_dir(), 'minio-upload-');
try {
    $bytes = "MinIO\0\xff\n中文";
    acceptanceCheck($storage->set($key, $bytes) !== [], '签名 PUT 写入失败');
    acceptanceCheck($storage->has($key), '签名 HEAD 不存在');
    acceptanceCheck($storage->get($key) === $bytes, '签名 GET 丢失二进制内容');
    acceptanceCheck($storage->get($key . '?attname=demo') === $bytes, '显示后缀不兼容');
    acceptanceCheck($storage->get($key . '!preview') === $bytes, '处理后缀不兼容');
    $info = $storage->info($key);
    acceptanceCheck($info['key'] === $key && $info['url'] === $storage->url($key) && $info['file'] === $storage->path($key), '文件信息契约变化');
    $unauthorized = HttpExtend::get($storage->url($key));
    acceptanceCheck(strpos($unauthorized, 'AccessDenied') !== false, '私有桶被意外公开');
    acceptanceCheck($storage->set($key, '') !== [] && $storage->has($key) && $storage->get($key) === '', '空文件写入或读取失败');
    acceptanceCheck($storage->set($key, '0') !== [] && $storage->get($key) === '0', '文件内容 0 被丢失');
    acceptanceCheck($storage->del($key), '签名 DELETE 失败');
    acceptanceCheck(!$storage->has($key), '删除后仍存在');
    acceptanceCheck($storage->info($key) === [] && $storage->get($key) === '', '不存在对象返回契约错误');
    acceptanceCheck($storage->del($key), '重复删除不是 S3 幂等成功');
    echo "PASS: MinIO 私有桶、非默认区域、带端口端点、签名 CRUD、二进制/空文件/0、后缀及重复删除\n";

    // 直接使用真实控制器签发的字段，不手工重签 policy。
    $app = Library::$sapp;
    $app->session->set('user.username', 'admin');
    $app->session->set('user.id', 1);
    $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
    file_put_contents($temp, $gif);
    $post = ['key' => $postKey, 'safe' => 0, 'uptype' => 'minio', 'name' => '验收.gif',
        'hash' => md5($gif), 'xext' => 'gif', 'size' => strlen($gif), 'mime' => 'image/gif'];
    $invoke = static function (string $action, array $data, array $files = []) use ($app): array {
        $app->instance('request', (new Request())->withPost($data)->withFiles($files)->withServer([
            'REQUEST_METHOD' => 'POST', 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost',
        ])->setAction($action));
        try {
            (new Upload($app))->{$action}();
        } catch (HttpResponseException $response) {
            return json_decode($response->getResponse()->getContent(), true);
        }
        throw new RuntimeException('上传控制器没有响应');
    };
    $state = $invoke('state', $post);
    acceptanceCheck($state['code'] === 404 && $state['data']['uptype'] === 'minio', '后台未签发 MinIO 上传授权：' . json_encode($state));
    $fields = $state['data']['fields'];
    acceptanceCheck(strpos(json_encode($state), 'acceptance-secret') === false, '上传响应泄漏 SecretKey');
    $submit = static function (array $fields) use ($state, $temp): array {
        // 对应 upload.js：授权字段原样提交，仅追加 file，不带 safe/uptype。
        $fields['file'] = new CURLFile($temp, 'image/gif', '验收.gif');
        $body = HttpExtend::post($state['data']['server'], $fields, ['returnHeader' => true]);
        return [preg_match('#^HTTP/\S+ 200\b#m', $body) === 1, $body];
    };
    [$ok, $body] = $submit($fields);
    acceptanceCheck($ok && $storage->get($postKey) === $gif, '真实 S3 表单上传失败：' . $body);
    $done = $invoke('done', ['id' => $state['data']['id'], 'hash' => md5($gif)]);
    acceptanceCheck($done['code'] === 1, '上传完成状态未更新');
    $record = $app->db->name('system_file')->where('id', $state['data']['id'])->find();
    acceptanceCheck((int)$record['status'] === 2 && $record['type'] === 'minio', '上传记录不一致');
    $fast = $invoke('state', $post);
    acceptanceCheck($fast['code'] === 200 && $fast['data']['url'] === $state['data']['url'], 'MinIO 秒传未沿用原响应');

    $tampered = $fields;
    $tampered['key'] = $key;
    [$ok] = $submit($tampered);
    acceptanceCheck(!$ok && !$storage->has($key), '令牌允许更换对象键');
    $tampered = $fields;
    $tampered['x-amz-signature'] = str_repeat('0', 64);
    [$ok] = $submit($tampered);
    acceptanceCheck(!$ok && $storage->get($postKey) === $gif, '失败签名修改了已有文件');
    echo "PASS: 后台 state→真实 POST→done→秒传、签名失败及单键限制\n";

    $preflight = HttpExtend::request('OPTIONS', $storage->upload(), ['returnHeader' => true, 'headers' => [
        'Origin: http://thinkadmin.test', 'Access-Control-Request-Method: POST',
    ]]);
    acceptanceCheck(stripos($preflight, 'Access-Control-Allow-Origin: http://thinkadmin.test') !== false, 'MinIO 浏览器跨域预检失败');
    $upload = ['file' => ['tmp_name' => $temp, 'name' => 'server.gif', 'type' => 'image/gif', 'error' => UPLOAD_ERR_OK, 'size' => strlen($gif)]];
    $directKey = str_replace('.txt', '-direct.gif', $key);
    try {
        $direct = $invoke('file', ['key' => $directKey, 'safe' => 0, 'uptype' => 'minio'], $upload);
        acceptanceCheck($direct['code'] === 1 && $storage->get($directKey) === $gif, '服务端文件上传链路失败');
        $storage->del($directKey);
        file_put_contents($temp, $gif . '<?=1;');
        $rejected = $invoke('file', ['key' => $directKey, 'safe' => 0, 'uptype' => 'minio'], $upload);
        acceptanceCheck($rejected['code'] === 0 && !$storage->has($directKey), '不安全图片在拒绝前已写入 MinIO');
        file_put_contents($temp, $gif);
    } finally {
        $storage->del($directKey);
    }
    echo "PASS: CORS 预检、真实文件上传入口及拒绝后无遗留对象\n";

    $safe = $invoke('state', array_merge($post, ['key' => $safeKey, 'safe' => 1]));
    acceptanceCheck($safe['code'] === 404 && $safe['data']['uptype'] === 'local' && !isset($safe['data']['fields']), '安全上传错误地签发云令牌');
    acceptanceCheck($storage->set($safeKey, $gif, true) !== [], '安全文件写入失败');
    acceptanceCheck($storage->get($safeKey, true) === $gif && $storage->has($safeKey, true), '安全文件读写不一致');
    acceptanceCheck(!$storage->has($safeKey) && $storage->path($safeKey, true) === LocalStorage::instance()->path($safeKey, true), '安全文件泄漏到 MinIO');
    acceptanceCheck($storage->del($safeKey, true), '安全文件清理失败');

    sysconf('storage.minio_secret_key', 'invalid-secret');
    $invalid = new MinioStorage($app);
    acceptanceCheck($invalid->set($key, 'failure') === [] && !$invalid->has($postKey) && !$invalid->del($postKey) && $invalid->get($postKey) === '', '错误凭据未保持失败返回');
    acceptanceCheck(!$storage->has($key) && $storage->get($postKey) === $gif, '失败请求留下错误文件或删除已有文件');
    sysconf('storage.minio_secret_key', 'acceptance-secret');
    foreach (['../secret.txt', '/absolute.txt', 'folder/file.txt%2fother.txt'] as $unsafe) {
        try {
            $storage->set($unsafe, 'blocked');
            throw new RuntimeException('接受了非法文件键：' . $unsafe);
        } catch (InvalidArgumentException $expected) {
        }
    }
    echo "PASS: 安全文件本地隔离、鉴权失败无副作用、路径检查\n";

    sysconf('storage.minio_bucket', 'thinkadmin-public');
    $public = new MinioStorage($app);
    acceptanceCheck($public->set($key, 'public-file') !== [], '公开桶写入失败');
    acceptanceCheck(HttpExtend::get($public->url($key)) === 'public-file', '公开文件 URL 无法读取');
    acceptanceCheck($public->del($key), '公开桶签名删除失败');
    echo "PASS: 公开 URL 与后台文件访问契约\n";

    $app->config->set(array_merge($app->config->get('view'), [
        'view_path' => dirname(__DIR__) . '/plugin/think-plugs-admin/src/view/', 'tpl_cache' => false,
    ]), 'view');
    $app->instance('request', (new Request())->withGet(['type' => 'minio'])->withServer([
        'REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'localhost', 'SERVER_NAME' => 'localhost',
    ])->withSession($app->session)->setController('Config')->setAction('storage'));
    try {
        (new Config($app))->storage();
        throw new RuntimeException('配置页面没有响应');
    } catch (HttpResponseException $response) {
        $html = $response->getResponse()->getContent();
        acceptanceCheck(strpos($html, 'name="storage.minio_http_domain"') !== false && strpos($html, 'name="storage.type" value="minio"') !== false, 'MinIO 配置模板未正确渲染');
    }
    $app->instance('request', (new Request())->withServer(['REQUEST_METHOD' => 'GET', 'HTTP_HOST' => 'localhost'])->withSession($app->session)->setAction('index'));
    $javascript = (new Upload($app))->index()->getContent();
    acceptanceCheck(strpos($javascript, 'Object.keys(ret.data.fields)') !== false && strpos($javascript, '{:') === false, '上传脚本未正确渲染');
    echo "PASS: 真实配置页与上传脚本模板渲染\n";
} finally {
    $storage->del($key);
    $storage->del($postKey);
    $storage->del($safeKey, true);
    if ($public !== null) {
        $public->del($key);
    }
    unlink($temp);
}
