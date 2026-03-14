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

namespace think\admin\tests;

use plugin\storage\controller\api\Upload as UploadController;
use plugin\storage\model\SystemFile;
use plugin\system\service\SystemAuthService;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\App;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class StorageUploadControllerTest extends SqliteIntegrationTestCase
{
    private string $appRoot = '';

    public function testStateReturnsUploadAuthorizationForMissingLocalFile(): void
    {
        $result = $this->callActionController('state', [
            'key' => 'pending/upload-test.png',
            'name' => 'upload-test.png',
            'hash' => 'pending-hash',
            'xext' => 'png',
            'size' => 128,
            'mime' => 'image/png',
            'safe' => 0,
            'uptype' => 'local',
        ]);

        $file = SystemFile::mk()->where(['hash' => 'pending-hash'])->findOrEmpty();

        $this->assertSame(404, intval($result['code'] ?? 0));
        $this->assertSame('获取上传授权参数', $result['info'] ?? '');
        $this->assertSame('local', $result['data']['uptype'] ?? '');
        $this->assertSame(0, intval($result['data']['safe'] ?? -1));
        $this->assertStringContainsString('/upload/pending/upload-test.png', strval($result['data']['url'] ?? ''));
        $this->assertStringContainsString('/storage/api.upload/file', strval($result['data']['server'] ?? ''));
        $this->assertTrue($file->isExists());
        $this->assertSame('pending/upload-test.png', $file->getData('xkey'));
        $this->assertSame(0, intval($file->getData('isfast')));
        $this->assertSame(1, intval($file->getData('status')));
    }

    public function testStateReturnsInstantUploadWhenLocalFileAlreadyExists(): void
    {
        $this->createSandboxUploadFile('fast/existing.png');

        $result = $this->callActionController('state', [
            'key' => 'fast/existing.png',
            'name' => 'existing.png',
            'hash' => 'fast-hash',
            'xext' => 'png',
            'size' => 128,
            'mime' => 'image/png',
            'safe' => 0,
            'uptype' => 'local',
        ]);

        $file = SystemFile::mk()->where(['hash' => 'fast-hash'])->findOrEmpty();

        $this->assertSame(200, intval($result['code'] ?? 0));
        $this->assertSame('文件已经上传', $result['info'] ?? '');
        $this->assertSame('upload/fast/existing.png', $result['data']['key'] ?? '');
        $this->assertStringContainsString('/upload/fast/existing.png', strval($result['data']['url'] ?? ''));
        $this->assertTrue($file->isExists());
        $this->assertSame(1, intval($file->getData('isfast')));
        $this->assertSame('/upload/fast/existing.png', $file->getData('xurl'));
    }

    public function testFileStoresUploadedImageInSandboxAndReturnsPublicUrl(): void
    {
        $upload = $this->createUploadedPng('avatar.png');

        $result = $this->callFileController([
            'key' => 'local-tests/avatar.png',
            'safe' => 0,
            'uptype' => 'local',
        ], $upload);

        $target = $this->appRoot . 'public/upload/local-tests/avatar.png';

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('文件上传成功！', $result['info'] ?? '');
        $this->assertSame('/upload/local-tests/avatar.png', $result['data']['url'] ?? '');
        $this->assertFileExists($target);
        $this->assertGreaterThan(0, filesize($target));
    }

    public function testFileStoresSafeUploadInSandboxAndReturnsStorageKey(): void
    {
        $upload = $this->createUploadedPng('safe-avatar.png');

        $result = $this->callFileController([
            'key' => 'safe-tests/safe-avatar.png',
            'safe' => 1,
            'uptype' => 'local',
        ], $upload);

        $target = $this->appRoot . 'safefile/safe-tests/safe-avatar.png';

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('文件上传成功！', $result['info'] ?? '');
        $this->assertSame('safe-tests/safe-avatar.png', $result['data']['url'] ?? '');
        $this->assertFileExists($target);
        $this->assertGreaterThan(0, filesize($target));
    }

    public function testImageFiltersCompletedUnsafeFilesForCurrentAdmin(): void
    {
        $this->createSystemFileFixture([
            'uuid' => 9101,
            'name' => 'picker-hit.png',
            'hash' => 'hash-picker-hit',
            'xext' => 'png',
            'create_time' => '2026-03-10 09:00:00',
            'update_time' => '2026-03-10 09:00:00',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9101,
            'name' => 'picker-other-type.jpg',
            'hash' => 'hash-picker-jpg',
            'xext' => 'jpg',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9101,
            'name' => 'picker-safe.png',
            'hash' => 'hash-picker-safe',
            'xext' => 'png',
            'issafe' => 1,
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9101,
            'name' => 'picker-pending.png',
            'hash' => 'hash-picker-pending',
            'xext' => 'png',
            'status' => 1,
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9102,
            'name' => 'picker-other-admin.png',
            'hash' => 'hash-picker-other-admin',
            'xext' => 'png',
        ]);

        $result = $this->callImageController([
            'output' => 'json',
            'type' => 'png',
            'name' => 'picker-hit',
            'create_time' => '2026-03-10 - 2026-03-10',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('picker-hit.png', $result['data']['list'][0]['name'] ?? '');
    }

    public function testImageMergesCurrentAdminAndUploadTokenOwnerScopes(): void
    {
        $this->createSystemFileFixture([
            'uuid' => 9101,
            'name' => 'picker-admin-visible.png',
            'hash' => 'hash-picker-admin-visible',
            'xext' => 'png',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 0,
            'unid' => 5001,
            'name' => 'picker-unid-visible.png',
            'hash' => 'hash-picker-unid-visible',
            'xext' => 'png',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 0,
            'unid' => 5002,
            'name' => 'picker-unid-hidden.png',
            'hash' => 'hash-picker-unid-hidden',
            'xext' => 'png',
        ]);

        $result = $this->callImageController([
            'output' => 'json',
            'type' => 'png',
            'uptoken' => SystemAuthService::withUploadToken(5001, 'png'),
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(2, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(2, $result['data']['list'] ?? []);
        $this->assertEqualsCanonicalizing([
            'picker-admin-visible.png',
            'picker-unid-visible.png',
        ], array_column($result['data']['list'] ?? [], 'name'));
    }

    protected function defineSchema(): void
    {
        $this->createSystemConfigTable();
        $this->createSystemFileTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);

        $this->appRoot = $this->sandboxPath . '/app-root/';
        is_dir($this->appRoot) || mkdir($this->appRoot, 0777, true);
        $this->setAppRootPath($this->appRoot);

        $this->createSystemConfigFixture([
            'type' => 'storage',
            'name' => 'driver',
            'value' => 'local',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'storage',
            'name' => 'allowed_exts',
            'value' => 'png,jpg',
        ]);
        $this->createSystemConfigFixture([
            'type' => 'storage',
            'name' => 'local.protocol',
            'value' => 'path',
        ]);
    }

    private function callActionController(string $action, array $payload): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->withServer(['HTTPS' => 'on'])
            ->setHost('storage.example.com')
            ->setMethod('POST')
            ->setController('upload')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new UploadController($this->app);
            $controller->{$action}();
            self::fail("Expected UploadController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callImageController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('upload')
            ->setAction('image');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new UploadController($this->app);
            $controller->image();
            self::fail('Expected UploadController::image to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callFileController(array $payload, array $file): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->withFiles(['file' => $file])
            ->withServer(['HTTPS' => 'on'])
            ->setHost('storage.example.com')
            ->setMethod('POST')
            ->setController('upload')
            ->setAction('file');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new UploadController($this->app);
            $controller->file();
            self::fail('Expected UploadController::file to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        RequestContext::instance()->setAuth([
            'id' => 9101,
            'username' => 'storage-admin',
            'password' => md5('changed-password'),
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }

    private function setAppRootPath(string $rootPath): void
    {
        $rootPath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $property = new \ReflectionProperty(App::class, 'rootPath');
        $property->setAccessible(true);
        $property->setValue($this->app, $rootPath);

        $this->app->setAppPath($rootPath . 'app' . DIRECTORY_SEPARATOR);
        $this->app->setRuntimePath($rootPath . 'runtime' . DIRECTORY_SEPARATOR);
    }

    private function createSandboxUploadFile(string $name): string
    {
        $target = $this->appRoot . 'public/upload/' . $name;
        is_dir(dirname($target)) || mkdir(dirname($target), 0777, true);
        file_put_contents($target, base64_decode($this->tinyPngBase64()));

        return $target;
    }

    private function createUploadedPng(string $name): array
    {
        $source = $this->sandboxPath . '/' . $name;
        file_put_contents($source, base64_decode($this->tinyPngBase64()));

        return [
            'name' => $name,
            'type' => 'image/png',
            'tmp_name' => $source,
            'error' => 0,
            'size' => filesize($source),
        ];
    }

    private function tinyPngBase64(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+j5QAAAABJRU5ErkJggg==';
    }
}
