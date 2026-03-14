<?php

declare(strict_types=1);

namespace think\admin\tests;

use plugin\system\service\SystemAuthService;
use plugin\system\controller\api\Upload as UploadController;
use plugin\system\service\SystemContext as PluginSystemContext;
use plugin\storage\model\SystemFile;
use think\Container;
use think\Request;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;

/**
 * @internal
 * @coversNothing
 */
class UploadControllerTest extends SqliteIntegrationTestCase
{
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
        $this->app->config->set(include TEST_PROJECT_ROOT . '/config/view.php', 'view');
    }

    public function testIndexBuildsUploadScriptUsingTokenRestrictedExtensions(): void
    {
        $this->createSystemConfigFixture([
            'type'  => 'storage',
            'name'  => 'allowed_exts',
            'value' => 'jpg,png,mp4',
        ]);
        $this->createSystemConfigFixture([
            'type'  => 'storage',
            'name'  => 'naming',
            'value' => 'date',
        ]);

        $response = $this->callIndexController([
            'uptoken' => SystemAuthService::withUploadToken(321, 'png,pdf,mp4'),
        ]);

        $contentType = strval($response->getHeader('Content-Type'));
        $content = strval($response->getContent());

        $this->assertStringContainsString('application/x-javascript', $contentType);
        $this->assertStringContainsString('"png":"image\\/png"', $content);
        $this->assertStringContainsString('"mp4":"video\\/mp4"', $content);
        $this->assertStringNotContainsString('"jpg":"image\\/jpeg"', $content);
        $this->assertStringNotContainsString('"pdf":"application\\/pdf"', $content);
        $this->assertStringContainsString("let IsDate = 'date'.indexOf('date') > -1;", $content);
    }

    public function testDoneMarksOwnedFileAsUploaded(): void
    {
        $file = $this->createSystemFileFixture([
            'uuid'   => 9101,
            'status' => 1,
            'hash'   => 'upload-hash-owned',
        ]);

        $result = $this->callActionController('done', [
            'id'   => $file->getAttr('id'),
            'hash' => 'upload-hash-owned',
        ], 9101);

        $file = $file->refresh();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('更新成功！', $result['info'] ?? '');
        $this->assertSame(2, intval($file->getAttr('status')));
    }

    public function testDoneRejectsFileFromAnotherAdminUser(): void
    {
        $file = $this->createSystemFileFixture([
            'uuid'   => 9201,
            'status' => 1,
            'hash'   => 'upload-hash-foreign',
        ]);

        $result = $this->callActionController('done', [
            'id'   => $file->getAttr('id'),
            'hash' => 'upload-hash-foreign',
        ], 9101);

        $file = $file->refresh();

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('文件不存在！', $result['info'] ?? '');
        $this->assertSame(1, intval($file->getAttr('status')));
    }

    private function callIndexController(array $query = [])
    {
        $request = (new Request())
            ->withGet($query)
            ->withServer(['HTTPS' => 'on'])
            ->setHost('admin.example.com')
            ->setMethod('GET')
            ->setController('upload')
            ->setAction('index');

        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        $controller = new UploadController($this->app);

        return $controller->index();
    }

    private function callActionController(string $action, array $payload, int $userId): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod('POST')
            ->setController('upload')
            ->setAction($action);

        $this->bindAdminUser($userId);
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

    private function bindAdminUser(int $userId): void
    {
        RequestContext::instance()->setAuth([
            'id'       => $userId,
            'username' => "admin-{$userId}",
            'password' => md5('changed-password'),
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
