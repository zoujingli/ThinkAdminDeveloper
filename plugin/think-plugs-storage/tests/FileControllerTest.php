<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\tests;

use plugin\storage\controller\File as FileController;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class FileControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexFiltersCurrentAdminFilesFromStorageRoute(): void
    {
        $this->createSystemFileFixture([
            'uuid' => 9101,
            'type' => 'local',
            'name' => 'storage-hit.png',
            'hash' => 'storage-hit-hash',
            'xext' => 'png',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9102,
            'type' => 'local',
            'name' => 'storage-other-user.png',
            'hash' => 'storage-other-user-hash',
            'xext' => 'png',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'local',
            'name' => 'storage-hit',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('storage-hit.png', $result['data']['list'][0]['name'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createSystemFileTable();
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('file')
            ->setAction('index');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new FileController($this->app);
            $controller->index();
            self::fail('Expected FileController::index to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        $this->context->setUser([
            'id' => 9101,
            'username' => 'tester',
        ], true)->setNodes([
            'storage/file/index',
            'storage/file/edit',
            'storage/file/remove',
            'storage/file/distinct',
        ]);
        RequestContext::instance()->setAuth([
            'id' => 9101,
            'username' => 'tester',
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
