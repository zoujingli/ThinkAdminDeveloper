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

use plugin\system\controller\Index as IndexController;
use plugin\system\model\SystemData;
use plugin\system\model\SystemOplog;
use plugin\system\model\SystemUser;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class IndexControllerTest extends SqliteIntegrationTestCase
{
    public function testThemePersistsCurrentUserThemeIntoSystemData(): void
    {
        $this->bindAdminUser(9101, 'tester', md5('changed-password'));

        $result = $this->callActionController('theme', [
            'site_theme' => 'black-2',
        ]);

        $row = SystemData::mk()->where(['name' => 'UserData_9101'])->findOrEmpty();
        $payload = json_decode(strval($row->getData('value')), true)[0] ?? [];

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('主题配置保存成功！', $result['info'] ?? '');
        $this->assertTrue($row->isExists());
        $this->assertSame('black-2', $payload['site_theme'] ?? '');
    }

    public function testInfoUpdatesOwnProfileButKeepsUsernameAndAuthorize(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => md5('changed-password'),
            'nickname' => '原昵称',
            'authorize' => ',1,2,',
            'contact_phone' => '13800130000',
        ]);

        $this->bindAdminUser(9101, 'tester', md5('changed-password'));

        $result = $this->callActionController('info', [
            'id' => 9101,
            'username' => 'hacker',
            'authorize' => ['9'],
            'nickname' => '新昵称',
            'contact_phone' => '13800139999',
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('用户资料修改成功！', $result['info'] ?? '');
        $this->assertSame('javascript:location.reload()', $result['data'] ?? '');
        $this->assertSame('tester', $updated->getData('username'));
        $this->assertSame(',1,2,', $updated->getData('authorize'));
        $this->assertSame('新昵称', $updated->getData('nickname'));
        $this->assertSame('13800139999', $updated->getData('contact_phone'));
    }

    public function testInfoRejectsEditingOtherUserProfile(): void
    {
        $this->bindAdminUser(9101, 'tester', md5('changed-password'));

        $result = $this->callActionController('info', [
            'id' => 9102,
            'nickname' => '越权修改',
        ]);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('只能修改自己的资料！', $result['info'] ?? '');
    }

    public function testPassUpdatesOwnPasswordAndWritesOplog(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => md5('old-password'),
        ]);

        $this->bindAdminUser(9101, 'tester', md5('old-password'));

        $result = $this->callActionController('pass', [
            'id' => 9101,
            'oldpassword' => 'old-password',
            'password' => 'new-password',
            'repassword' => 'new-password',
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));
        $oplog = SystemOplog::mk()->order('id desc')->findOrEmpty();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('密码修改成功，下次请使用新密码登录！', $result['info'] ?? '');
        $this->assertSame(md5('new-password'), $updated->getData('password'));
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统用户管理', $oplog->getData('action'));
        $this->assertSame('修改用户[9101]密码成功', $oplog->getData('content'));
        $this->assertSame('tester', $oplog->getData('username'));
    }

    public function testPassRejectsChangingOtherUserPassword(): void
    {
        $this->bindAdminUser(9101, 'tester', md5('changed-password'));

        $result = $this->callActionController('pass', [
            'id' => 9102,
            'oldpassword' => 'old-password',
            'password' => 'new-password',
            'repassword' => 'new-password',
        ]);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('禁止修改他人密码！', $result['info'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createSystemUserTable();
        $this->createSystemDataTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
    }

    private function callActionController(string $action, array $payload): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod('POST')
            ->setController('index')
            ->setAction($action);

        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new IndexController($this->app);
            $controller->{$action}();
            self::fail("Expected IndexController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(int $id, string $username, string $password): void
    {
        RequestContext::instance()->setAuth([
            'id' => $id,
            'username' => $username,
            'password' => $password,
        ], '', true);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
