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

use plugin\system\controller\Index as IndexController;
use plugin\system\model\SystemData;
use plugin\system\model\SystemOplog;
use plugin\system\model\SystemUser;
use plugin\system\service\AuthService;
use plugin\system\service\LangService;
use plugin\system\service\SystemContext as PluginSystemContext;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\runtime\RequestTokenService;
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
    /**
     * @var array<string, string>
     */
    private array $requestCookies = [];

    public function testIndexRendersShellWithNormalizedBrandAndUserContext(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.site',
            'value' => [
                'website_name' => '测试站点',
                'application_name' => 'Console Hub',
                'application_version' => 'v9.9.9',
                'browser_icon' => 'https://static.example.com/logo.png',
            ],
        ]);
        $root = $this->createSystemMenuFixture([
            'title' => '系统管理',
            'url' => '#',
            'icon' => 'layui-icon layui-icon-set',
        ]);
        $this->createSystemMenuFixture([
            'pid' => intval($root->getAttr('id')),
            'title' => '用户管理',
            'url' => 'system/user/index',
            'icon' => 'layui-icon layui-icon-user',
        ]);

        $this->bindAdminUser(9101, AuthService::getSuperName(), $this->hashSystemPassword('changed-password'), [
            'nickname' => '控制台用户',
            'headimg' => 'https://static.example.com/headimg.png',
        ]);

        $response = $this->callPageResponse('index', [], 'GET');
        $html = $response->getContent();

        $this->assertSame(200, $response->getCode());
        $this->assertStringContainsString('Console Hub', $html);
        $this->assertStringContainsString('v9.9.9', $html);
        $this->assertStringContainsString('https://static.example.com/logo.png', $html);
        $this->assertStringContainsString('控制台用户', $html);
        $this->assertStringContainsString('用户管理', $html);
        $this->assertStringContainsString('/system/index/info.html?id=9101', $html);
        $this->assertStringContainsString('/system/index/pass.html?id=9101', $html);
    }

    public function testThemeGetRendersUserThemeFormBuilderMarkup(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));

        $html = $this->callActionHtml('theme');

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('data-theme-card="default"', $html);
        $this->assertStringContainsString('name="site_theme"', $html);
        $this->assertStringContainsString('保存配置', $html);
        $this->assertStringNotContainsString('ThemeCatalogJson', $html);
    }

    public function testThemeConfigGetRendersPickerFormBuilderMarkup(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));

        $html = $this->callActionHtml('theme', ['scene' => 'config', 'picker' => '__themeConfigPicker']);

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('data-theme-card="default"', $html);
        $this->assertStringContainsString('data-theme-confirm', $html);
        $this->assertStringContainsString('data-theme-cancel', $html);
        $this->assertStringNotContainsString('ThemeCatalogJson', $html);
    }

    public function testThemeConfigGetRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('theme', ['scene' => 'config', 'picker' => '__themeConfigPicker']);

        $this->assertStringContainsString('Choose Backend Default Theme', $html);
        $this->assertStringContainsString('Backend Theme Palette', $html);
        $this->assertStringContainsString('Confirm Selection', $html);
        $this->assertStringContainsString('Cancel Selection', $html);
        $this->assertStringNotContainsString('后台配色方案', $html);
    }

    public function testThemePersistsCurrentUserThemeIntoSystemData(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));

        $result = $this->callActionController('theme', [
            'site_theme' => 'black-2',
        ]);

        $row = SystemData::mk()->where(['name' => 'UserData_9101'])->findOrEmpty();
        $payload = (array)$row->getAttr('value');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('主题配置保存成功！', $result['info'] ?? '');
        $this->assertTrue($row->isExists());
        $this->assertSame('black-2', $payload['site_theme'] ?? '');
    }

    public function testThemeRejectsUnknownThemeValue(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));

        $result = $this->callActionController('theme', [
            'site_theme' => 'not-exists',
        ]);

        $row = SystemData::mk()->where(['name' => 'UserData_9101'])->findOrEmpty();

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('主题方案不存在！', $result['info'] ?? '');
        $this->assertFalse($row->isExists());
    }

    public function testInfoUpdatesOwnProfileButKeepsUsernameAndAuthIds(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('changed-password'),
            'nickname' => '原昵称',
            'auth_ids' => ',1,2,',
            'contact_phone' => '13800130000',
        ]);

        $this->bindAdminUser(9101, 'tester', strval($user->getData('password')));

        $result = $this->callActionController('info', [
            'id' => 9101,
            'username' => 'hacker',
            'auth_ids' => ['9'],
            'nickname' => '新昵称',
            'contact_phone' => '13800139999',
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('用户资料修改成功！', $result['info'] ?? '');
        $this->assertSame('javascript:location.reload()', $result['data'] ?? '');
        $this->assertSame('tester', $updated->getData('username'));
        $this->assertSame(',1,2,', $updated->getData('auth_ids'));
        $this->assertSame('新昵称', $updated->getData('nickname'));
        $this->assertSame('13800139999', $updated->getData('contact_phone'));
    }

    public function testInfoGetRendersProfileFormBuilderMarkup(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('changed-password'),
            'nickname' => '原昵称',
            'auth_ids' => ',1,2,',
        ]);

        $this->bindAdminUser(9101, 'tester', strval($user->getData('password')));

        $html = $this->callActionHtml('info', ['id' => 9101]);

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('账号信息', $html);
        $this->assertStringContainsString('联系资料', $html);
        $this->assertStringNotContainsString('身份与权限', $html);
        $this->assertStringNotContainsString('管理设置', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('name="nickname"', $html);
        $this->assertStringNotContainsString('name="auth_ids[]"', $html);
    }

    public function testInfoRejectsEditingOtherUserProfile(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));

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
            'password' => $this->hashSystemPassword('old-password'),
        ]);

        $this->bindAdminUser(9101, 'tester', strval($user->getData('password')));

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
        $this->assertTrue($this->verifySystemPassword('new-password', strval($updated->getData('password'))));
        $this->assertTrue($oplog->isExists());
        $this->assertSame('系统用户管理', $oplog->getData('action'));
        $this->assertSame('修改用户[9101]密码成功', $oplog->getData('content'));
        $this->assertSame('tester', $oplog->getData('username'));
    }

    public function testPassRejectsWrongOldPassword(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('old-password'),
        ]);

        $this->bindAdminUser(9101, 'tester', strval($user->getData('password')));

        $result = $this->callActionController('pass', [
            'id' => 9101,
            'oldpassword' => 'wrong-password',
            'password' => 'new-password',
            'repassword' => 'new-password',
        ]);

        $updated = SystemUser::mk()->findOrEmpty(intval($user->getAttr('id')));

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('旧密码验证失败，请重新输入！', $result['info'] ?? '');
        $this->assertTrue($this->verifySystemPassword('old-password', strval($updated->getData('password'))));
    }

    public function testPassGetRendersFormBuilderMarkup(): void
    {
        $user = $this->createSystemUserFixture([
            'id' => 9101,
            'username' => 'tester',
            'password' => $this->hashSystemPassword('old-password'),
        ]);

        $this->bindAdminUser(9101, 'tester', strval($user->getData('password')));

        $html = $this->callActionHtml('pass', ['id' => 9101]);

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('账号确认', $html);
        $this->assertStringContainsString('新密码设置', $html);
        $this->assertStringContainsString('name="oldpassword"', $html);
        $this->assertStringContainsString('name="password"', $html);
        $this->assertStringContainsString('name="repassword"', $html);
        $this->assertStringNotContainsString('value="******"', $html);
        $this->assertStringNotContainsString(strval($user->getData('password')), $html);
        $this->assertStringNotContainsString('value="$2y$', $html);
    }

    public function testPassRejectsChangingOtherUserPassword(): void
    {
        $this->bindAdminUser(9101, 'tester', $this->hashSystemPassword('changed-password'));

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
        $this->createSystemMenuTable();
        $this->createSystemUserTable();
        $this->createSystemDataTable();
        $this->createSystemOplogTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-system/src/view' . DIRECTORY_SEPARATOR,
        ]);
    }

    private function callActionController(string $action, array $payload): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->withCookie($this->requestCookies)
            ->setMethod('POST')
            ->setController('index')
            ->setAction($action);

        $this->setRequestPayload($request, $payload);
        RequestContext::instance()->clearRequestTokens();
        $this->app->instance('request', $request);

        try {
            $controller = new IndexController($this->app);
            $controller->{$action}();
            self::fail("Expected IndexController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callActionHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->withCookie($this->requestCookies)
            ->setMethod('GET')
            ->setController('index')
            ->setAction($action);

        $this->setRequestPayload($request, $query);
        RequestContext::instance()->clearRequestTokens();
        $this->app->instance('request', $request);

        try {
            $controller = new IndexController($this->app);
            $controller->{$action}();
            self::fail("Expected IndexController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callPageResponse(string $action, array $payload = [], string $method = 'GET')
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->withCookie($this->requestCookies)
            ->setMethod($method)
            ->setController('index')
            ->setAction($action);

        $this->setRequestPayload($request, $payload);
        RequestContext::instance()->clearRequestTokens();
        $this->app->instance('request', $request);

        try {
            $controller = new IndexController($this->app);
            $controller->{$action}();
            self::fail("Expected IndexController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse();
        }
    }

    private function bindAdminUser(int $id, string $username, string $password, array $extra = []): void
    {
        $payload = array_merge([
            'id' => $id,
            'username' => $username,
            'password' => $password,
            'status' => 1,
        ], $extra);

        $user = SystemUser::mk()->findOrEmpty($id);
        if ($user->isEmpty()) {
            $this->createSystemUserFixture($payload);
        } else {
            $user->save(array_merge([
                'username' => $username,
                'password' => $password,
                'status' => 1,
            ], $extra));
        }

        $auth = array_merge(SystemUser::mk()->findOrEmpty($id)->toArray(), $payload);
        RequestContext::instance()->setAuth($auth, '', true);

        $token = AuthService::buildToken($auth);
        $this->requestCookies = $token === '' ? [] : [
            AuthService::getTokenCookie() => RequestTokenService::encodeCookieToken($token),
        ];
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
