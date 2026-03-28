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

use plugin\system\controller\Menu as MenuController;
use plugin\system\model\SystemMenu;
use plugin\system\service\LangService;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class MenuControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexGetRendersPageBuilderMarkup(): void
    {
        $html = $this->callActionHtml('index', ['type' => 'index']);

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('id="MenuTable"', $html);
        $this->assertStringContainsString('class="layui-tab-content"', $html);
        $this->assertStringContainsString('添加菜单', $html);
        $this->assertStringNotContainsString('{:url(', $html);
        $this->assertStringNotContainsString('{foreach', $html);
        $this->assertStringNotContainsString('{$indexUrl', $html);
        $this->assertStringNotContainsString('MenuStatusSwitchTpl', $html);
        $this->assertStringNotContainsString('MenuToolbarTpl', $html);
        $this->assertStringContainsString("/menu/add.html?pid=' + d.id", $html);
        $this->assertStringContainsString("/menu/edit.html?id=' + d.id", $html);
    }

    public function testIndexFlattensTreeAndNormalizesInternalUrls(): void
    {
        $root = $this->createSystemMenuFixture([
            'title' => '系统配置',
            'url' => '#',
            'status' => 1,
        ]);
        $this->createSystemMenuFixture([
            'pid' => intval($root->getAttr('id')),
            'title' => '数据字典',
            'url' => 'system/base/index',
            'params' => 'tab=dict',
            'status' => 1,
            'sort' => 20,
        ]);
        $this->createSystemMenuFixture([
            'pid' => intval($root->getAttr('id')),
            'title' => '外链文档',
            'url' => 'https://example.com/docs',
            'status' => 1,
            'sort' => 10,
        ]);
        $other = $this->createSystemMenuFixture([
            'title' => '其它根菜单',
            'url' => '#',
            'status' => 1,
        ]);
        $this->createSystemMenuFixture([
            'pid' => intval($other->getAttr('id')),
            'title' => '其它子菜单',
            'url' => 'system/auth/index',
            'status' => 1,
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'index',
            'pid' => intval($root->getAttr('id')),
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertCount(3, $result['data']['list'] ?? []);
        $this->assertSame('系统配置', $result['data']['list'][0]['title'] ?? '');
        $this->assertSame('数据字典', $result['data']['list'][1]['title'] ?? '');
        $this->assertStringContainsString('system/base', $result['data']['list'][1]['url'] ?? '');
        $this->assertStringContainsString('tab=dict', $result['data']['list'][1]['url'] ?? '');
        $this->assertSame('https://example.com/docs', $result['data']['list'][2]['url'] ?? '');
    }

    public function testRecycleShowsDisabledBranchOnly(): void
    {
        $root = $this->createSystemMenuFixture([
            'title' => '回收根菜单',
            'url' => '#',
            'status' => 1,
        ]);
        $this->createSystemMenuFixture([
            'pid' => intval($root->getAttr('id')),
            'title' => '已删除子菜单',
            'url' => 'system/auth/index',
            'status' => 0,
        ]);
        $this->createSystemMenuFixture([
            'pid' => intval($root->getAttr('id')),
            'title' => '有效子菜单',
            'url' => 'system/base/index',
            'status' => 1,
        ]);
        $this->createSystemMenuFixture([
            'title' => '孤立禁用根菜单',
            'url' => '#',
            'status' => 0,
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'recycle',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $titles = array_column($result['data']['list'] ?? [], 'title');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertContains('回收根菜单', $titles);
        $this->assertContains('已删除子菜单', $titles);
        $this->assertNotContains('有效子菜单', $titles);
        $this->assertNotContains('孤立禁用根菜单', $titles);
    }

    public function testAddGetRendersBuilderFormMarkup(): void
    {
        $this->createSystemMenuFixture([
            'title' => '根菜单',
            'url' => '#',
        ]);

        $html = $this->callActionHtml('add', ['pid' => 0]);

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('name="title"', $html);
        $this->assertStringContainsString('data-menu-nodes=', $html);
        $this->assertStringContainsString('data-menu-auths=', $html);
        $this->assertStringContainsString('data-open-menu-icon', $html);
        $this->assertStringContainsString('标准化链接预览', $html);
        $this->assertStringContainsString('name="target"', $html);
    }

    public function testAddGetRendersEnglishIconPickerTextsWhenLangSetIsEnUs(): void
    {
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('add', ['pid' => 0]);

        $this->assertStringContainsString('Icon Picker', $html);
        $this->assertStringContainsString('Icon not set', $html);
        $this->assertStringContainsString('Choose Menu Icon', $html);
        $this->assertStringNotContainsString('未设置图标', $html);
    }

    public function testAddAndEditPersistMenuFields(): void
    {
        $root = $this->createSystemMenuFixture([
            'title' => '上级菜单',
            'url' => '#',
        ]);

        $add = $this->callFormController('add', [
            'pid' => intval($root->getAttr('id')),
            'title' => '新增菜单',
            'icon' => 'layui-icon layui-icon-star',
            'node' => 'system/base/index',
            'url' => '/system/base.html?tab=menu',
            'params' => 'from=builder',
            'target' => '_blank',
            'sort' => 30,
            'status' => 1,
        ]);

        $created = SystemMenu::mk()->where(['title' => '新增菜单'])->findOrEmpty();

        $this->assertSame(1, intval($add['code'] ?? 0));
        $this->assertSame('数据保存成功！', $add['info'] ?? '');
        $this->assertTrue($created->isExists());
        $this->assertSame('_blank', $created->getData('target'));
        $this->assertSame('system/base/index', $created->getData('url'));
        $this->assertSame('tab=menu&from=builder', $created->getData('params'));

        $edit = $this->callFormController('edit', [
            'id' => intval($created->getAttr('id')),
            'pid' => intval($root->getAttr('id')),
            'title' => '更新菜单',
            'icon' => 'layui-icon layui-icon-template',
            'node' => 'system/file/index',
            'url' => 'system/file/index',
            'params' => 'type=image',
            'target' => '_self',
            'sort' => 40,
            'status' => 0,
        ]);

        $updated = SystemMenu::mk()->findOrEmpty(intval($created->getAttr('id')));

        $this->assertSame(1, intval($edit['code'] ?? 0));
        $this->assertSame('数据保存成功！', $edit['info'] ?? '');
        $this->assertSame('更新菜单', $updated->getData('title'));
        $this->assertSame('system/file/index', $updated->getData('url'));
        $this->assertSame('type=image', $updated->getData('params'));
        $this->assertSame(0, intval($updated->getData('status')));
    }

    public function testAddRejectsLeafParentAndInvalidPermissionNode(): void
    {
        $leaf = $this->createSystemMenuFixture([
            'title' => '叶子菜单',
            'url' => 'system/base/index',
        ]);
        $branch = $this->createSystemMenuFixture([
            'title' => '分组菜单',
            'url' => '#',
        ]);

        $invalidParent = $this->callFormController('add', [
            'pid' => intval($leaf->getAttr('id')),
            'title' => '不能挂载',
            'url' => 'system/base/index',
            'target' => '_self',
            'status' => 1,
        ]);
        $invalidNode = $this->callFormController('add', [
            'pid' => intval($branch->getAttr('id')),
            'title' => '节点异常',
            'url' => 'system/base/index',
            'node' => 'system/not-found/index',
            'target' => '_self',
            'status' => 1,
        ]);

        $this->assertNotSame(1, intval($invalidParent['code'] ?? 0));
        $this->assertSame('当前父级菜单不能继续挂载子节点！', $invalidParent['info'] ?? '');
        $this->assertNotSame(1, intval($invalidNode['code'] ?? 0));
        $this->assertSame('权限节点不存在！', $invalidNode['info'] ?? '');
    }

    public function testStateAndRemoveUpdateMenuLifecycle(): void
    {
        $menu = $this->createSystemMenuFixture([
            'title' => '生命周期菜单',
            'status' => 1,
            'url' => 'system/base/index',
        ]);

        $state = $this->callActionController('state', [
            'id' => intval($menu->getAttr('id')),
            'status' => 0,
        ]);
        $afterState = SystemMenu::mk()->findOrEmpty(intval($menu->getAttr('id')));

        $remove = $this->callActionController('remove', [
            'id' => intval($menu->getAttr('id')),
        ]);

        $this->assertSame(1, intval($state['code'] ?? 0));
        $this->assertSame('数据保存成功！', $state['info'] ?? '');
        $this->assertSame(0, intval($afterState->getData('status')));
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('数据删除成功！', $remove['info'] ?? '');
        $this->assertFalse(SystemMenu::mk()->where(['id' => $menu->getAttr('id')])->findOrEmpty()->isExists());
    }

    protected function defineSchema(): void
    {
        $this->createSystemMenuTable();
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('menu')
            ->setAction('index');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->activateApplicationContext($request);

        try {
            $controller = new MenuController($this->app);
            $controller->index();
            self::fail('Expected MenuController::index to throw HttpResponseException.');
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
            ->setController('menu')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->activateApplicationContext($request);

        try {
            $controller = new MenuController($this->app);
            $controller->{$action}();
            self::fail("Expected MenuController::{$action} to throw HttpResponseException.");
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
            ->setController('menu')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $post);
        $this->activateApplicationContext($request);

        try {
            $controller = new MenuController($this->app);
            $controller->{$action}();
            self::fail("Expected MenuController::{$action} to throw HttpResponseException.");
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

    private function switchSystemLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        LangService::load($this->app, $langSet);
    }
}
