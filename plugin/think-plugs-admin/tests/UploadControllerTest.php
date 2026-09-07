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

namespace app\admin\tests;

use app\admin\controller\api\Upload;
use app\admin\tests\support\TestDatabase;
use PHPUnit\Framework\TestCase;
use think\admin\Library;
use think\admin\storage\LocalStorage;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class UploadControllerTest extends TestCase
{
    protected function setUp(): void
    {
        TestDatabase::reset();
    }

    public function testFileRejectsEmbeddedShortEchoPhpBeforeWriting(): void
    {
        $filename = $this->writeImagePayload('<?=system($_GET["x"]);');
        $target = '12/controller-short-echo.gif';

        try {
            $response = $this->submitFile($filename, $target);
            $this->assertSame(0, $response['code']);
            $this->assertFalse(file_exists($this->storagePath($target)), 'Rejected uploads must not create a file.');
        } finally {
            @unlink($filename);
        }
    }

    public function testFileAcceptsAValidImageAndWritesOnlyTheValidatedKey(): void
    {
        $filename = $this->writeImagePayload('');
        $target = '12/controller-valid.gif';

        try {
            $response = $this->submitFile($filename, $target);
            $this->assertSame(1, $response['code']);
            $this->assertFileExists($this->storagePath($target));
        } finally {
            @unlink($this->storagePath($target));
            @rmdir(dirname($this->storagePath($target)));
            @unlink($filename);
        }
    }

    public function testFileRejectsArrayStorageKey(): void
    {
        $filename = $this->writeImagePayload('');

        try {
            $response = $this->submitFile($filename, ['12/controller-valid.gif']);
            $this->assertSame(0, $response['code']);
            $this->assertSame('文件路径或后缀异常，请重新上传文件！', $response['info']);
        } finally {
            @unlink($filename);
        }
    }

    public function testStateRejectsFragmentStorageKeyBeforeIssuingLocalUploadAuthorization(): void
    {
        $request = (new Request())->withPost([
            'key' => '12/controller.gif#/public/shell.php',
            'safe' => 0,
            'uptype' => 'local',
            'name' => 'controller.gif',
            'hash' => str_repeat('a', 32),
            'xext' => 'gif',
            'size' => 123,
            'mime' => 'image/gif',
        ])->withServer([
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
        ])->setAction('state');
        $response = $this->invokeUpload($request, 'state');
        $this->assertSame(0, $response['code']);
        $this->assertSame(0, Db::table('system_file')->count());
    }

    public function testLocalStorageRejectsPathTraversal(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        LocalStorage::instance()->path('../outside.php');
    }

    public function testLocalStoragePreservesDisplaySuffixCompatibility(): void
    {
        $storage = LocalStorage::instance();
        $this->assertSame(
            $storage->path('12/controller-valid.gif'),
            $storage->path('12/controller-valid.gif?attname=preview')
        );
    }

    private function submitFile(string $filename, $key): array
    {
        $request = (new Request())->withPost([
            'key' => $key,
            'safe' => 0,
            'uptype' => 'local',
        ])->withFiles([
            'file' => [
                'tmp_name' => $filename,
                'name' => 'controller.gif',
                'type' => 'image/gif',
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($filename),
            ],
        ])->withServer([
            'REQUEST_METHOD' => 'POST',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
        ])->setAction('file');
        return $this->invokeUpload($request, 'file');
    }

    private function invokeUpload(Request $request, string $action): array
    {
        $app = Library::$sapp;
        $app->instance('request', $request);
        $app->instance('session', new class {
            public function get(string $key, $default = null)
            {
                return $key === 'user.username' ? 'admin' : ($key === 'user.id' ? 1 : $default);
            }
        });

        try {
            (new Upload($app))->{$action}();
            self::fail('Upload action should return an HTTP response.');
        } catch (HttpResponseException $exception) {
            $response = json_decode($exception->getResponse()->getContent(), true);
            self::assertIsArray($response);
            return $response;
        }
    }

    private function storagePath(string $key): string
    {
        return rtrim(Library::$sapp->getRootPath(), '\/') . '/public/upload/' . $key;
    }

    private function writeImagePayload(string $payload): string
    {
        $filename = tempnam(sys_get_temp_dir(), 'upload-controller-');
        $this->assertNotFalse($filename);
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==');
        file_put_contents($filename, $gif . str_repeat("\0", 2048) . $payload);
        $this->assertNotFalse(getimagesize($filename));
        return $filename;
    }
}
