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

use plugin\system\controller\Auth as AuthController;
use plugin\system\model\SystemAuth;
use plugin\system\model\SystemNode;
use plugin\system\service\LangService;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class AuthControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexFiltersRolesByStatusKeywordAndCommonGroup(): void
    {
        $this->createSystemAuthFixture([
            'title' => '命中权限',
            'code' => 'match-role',
            'remark' => '命中说明',
            'status' => 1,
            'create_time' => '2026-03-10 08:00:00',
        ]);
        $this->createSystemAuthFixture([
            'title' => '跨日权限',
            'code' => 'cross-day-role',
            'remark' => '跨日说明',
            'status' => 1,
            'create_time' => '2026-03-09 08:00:00',
        ]);
        $this->createSystemAuthFixture([
            'title' => '禁用权限',
            'code' => 'disabled-role',
            'remark' => '禁用说明',
            'status' => 0,
            'create_time' => '2026-03-10 09:00:00',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'index',
            'title' => '命中',
            'create_time' => '2026-03-10 - 2026-03-10',
            'plugin_group' => 'common',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('命中权限', $result['data']['list'][0]['title'] ?? '');
        $this->assertSame('common', $result['data']['list'][0]['plugin_group'] ?? '');
    }

    public function testIndexGetRendersPageBuilderMarkup(): void
    {
        $html = $this->callActionHtml('index', ['plugin_group' => 'tester']);

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('id="RoleTable"', $html);
        $this->assertStringContainsString('StatusSwitchRoleTable', $html);
        $this->assertStringContainsString('data-open=', $html);
        $this->assertStringNotContainsString('<fieldset><legend>条件搜索</legend>', $html);
        $this->assertStringContainsString('class="layui-card-table"', $html);
        $this->assertStringContainsString('class="layui-tab layui-tab-card"', $html);
        $this->assertStringContainsString('class="layui-tab-content"', $html);
        $this->assertStringContainsString('系统权限', $html);
        $this->assertStringContainsString('回 收 站', $html);
    }

    public function testAddGetRendersBuilderFormMarkup(): void
    {
        $html = $this->callActionHtml('add', ['plugin' => 'tester']);

        $this->assertStringContainsString('id="RoleForm"', $html);
        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('AuthPluginFilter', $html);
        $this->assertStringContainsString('data-target-backup', $html);
        $this->assertStringContainsString('layui-card-header', $html);
        $this->assertStringContainsString('layui-card-line', $html);
        $this->assertStringContainsString('class="layui-card-table"', $html);
        $this->assertStringContainsString('class="think-box-shadow"', $html);
        $this->assertStringContainsString('name="code"', $html);
        $this->assertStringContainsString('name="remark"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('id="AuthTreeKeyword"', $html);
        $this->assertStringContainsString('id="AuthTreeKeywordClear"', $html);
        $this->assertStringContainsString('搜索权限节点名称，按 / 快速聚焦', $html);
        $this->assertStringContainsString('keydown.auth-tree-search', $html);
        $this->assertStringContainsString('id="AuthTreeSelectedOnly"', $html);
        $this->assertStringContainsString('只看已选', $html);
        $this->assertStringContainsString('data-tree-action="select-visible"', $html);
        $this->assertStringContainsString('data-tree-action="clear-visible"', $html);
        $this->assertStringContainsString('id="AuthTreePanel"', $html);
        $this->assertStringContainsString('data-role-plugin="tester"', $html);
        $this->assertStringContainsString('data-role-action-url="/auth/add.html?plugin=tester"', $html);
        $this->assertStringContainsString('data-expand-node', $html);
        $this->assertStringContainsString('auth-plugin-title', $html);
        $this->assertStringContainsString('auth-group-title', $html);
        $this->assertStringContainsString('auth-group-grid', $html);
        $this->assertStringContainsString('auth-plugin-card', $html);
        $this->assertStringContainsString('data-batch-type="select"', $html);
        $this->assertStringContainsString('data-batch-type="clear"', $html);
        $this->assertStringContainsString('系统权限编辑', $html);
        $this->assertStringContainsString('返回列表', $html);
        $this->assertStringContainsString('class="pa40"', $html);
        $this->assertStringContainsString("this.filter = String(\$roleForm.data('rolePlugin') || '');", $html);
        $this->assertStringContainsString("this.actionUrl = String(\$roleForm.data('roleActionUrl') || \$roleForm.attr('action') || '');", $html);
        $this->assertStringContainsString('system-auth-tree-state', $html);
        $this->assertStringContainsString('localStorage.setItem', $html);
        $this->assertStringContainsString('确认保存权限变更', $html);
        $this->assertStringContainsString('本次权限变更', $html);
        $this->assertStringNotContainsString('<style>', $html);
        $this->assertStringNotContainsString('{$actionUrl', $html);
        $this->assertStringNotContainsString('{$vo.id', $html);
    }

    public function testAddGetRendersEnglishPermissionTreeControlsWhenLangSetIsEnUs(): void
    {
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('add', ['plugin' => 'tester']);

        $this->assertStringContainsString('All Plugins', $html);
        $this->assertStringContainsString('Selected Only', $html);
        $this->assertStringContainsString('Permission Changes', $html);
        $this->assertStringNotContainsString('全部插件', $html);
    }

    public function testAddJsonReturnsTreePayload(): void
    {
        $result = $this->callFormController('add', ['action' => 'json']);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('获取权限节点成功！', $result['info'] ?? '');
        $this->assertIsArray($result['data'] ?? null);
    }

    public function testAddAndEditPersistRoleNodes(): void
    {
        $add = $this->callFormController('add', [
            'action' => 'save',
            'title' => '新增权限',
            'code' => 'system-create-role',
            'remark' => '新增说明',
            'sort' => 12,
            'status' => 1,
            'nodes' => ['index/test/create', 'index/test/update'],
        ]);

        $created = SystemAuth::mk()->where(['title' => '新增权限'])->findOrEmpty();

        $this->assertSame(1, intval($add['code'] ?? 0));
        $this->assertSame('权限修改成功！', $add['info'] ?? '');
        $this->assertTrue($created->isExists());
        $this->assertSame(2, SystemNode::mk()->where(['auth' => $created->getAttr('id')])->count());

        $edit = $this->callFormController('edit', [
            'action' => 'save',
            'id' => intval($created->getAttr('id')),
            'title' => '更新权限',
            'code' => 'system-manager-role',
            'remark' => '更新说明',
            'sort' => 20,
            'status' => 0,
            'nodes' => ['index/test/final'],
        ]);

        $updated = SystemAuth::mk()->findOrEmpty(intval($created->getAttr('id')));
        $nodes = SystemNode::mk()->where(['auth' => $created->getAttr('id')])->column('node');

        $this->assertSame(1, intval($edit['code'] ?? 0));
        $this->assertSame('权限修改成功！', $edit['info'] ?? '');
        $this->assertSame('更新权限', $updated->getData('title'));
        $this->assertSame('system-manager-role', $updated->getData('code'));
        $this->assertSame('更新说明', $updated->getData('remark'));
        $this->assertSame(20, intval($updated->getData('sort')));
        $this->assertSame(0, intval($updated->getData('status')));
        $this->assertSame(['index/test/final'], array_values($nodes));
    }

    public function testAddRejectsMissingNodes(): void
    {
        $result = $this->callFormController('add', [
            'action' => 'save',
            'title' => '缺少节点权限',
            'code' => 'missing-node-role',
            'remark' => '空节点',
            'sort' => 0,
            'status' => 1,
            'nodes' => [],
        ]);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('未配置功能节点！', $result['info'] ?? '');
        $this->assertSame(0, SystemAuth::mk()->where(['title' => '缺少节点权限'])->count());
    }

    public function testStateAndRemoveUpdateRoleLifecycleAndCleanupNodes(): void
    {
        $auth = $this->createSystemAuthFixture([
            'title' => '生命周期权限',
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
            'id' => intval($auth->getAttr('id')),
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

    protected function defineSchema(): void
    {
        $this->createSystemAuthTable();
        $this->createSystemAuthNodeTable();
    }

    private function switchSystemLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        LangService::load($this->app, $langSet);
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

    private function callActionHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('auth')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new AuthController($this->app);
            $controller->{$action}();
            self::fail("Expected AuthController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
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
