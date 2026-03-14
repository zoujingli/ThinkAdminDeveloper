<?php

declare(strict_types=1);

namespace think\admin\tests;

use plugin\system\controller\Auth as AuthController;
use plugin\system\model\SystemAuth;
use plugin\system\model\SystemNode;
use think\Request;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;

/**
 * @internal
 * @coversNothing
 */
class AuthControllerTest extends SqliteIntegrationTestCase
{
    protected function defineSchema(): void
    {
        $this->createSystemAuthTable();
        $this->createSystemAuthNodeTable();
    }

    public function testIndexFiltersRolesByStatusKeywordAndCommonGroup(): void
    {
        $this->createSystemAuthFixture([
            'title'       => '命中权限',
            'utype'       => 'staff',
            'desc'        => '命中说明',
            'status'      => 1,
            'create_time' => '2026-03-10 08:00:00',
        ]);
        $this->createSystemAuthFixture([
            'title'       => '跨日权限',
            'utype'       => 'staff',
            'desc'        => '跨日说明',
            'status'      => 1,
            'create_time' => '2026-03-09 08:00:00',
        ]);
        $this->createSystemAuthFixture([
            'title'       => '禁用权限',
            'utype'       => 'staff',
            'desc'        => '禁用说明',
            'status'      => 0,
            'create_time' => '2026-03-10 09:00:00',
        ]);

        $result = $this->callIndexController([
            'output'       => 'json',
            'status'       => 1,
            'utype'        => 'staff',
            'title'        => '命中',
            'create_time'  => '2026-03-10 - 2026-03-10',
            'plugin_group' => 'common',
            '_field_'      => 'id',
            '_order_'      => 'asc',
            'page'         => 1,
            'limit'        => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('命中权限', $result['data']['list'][0]['title'] ?? '');
        $this->assertSame('common', $result['data']['list'][0]['plugin_group'] ?? '');
    }

    public function testAddAndEditPersistRoleNodes(): void
    {
        $add = $this->callFormController('add', [
            'action' => 'save',
            'title'  => '新增权限',
            'utype'  => 'staff',
            'desc'   => '新增说明',
            'sort'   => 12,
            'status' => 1,
            'nodes'  => ['index/test/create', 'index/test/update'],
        ]);

        $created = SystemAuth::mk()->where(['title' => '新增权限'])->findOrEmpty();

        $this->assertSame(1, intval($add['code'] ?? 0));
        $this->assertSame('权限修改成功！', $add['info'] ?? '');
        $this->assertTrue($created->isExists());
        $this->assertSame(2, SystemNode::mk()->where(['auth' => $created->getAttr('id')])->count());

        $edit = $this->callFormController('edit', [
            'action' => 'save',
            'id'     => intval($created->getAttr('id')),
            'title'  => '更新权限',
            'utype'  => 'manager',
            'desc'   => '更新说明',
            'sort'   => 20,
            'status' => 0,
            'nodes'  => ['index/test/final'],
        ]);

        $updated = SystemAuth::mk()->findOrEmpty(intval($created->getAttr('id')));
        $nodes = SystemNode::mk()->where(['auth' => $created->getAttr('id')])->column('node');

        $this->assertSame(1, intval($edit['code'] ?? 0));
        $this->assertSame('权限修改成功！', $edit['info'] ?? '');
        $this->assertSame('更新权限', $updated->getData('title'));
        $this->assertSame('manager', $updated->getData('utype'));
        $this->assertSame('更新说明', $updated->getData('desc'));
        $this->assertSame(20, intval($updated->getData('sort')));
        $this->assertSame(0, intval($updated->getData('status')));
        $this->assertSame(['index/test/final'], array_values($nodes));
    }

    public function testAddRejectsMissingNodes(): void
    {
        $result = $this->callFormController('add', [
            'action' => 'save',
            'title'  => '缺少节点权限',
            'utype'  => 'staff',
            'desc'   => '空节点',
            'sort'   => 0,
            'status' => 1,
            'nodes'  => [],
        ]);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('未配置功能节点！', $result['info'] ?? '');
        $this->assertSame(0, SystemAuth::mk()->where(['title' => '缺少节点权限'])->count());
    }

    public function testStateAndRemoveUpdateRoleLifecycleAndCleanupNodes(): void
    {
        $auth = $this->createSystemAuthFixture([
            'title'  => '生命周期权限',
            'status' => 1,
        ]);
        $this->createSystemAuthNodeFixture([
            'auth' => intval($auth->getAttr('id')),
            'node' => 'index/test/remove-a',
        ]);
        $this->createSystemAuthNodeFixture([
            'auth' => intval($auth->getAttr('id')),
            'node' => 'index/test/remove-b',
        ]);

        $state = $this->callActionController('state', [
            'id'     => intval($auth->getAttr('id')),
            'status' => 0,
        ]);
        $afterState = SystemAuth::mk()->findOrEmpty(intval($auth->getAttr('id')));

        $remove = $this->callActionController('remove', [
            'id' => intval($auth->getAttr('id')),
        ]);

        $this->assertSame(1, intval($state['code'] ?? 0));
        $this->assertSame('数据保存成功！', $state['info'] ?? '');
        $this->assertSame(0, intval($afterState->getData('status')));
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('数据删除成功！', $remove['info'] ?? '');
        $this->assertFalse(SystemAuth::mk()->where(['id' => $auth->getAttr('id')])->findOrEmpty()->isExists());
        $this->assertSame(0, SystemNode::mk()->where(['auth' => intval($auth->getAttr('id'))])->count());
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('auth')
            ->setAction('index');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new AuthController($this->app);
            $controller->index();
            self::fail('Expected AuthController::index to throw HttpResponseException.');
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callFormController(string $action, array $post): array
    {
        return $this->callActionController($action, $post);
    }

    private function callActionController(string $action, array $post = []): array
    {
        $request = (new Request())
            ->withGet($post)
            ->withPost($post)
            ->setMethod('POST')
            ->setController('auth')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new AuthController($this->app);
            $controller->{$action}();
            self::fail("Expected AuthController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        RequestContext::instance()->setAuth([
            'id'       => 9101,
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
