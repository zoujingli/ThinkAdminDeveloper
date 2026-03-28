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

use plugin\system\controller\User as UserController;
use plugin\system\model\SystemUser;
use plugin\system\service\AuthService;
use plugin\system\service\LangService;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class UserControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexFiltersActiveUsersByIdentityKeywordAndDateRange(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);
        $this->createSystemUserFixture([
            'base_code' => 'staff',
            'username' => 'manager-hit',
            'nickname' => '命中用户',
            'contact_phone' => '13800138000',
            'login_at' => '2026-03-10 10:00:00',
            'create_time' => '2026-03-10 08:00:00',
            'status' => 1,
        ]);
        $this->createSystemUserFixture([
            'base_code' => 'staff',
            'username' => 'manager-old',
            'nickname' => '跨日用户',
            'contact_phone' => '13800138001',
            'login_at' => '2026-03-09 10:00:00',
            'create_time' => '2026-03-09 08:00:00',
            'status' => 1,
        ]);
        $this->createSystemUserFixture([
            'base_code' => 'staff',
            'username' => 'manager-history',
            'nickname' => '禁用用户',
            'contact_phone' => '13800138002',
            'login_at' => '2026-03-10 11:00:00',
            'create_time' => '2026-03-10 09:00:00',
            'status' => 0,
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'index',
            'base_code' => 'staff',
            'username' => 'manager-hit',
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
        $this->assertSame('manager-hit', $result['data']['list'][0]['username'] ?? '');
        $this->assertSame('员工身份', $result['data']['list'][0]['userinfo']['name'] ?? '');
    }

    public function testIndexPaginatesHistoryUsers(): void
    {
        for ($i = 1; $i <= 21; ++$i) {
            $this->createSystemUserFixture([
                'username' => sprintf('history-user-%02d', $i),
                'nickname' => sprintf('历史用户-%02d', $i),
                'status' => 0,
                'create_time' => sprintf('2026-03-10 08:%02d:00', $i % 60),
            ]);
        }

        $result = $this->callIndexController([
            'output' => 'json',
            'type' => 'history',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 10,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(21, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(3, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(10, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(10, $result['data']['list'] ?? []);
        $this->assertSame('history-user-11', $result['data']['list'][0]['username'] ?? '');
    }

    public function testIndexGetRendersPageBuilderMarkup(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);

        $html = $this->callActionHtml('index', ['type' => 'index']);

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('id="UserTable"', $html);
        $this->assertStringContainsString('StatusSwitchUserTable', $html);
        $this->assertStringContainsString('data-modal=', $html);
        $this->assertStringContainsString('添加用户', $html);
    }

    public function testAddGetRendersBuilderFormMarkup(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);
        $this->createSystemAuthFixture([
            'title' => '系统用户管理',
            'code' => 'system-user-manage',
            'remark' => '用户权限',
            'status' => 1,
        ]);

        $html = $this->callActionHtml('add');

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('账号信息', $html);
        $this->assertStringContainsString('身份与权限', $html);
        $this->assertStringContainsString('联系资料', $html);
        $this->assertStringContainsString('管理设置', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*name="headimg"[^>]*type="text"/', $html);
        $this->assertStringContainsString('data-field="headimg"', $html);
        $this->assertStringContainsString('data-file="image"', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('name="nickname"', $html);
        $this->assertStringContainsString('登录密码', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="repassword"', $html);
        $this->assertStringContainsString('UserBasePluginFilter', $html);
        $this->assertStringContainsString('name="base_code"', $html);
        $this->assertStringContainsString('name="auth_ids[]"', $html);
        $this->assertStringContainsString('data-table-id="UserTable"', $html);
    }

    public function testAddGetRendersEnglishBuilderFormMarkupWhenLangSetIsEnUs(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);
        $this->createSystemAuthFixture([
            'title' => '系统用户管理',
            'code' => 'system-user-manage',
            'remark' => '用户权限',
            'status' => 1,
        ]);
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('add');

        $this->assertStringContainsString('Account Profile', $html);
        $this->assertStringContainsString('Identity &amp; Permissions', $html);
        $this->assertStringContainsString('Contact Details', $html);
        $this->assertStringContainsString('Management Settings', $html);
        $this->assertStringContainsString('Login Account', $html);
        $this->assertStringContainsString('Please enter login account', $html);
        $this->assertStringContainsString('Account Status', $html);
        $this->assertStringNotContainsString('账号信息', $html);
    }

    public function testEditGetRendersSuperUserPermissionToggleWithoutTemplateConditionals(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);
        $this->createSystemAuthFixture([
            'title' => '用户管理',
            'node' => 'system/user/index',
            'status' => 1,
        ]);
        $user = $this->createSystemUserFixture([
            'username' => AuthService::getSuperName(),
            'nickname' => '系统超管',
            'auth_ids' => ',1,',
            'status' => 1,
        ]);

        $html = $this->callActionHtml('edit', ['id' => intval($user->getAttr('id'))]);

        $this->assertStringContainsString('user-super-notice', $html);
        $this->assertStringContainsString('user-auth-wrap', $html);
        $this->assertStringContainsString('syncSuperUser', $html);
        $this->assertStringContainsString('value="******"', $html);
        $this->assertStringNotContainsString('{if isset($vo.username)', $html);
        $this->assertStringNotContainsString('{/if}', $html);
    }

    public function testEditGetRendersEnglishSuperUserNoticeWhenLangSetIsEnUs(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);
        $this->createSystemAuthFixture([
            'title' => '用户管理',
            'node' => 'system/user/index',
            'status' => 1,
        ]);
        $user = $this->createSystemUserFixture([
            'username' => AuthService::getSuperName(),
            'nickname' => '系统超管',
            'auth_ids' => ',1,',
            'status' => 1,
        ]);
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('edit', ['id' => intval($user->getAttr('id'))]);

        $this->assertStringContainsString('Super users have all access permissions and do not need to configure permissions.', $html);
        $this->assertStringNotContainsString('超级用户拥有所有访问权限，不需要配置权限。', $html);
    }

    public function testAddGetReturnsBuilderJsonWhenAcceptRequestsApi(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);

        $result = $this->callActionJson('add', [], [
            'Authorization' => 'Bearer builder-api-token',
            'Accept' => 'application/json',
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('获取表单成功！', $result['info'] ?? '');
        $this->assertSame('builder', $result['data']['driver'] ?? '');
        $this->assertSame('form', $result['data']['scene'] ?? '');
        $this->assertSame('api', $result['data']['mode'] ?? '');
        $this->assertSame('Authorization', $result['data']['token']['header'] ?? '');
        $this->assertSame('form', $result['data']['builder']['type'] ?? '');
        $fieldNames = array_column($result['data']['builder']['schema']['fields'] ?? [], 'name');
        $this->assertContains('username', $fieldNames);
        $this->assertContains('password', $fieldNames);
        $this->assertContains('repassword', $fieldNames);
        $this->assertContains('remark', $fieldNames);
        $this->assertContains('status', $fieldNames);
    }

    public function testIndexGetReturnsBuilderJsonWhenPresentationModeIsApi(): void
    {
        $this->setPresentationMode('api');

        $result = $this->callActionJson('index', ['type' => 'index']);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('获取页面成功！', $result['info'] ?? '');
        $this->assertSame('builder', $result['data']['driver'] ?? '');
        $this->assertSame('page', $result['data']['scene'] ?? '');
        $this->assertSame('api', $result['data']['mode'] ?? '');
        $this->assertSame('page', $result['data']['builder']['type'] ?? '');
        $this->assertSame('UserTable', $this->findFirstTableId($result['data']['builder']['schema']['content'] ?? []));
        $this->assertSame('系统用户管理', $result['data']['context']['title'] ?? '');
    }

    public function testAddAndEditPersistUserProfileAndAuthorization(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);

        $add = $this->callFormController('add', [
            'base_code' => 'staff',
            'username' => 'operator-new',
            'nickname' => '新增运维',
            'password' => 'Operator@123',
            'repassword' => 'Operator@123',
            'auth_ids' => ['2', '3'],
            'contact_phone' => '13800138111',
            'contact_mail' => 'operator@example.com',
            'remark' => '新增说明',
            'sort' => 12,
            'status' => 1,
        ]);

        $created = SystemUser::mk()->where(['username' => 'operator-new'])->findOrEmpty();

        $this->assertSame(1, intval($add['code'] ?? 0));
        $this->assertSame('数据保存成功！', $add['info'] ?? '');
        $this->assertTrue($created->isExists());
        $this->assertTrue($this->verifySystemPassword('Operator@123', strval($created->getData('password'))));
        $this->assertSame(',2,3,', $created->getData('auth_ids'));

        $edit = $this->callFormController('edit', [
            'id' => intval($created->getAttr('id')),
            'username' => 'operator-changed',
            'base_code' => 'staff',
            'nickname' => '更新运维',
            'password' => password_mask(),
            'repassword' => password_mask(),
            'auth_ids' => ['3'],
            'contact_phone' => '13800138222',
            'contact_mail' => 'updated@example.com',
            'remark' => '更新说明',
            'sort' => 20,
            'status' => 0,
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($created->getAttr('id')));

        $this->assertSame(1, intval($edit['code'] ?? 0));
        $this->assertSame('数据保存成功！', $edit['info'] ?? '');
        $this->assertSame('operator-new', $updated->getData('username'));
        $this->assertSame('更新运维', $updated->getData('nickname'));
        $this->assertSame(',3,', $updated->getData('auth_ids'));
        $this->assertSame(0, intval($updated->getData('status')));
        $this->assertTrue($this->verifySystemPassword('Operator@123', strval($updated->getData('password'))));
    }

    public function testEditCanChangePasswordThroughMainForm(): void
    {
        $this->createSystemBaseFixture([
            'type' => '身份权限',
            'code' => 'staff',
            'name' => '员工身份',
            'content' => '员工说明',
            'status' => 1,
        ]);
        $user = $this->createSystemUserFixture([
            'username' => 'operator-pass',
            'password' => $this->hashSystemPassword('Old@1234'),
            'auth_ids' => ',2,3,',
            'base_code' => 'staff',
            'status' => 1,
        ]);

        $edit = $this->callFormController('edit', [
            'id' => intval($user->getAttr('id')),
            'username' => 'operator-pass',
            'base_code' => 'staff',
            'nickname' => '密码更新',
            'password' => 'New@1234',
            'repassword' => 'New@1234',
            'auth_ids' => ['2', '3'],
            'status' => 1,
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));

        $this->assertSame(1, intval($edit['code'] ?? 0));
        $this->assertSame('数据保存成功！', $edit['info'] ?? '');
        $this->assertTrue($this->verifySystemPassword('New@1234', strval($updated->getData('password'))));
    }

    public function testPassUpdatesUserPasswordHash(): void
    {
        $user = $this->createSystemUserFixture([
            'username' => 'pass-user',
            'password' => $this->hashSystemPassword('old-password'),
        ]);

        $result = $this->callActionController('pass', [
            'id' => intval($user->getAttr('id')),
            'password' => 'new-password',
            'repassword' => 'new-password',
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('密码修改成功，请使用新密码登录！', $result['info'] ?? '');
        $this->assertTrue($this->verifySystemPassword('new-password', strval($updated->getData('password'))));
    }

    public function testPassGetRendersFormBuilderMarkup(): void
    {
        $user = $this->createSystemUserFixture([
            'username' => 'pass-view-user',
            'password' => $this->hashSystemPassword('old-password'),
        ]);

        $html = $this->callActionHtml('pass', [
            'id' => intval($user->getAttr('id')),
        ]);

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('账号确认', $html);
        $this->assertStringContainsString('新密码设置', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="repassword"', $html);
        $this->assertStringNotContainsString(strval($user->getData('password')), $html);
        $this->assertStringNotContainsString('value="$2y$', $html);
    }

    public function testStateAndRemoveUpdateUserLifecycle(): void
    {
        $user = $this->createSystemUserFixture([
            'username' => 'lifecycle-user',
            'status' => 1,
        ]);

        $state = $this->callActionController('state', [
            'id' => intval($user->getAttr('id')),
            'status' => 0,
        ]);
        $afterState = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));

        $remove = $this->callActionController('remove', [
            'id' => intval($user->getAttr('id')),
        ]);
        $afterRemove = SystemUser::mk()->withTrashed()->findOrEmpty(intval($user->getAttr('id')));

        $this->assertSame(1, intval($state['code'] ?? 0));
        $this->assertSame('数据保存成功！', $state['info'] ?? '');
        $this->assertSame(0, intval($afterState->getData('status')));
        $this->assertSame(1, intval($remove['code'] ?? 0));
        $this->assertSame('数据删除成功！', $remove['info'] ?? '');
        $this->assertNotEmpty($afterRemove->getData('delete_time'));
    }

    public function testRemoveRejectsSuperAdminAccount(): void
    {
        $super = $this->createSystemUserFixture([
            'id' => 10000,
            'username' => 'admin',
            'nickname' => '超级管理员',
        ]);

        $result = $this->callActionController('remove', [
            'id' => 10000,
        ]);

        $record = SystemUser::mk()->findOrEmpty(intval($super->getAttr('id')));

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('系统超级账号禁止删除！', $result['info'] ?? '');
        $this->assertTrue($record->isExists());
    }

    /**
     * @param array<int, array<string, mixed>> $nodes
     */
    private function findFirstTableId(array $nodes): string
    {
        foreach ($nodes as $node) {
            if (($node['type'] ?? '') === 'table' && !empty($node['id'])) {
                return strval($node['id']);
            }
            $children = is_array($node['children'] ?? null) ? $node['children'] : [];
            if (($id = $this->findFirstTableId($children)) !== '') {
                return $id;
            }
        }

        return '';
    }

    protected function defineSchema(): void
    {
        $this->createSystemBaseTable();
        $this->createSystemAuthTable();
        $this->createSystemAuthNodeTable();
        $this->createSystemUserTable();
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('user')
            ->setAction('index');

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new UserController($this->app);
            $controller->index();
            self::fail('Expected UserController::index to throw HttpResponseException.');
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
            ->setController('user')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new UserController($this->app);
            $controller->{$action}();
            self::fail("Expected UserController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callActionJson(string $action, array $query = [], array $headers = []): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->withHeader($headers)
            ->setController('user')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new UserController($this->app);
            $controller->{$action}();
            self::fail("Expected UserController::{$action} to throw HttpResponseException.");
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
            ->setController('user')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new UserController($this->app);
            $controller->{$action}();
            self::fail("Expected UserController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(): void
    {
        $this->context->setUser([
            'id' => 10000,
            'username' => 'admin',
        ], true, true);
        RequestContext::instance()->setAuth([
            'id' => 10000,
            'username' => 'admin',
        ], '', true);
    }

    private function setPresentationMode(string $mode): void
    {
        $config = $this->app->config->get('app', []);
        $config['presentation'] = array_merge($config['presentation'] ?? [], ['mode' => $mode]);
        $this->app->config->set($config, 'app');
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
