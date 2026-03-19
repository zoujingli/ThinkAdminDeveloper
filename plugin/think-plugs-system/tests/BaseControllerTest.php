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

use plugin\system\controller\Base as BaseController;
use plugin\system\model\SystemBase;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class BaseControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexAndAddRenderBuilderPages(): void
    {
        $this->createSystemBaseFixture([
            'type' => 'identity',
            'code' => 'base-render',
            'name' => '渲染字典',
            'content' => SystemBase::packContent('渲染内容', 'index'),
        ]);

        $indexHtml = $this->callActionHtml('index', [
            'type' => 'identity',
        ], 'GET');
        $formHtml = $this->callActionHtml('add', [
            'type' => 'identity',
        ], 'GET');

        $this->assertStringContainsString('page-builder-schema', $indexHtml);
        $this->assertStringContainsString('name="type_select"', $formHtml);
        $this->assertStringContainsString('form-builder-schema', $formHtml);
    }

    public function testIndexFiltersByTypePluginGroupAndDateRange(): void
    {
        $this->createSystemBaseFixture([
            'type' => 'identity',
            'code' => 'base-hit',
            'name' => '命中字典',
            'content' => SystemBase::packContent('命中内容', 'index'),
            'create_time' => '2026-03-10 08:00:00',
        ]);
        $this->createSystemBaseFixture([
            'type' => 'identity',
            'code' => 'base-common',
            'name' => '公共字典',
            'content' => '公共内容',
            'create_time' => '2026-03-10 12:00:00',
        ]);
        $this->createSystemBaseFixture([
            'type' => 'identity',
            'code' => 'base-other-day',
            'name' => '跨日字典',
            'content' => SystemBase::packContent('跨日内容', 'index'),
            'create_time' => '2026-03-09 08:00:00',
        ]);
        $this->createSystemBaseFixture([
            'type' => 'payment',
            'code' => 'base-other-type',
            'name' => '其他类型字典',
            'content' => SystemBase::packContent('其他类型', 'index'),
            'create_time' => '2026-03-10 09:00:00',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'identity',
            'plugin_group' => 'index',
            'code' => 'base-hit',
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
        $this->assertSame('base-hit', $result['data']['list'][0]['code'] ?? '');
        $this->assertSame('index', $result['data']['list'][0]['plugin_group'] ?? '');
        $this->assertSame('Index', $result['data']['list'][0]['plugin_title'] ?? '');
        $this->assertSame('命中内容', $result['data']['list'][0]['content_text'] ?? '');
    }

    public function testAddAndEditPackPluginContent(): void
    {
        $add = $this->callFormController('add', [
            'type' => 'identity',
            'code' => 'base-create',
            'name' => '创建字典',
            'content_text' => '创建内容',
            'plugin_code' => 'index',
            'sort' => 20,
            'status' => 1,
        ]);

        $created = SystemBase::mk()->where(['type' => 'identity', 'code' => 'base-create'])->findOrEmpty();

        $this->assertSame(1, intval($add['code'] ?? 0));
        $this->assertSame('数据保存成功！', $add['info'] ?? '');
        $this->assertTrue($created->isExists());
        $createdMeta = SystemBase::parseContent(strval($created->getAttr('content')));
        $this->assertSame('创建内容', $createdMeta['text'] ?? '');
        $this->assertContains('index', (array)($createdMeta['plugin'] ?? []));

        $edit = $this->callFormController('edit', [
            'id' => intval($created->getAttr('id')),
            'type' => 'identity',
            'code' => 'base-create',
            'name' => '更新字典',
            'content_text' => '更新内容',
            'plugin_code' => '',
            'sort' => 30,
            'status' => 0,
        ]);

        $updated = SystemBase::mk()->findOrEmpty(intval($created->getAttr('id')));

        $this->assertSame(1, intval($edit['code'] ?? 0));
        $this->assertSame('数据保存成功！', $edit['info'] ?? '');
        $this->assertSame('更新字典', $updated->getAttr('name'));
        $updatedMeta = SystemBase::parseContent(strval($updated->getAttr('content')));
        $this->assertSame('更新内容', $updatedMeta['text'] ?? ($updated->getAttr('content') ?? ''));
        $this->assertSame(30, intval($updated->getAttr('sort')));
        $this->assertSame(0, intval($updated->getAttr('status')));
    }

    public function testAddRejectsDuplicateCodeWithinSameType(): void
    {
        $this->createSystemBaseFixture([
            'type' => 'identity',
            'code' => 'base-duplicate',
            'name' => '已有字典',
            'content' => '已有内容',
        ]);

        $result = $this->callFormController('add', [
            'type' => 'identity',
            'code' => 'base-duplicate',
            'name' => '重复字典',
            'content_text' => '重复内容',
            'plugin_code' => 'index',
            'sort' => 0,
            'status' => 1,
        ]);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('数据编码已经存在！', $result['info'] ?? '');
        $this->assertSame(1, SystemBase::mk()->where(['type' => 'identity', 'code' => 'base-duplicate'])->count());
    }

    public function testStateAndRemoveUpdateDictionaryLifecycle(): void
    {
        $item = $this->createSystemBaseFixture([
            'type' => 'identity',
            'code' => 'base-state-remove',
            'name' => '状态删除字典',
            'content' => '生命周期内容',
            'status' => 1,
        ]);

        $state = $this->callActionController('state', [
            'id' => intval($item->getAttr('id')),
            'status' => 0,
        ]);
        $afterState = SystemBase::mk()->findOrEmpty(intval($item->getAttr('id')));

        $remove = $this->callActionController('remove', [
            'id' => intval($item->getAttr('id')),
        ]);
        $afterRemove = SystemBase::mk()->withTrashed()->findOrEmpty(intval($item->getAttr('id')));

        $this->assertSame(1, intval($state['code'] ?? 0));
        $this->assertSame('数据保存成功！', $state['info'] ?? '');
        $this->assertSame(0, intval($afterState->getAttr('status')));
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('数据删除成功！', $remove['info'] ?? '');
        $this->assertNotEmpty($afterRemove->getAttr('delete_time'));
    }

    protected function defineSchema(): void
    {
        $this->createSystemBaseTable();
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('base')
            ->setAction('index');

        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new BaseController($this->app);
            $controller->index();
            self::fail('Expected BaseController::index to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callFormController(string $action, array $post): array
    {
        return $this->callActionController($action, $post);
    }

    private function callActionHtml(string $action, array $data = [], string $method = 'GET'): string
    {
        $request = (new Request())
            ->withGet($data)
            ->withPost($data)
            ->setMethod($method)
            ->setController('base')
            ->setAction($action);

        $this->setRequestPayload($request, $data);
        $this->app->instance('request', $request);

        try {
            $controller = new BaseController($this->app);
            $controller->{$action}();
            self::fail("Expected BaseController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callActionController(string $action, array $post): array
    {
        $request = (new Request())
            ->withGet($post)
            ->withPost($post)
            ->setMethod('POST')
            ->setController('base')
            ->setAction($action);

        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new BaseController($this->app);
            $controller->{$action}();
            self::fail("Expected BaseController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
