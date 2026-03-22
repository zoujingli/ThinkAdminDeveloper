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

use plugin\system\controller\File as FileController;
use plugin\system\model\SystemFile;
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
    public function testIndexFiltersFilesByCurrentAdminTypeAndDateRange(): void
    {
        $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'report-hit.png',
            'hash' => 'hash-hit',
            'xext' => 'png',
            'create_time' => '2026-03-10 08:00:00',
            'update_time' => '2026-03-10 08:00:00',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'report-other-day.png',
            'hash' => 'hash-other-day',
            'xext' => 'png',
            'create_time' => '2026-03-09 08:00:00',
            'update_time' => '2026-03-09 08:00:00',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'qiniu',
            'name' => 'report-other-type.png',
            'hash' => 'hash-other-type',
            'xext' => 'png',
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'report-safe.png',
            'hash' => 'hash-safe',
            'xext' => 'png',
            'issafe' => 1,
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'report-pending.png',
            'hash' => 'hash-pending',
            'xext' => 'png',
            'status' => 1,
        ]);
        $this->createSystemFileFixture([
            'uuid' => 9002,
            'type' => 'local',
            'name' => 'report-other-user.png',
            'hash' => 'hash-other-user',
            'xext' => 'png',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'local',
            'name' => 'report-hit',
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
        $this->assertSame('report-hit.png', $result['data']['list'][0]['name'] ?? '');
        $this->assertSame('local', $result['data']['list'][0]['type'] ?? '');
        $this->assertSame('本地服务器存储', $result['data']['list'][0]['ctype'] ?? '');
    }

    public function testIndexPaginatesCurrentAdminFilesAndFallsBackToDefaultLimit(): void
    {
        for ($i = 1; $i <= 21; ++$i) {
            $this->createSystemFileFixture([
                'uuid' => 9001,
                'type' => 'local',
                'name' => sprintf('page-file-%02d.png', $i),
                'hash' => sprintf('hash-page-%02d', $i),
                'xext' => 'png',
            ]);
        }

        $this->createSystemFileFixture([
            'uuid' => 9002,
            'type' => 'local',
            'name' => 'page-file-other-user.png',
            'hash' => 'hash-page-other-user',
            'xext' => 'png',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'local',
            'xext' => 'png',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 999,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(21, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(20, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('page-file-21.png', $result['data']['list'][0]['name'] ?? '');
        $this->assertSame('本地服务器存储', $result['data']['list'][0]['ctype'] ?? '');
    }

    public function testRemoveOnlyDeletesCurrentAdminFiles(): void
    {
        $owned = $this->createSystemFileFixture([
            'uuid' => 9001,
            'name' => 'owned-remove.png',
            'hash' => 'hash-owned-remove',
            'xkey' => 'upload/owned-remove.png',
        ]);
        $other = $this->createSystemFileFixture([
            'uuid' => 9002,
            'name' => 'other-remove.png',
            'hash' => 'hash-other-remove',
            'xkey' => 'upload/other-remove.png',
        ]);
        $ownedKeep = $this->createSystemFileFixture([
            'uuid' => 9001,
            'name' => 'owned-keep.png',
            'hash' => 'hash-owned-keep',
            'xkey' => 'upload/owned-keep.png',
        ]);

        $result = $this->callActionController('remove', [
            'id' => $owned->getAttr('id') . ',' . $other->getAttr('id'),
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('数据删除成功！', $result['info'] ?? '');
        $this->assertFalse(SystemFile::mk()->where(['id' => $owned->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertTrue(SystemFile::mk()->where(['id' => $other->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertTrue(SystemFile::mk()->where(['id' => $ownedKeep->getAttr('id')])->findOrEmpty()->isExists());
    }

    public function testDistinctRemovesDuplicateUnsafeFilesForCurrentAdminOnly(): void
    {
        $duplicateA = $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'duplicate-a.png',
            'hash' => 'hash-duplicate-a',
            'xkey' => 'upload/repeat.png',
        ]);
        $duplicateB = $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'duplicate-b.png',
            'hash' => 'hash-duplicate-b',
            'xkey' => 'upload/repeat.png',
        ]);
        $safeDuplicate = $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'duplicate-safe.png',
            'hash' => 'hash-duplicate-safe',
            'xkey' => 'upload/repeat.png',
            'issafe' => 1,
        ]);
        $otherUserDuplicate = $this->createSystemFileFixture([
            'uuid' => 9002,
            'type' => 'local',
            'name' => 'duplicate-other-user.png',
            'hash' => 'hash-duplicate-other-user',
            'xkey' => 'upload/repeat.png',
        ]);
        $otherTypeDuplicate = $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'qiniu',
            'name' => 'duplicate-other-type.png',
            'hash' => 'hash-duplicate-other-type',
            'xkey' => 'upload/repeat.png',
        ]);

        $result = $this->callActionController('distinct');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('清理重复文件成功！', $result['info'] ?? '');
        $this->assertFalse(SystemFile::mk()->where(['id' => $duplicateA->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertTrue(SystemFile::mk()->where(['id' => $duplicateB->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertTrue(SystemFile::mk()->where(['id' => $safeDuplicate->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertTrue(SystemFile::mk()->where(['id' => $otherUserDuplicate->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertTrue(SystemFile::mk()->where(['id' => $otherTypeDuplicate->getAttr('id')])->findOrEmpty()->isExists());
    }

    public function testEditPostUpdatesOwnedFileName(): void
    {
        $owned = $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'before-edit.png',
            'hash' => 'hash-before-edit',
        ]);

        $result = $this->callEditSave([
            'id' => intval($owned->getAttr('id')),
            'name' => 'after-edit.png',
        ]);

        $record = SystemFile::mk()->where(['id' => $owned->getAttr('id')])->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('数据保存成功！', $result['info'] ?? '');
        $this->assertSame('after-edit.png', $record->getAttr('name'));
    }

    public function testEditGetRendersFormBuilderMarkup(): void
    {
        $owned = $this->createSystemFileFixture([
            'uuid' => 9001,
            'type' => 'local',
            'name' => 'builder-edit.png',
            'hash' => 'hash-builder-edit',
        ]);

        $html = $this->callEditHtml([
            'id' => intval($owned->getAttr('id')),
        ]);

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('name="name"', $html);
        $this->assertStringContainsString('name="size_display"', $html);
    }

    public function testEditRejectsFilesOwnedByOtherAdmin(): void
    {
        $other = $this->createSystemFileFixture([
            'uuid' => 9002,
            'type' => 'local',
            'name' => 'other-edit.png',
            'hash' => 'hash-other-edit',
        ]);

        $view = $this->callEditJson('GET', ['id' => intval($other->getAttr('id'))]);
        $save = $this->callEditJson('POST', [
            'id' => intval($other->getAttr('id')),
            'name' => 'other-edit-new.png',
        ]);
        $record = SystemFile::mk()->where(['id' => $other->getAttr('id')])->findOrEmpty();

        $this->assertSame(0, intval($view['code'] ?? 1));
        $this->assertSame('文件记录不存在！', $view['info'] ?? '');
        $this->assertSame(0, intval($save['code'] ?? 1));
        $this->assertSame('文件记录不存在！', $save['info'] ?? '');
        $this->assertSame('other-edit.png', $record->getAttr('name'));
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

    private function callActionController(string $action, array $post = []): array
    {
        $request = (new Request())
            ->withGet($post)
            ->withPost($post)
            ->setMethod('POST')
            ->setController('file')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new FileController($this->app);
            $controller->{$action}();
            self::fail("Expected FileController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callEditSave(array $post): array
    {
        return $this->callEditJson('POST', $post);
    }

    private function callEditHtml(array $query): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('file')
            ->setAction('edit');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new FileController($this->app);
            $controller->edit();
            self::fail('Expected FileController::edit to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callEditJson(string $method, array $data): array
    {
        $request = (new Request())
            ->withGet($data)
            ->withPost($data)
            ->setMethod($method)
            ->setController('file')
            ->setAction('edit');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $data);
        $this->app->instance('request', $request);

        try {
            $controller = new FileController($this->app);
            $controller->edit();
            self::fail('Expected FileController::edit to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        $this->context->setUser([
            'id' => 9001,
            'username' => 'tester',
        ], true)->setNodes([
            'system/file/index',
            'system/file/edit',
            'system/file/remove',
            'system/file/distinct',
        ]);
        RequestContext::instance()->setAuth([
            'id' => 9001,
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
