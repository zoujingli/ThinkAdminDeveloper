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

use plugin\system\middleware\LoadModuleLangPack;
use plugin\system\middleware\RbacAccess;
use plugin\system\service\LangService;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\middleware\LoadLangPack;
use think\Request;
use think\Response;

/**
 * @internal
 * @coversNothing
 */
class LangPackTest extends SqliteIntegrationTestCase
{
    public function testDefaultLangSetIsZhCn(): void
    {
        $this->assertSame('zh-cn', $this->app->lang->defaultLangSet());
    }

    public function testRbacAccessLoadsZhCnPackFromSystemModule(): void
    {
        $this->createSystemBaseFixture([
            'type' => '简体菜单',
            'code' => '系统配置',
            'name' => '系统配置中心',
        ]);

        $this->app->lang->switchLangSet('zh-cn');
        $this->runRbacAccess();

        $this->assertSame('系统配置中心', $this->app->lang->get('menus_系统配置'));
        $this->assertSame('退出登录', $this->app->lang->get('退出登录'));
    }

    public function testRbacAccessLoadsEnUsPackFromSystemModule(): void
    {
        $this->createSystemBaseFixture([
            'type' => '英文菜单',
            'code' => '系统配置',
            'name' => 'Setup',
        ]);
        $this->createSystemBaseFixture([
            'type' => '英文字典',
            'code' => '禁用访问！',
            'name' => 'Access denied.',
        ]);

        $this->app->lang->switchLangSet('en-us');
        $this->runRbacAccess();

        $this->assertSame('Setup', $this->app->lang->get('menus_系统配置'));
        $this->assertSame('Logout', $this->app->lang->get('退出登录'));
        $this->assertSame('Access denied.', $this->app->lang->get('禁用访问！'));
    }

    public function testApiHeaderCanSwitchToEnUsBeforeModulePackLoads(): void
    {
        $this->createSystemBaseFixture([
            'type' => '英文菜单',
            'code' => '系统配置',
            'name' => 'Setup',
        ]);

        $request = (new Request())
            ->setMethod('GET')
            ->withHeader(['think-lang' => 'en-us'])
            ->setController('config')
            ->setAction('index');
        $this->app->instance('request', $request);

        (new LoadLangPack($this->app, $this->app->lang, $this->app->config))->handle($request, fn(Request $request): Response => Response::create('ok'));
        (new LoadModuleLangPack($this->app))->handle($request, fn(Request $request): Response => Response::create('ok'));

        $this->assertSame('en-us', $this->app->lang->getLangSet());
        $this->assertSame('Setup', $this->app->lang->get('menus_系统配置'));
        $this->assertSame('Logout', $this->app->lang->get('退出登录'));
    }

    public function testEnUsPackCoversRecentLoginAndUploadKeys(): void
    {
        $this->app->lang->switchLangSet('en-us');
        LangService::load($this->app, 'en-us');

        $this->assertSame('System Login', $this->app->lang->get('系统登录'));
        $this->assertSame('Incorrect login account or password. Please try again!', $this->app->lang->get('登录账号或密码错误，请重新输入!'));
        $this->assertSame('File record does not exist!', $this->app->lang->get('文件记录不存在！'));
        $this->assertSame('Please use a super-admin account to perform this action.', $this->app->lang->get('请使用超管账号操作！'));
        $this->assertSame('>>> Task is processing <<<', $this->app->lang->get('>>> 任务处理中 <<<'));
        $this->assertSame('Choose Menu Icon', $this->app->lang->get('选择菜单图标'));
        $this->assertSame('Click to refresh service status', $this->app->lang->get('点击刷新服务状态'));
        $this->assertSame('Log Management', $this->app->lang->get('系统日志管理'));
        $this->assertSame('Recommended browser: Chrome or Edge', sprintf($this->app->lang->get('推荐使用 %s 或 %s 浏览器访问'), 'Chrome', 'Edge'));
    }

    public function testZhTwPackCoversRecentLoginAndUploadKeys(): void
    {
        $this->app->lang->switchLangSet('zh-tw');
        LangService::load($this->app, 'zh-tw');

        $this->assertSame('系統登錄', $this->app->lang->get('系统登录'));
        $this->assertSame('登錄賬號或密碼錯誤，請重新輸入!', $this->app->lang->get('登录账号或密码错误，请重新输入!'));
        $this->assertSame('文件記錄不存在！', $this->app->lang->get('文件记录不存在！'));
        $this->assertSame('請先完成滑塊驗證!', $this->app->lang->get('请先完成滑块验证!'));
        $this->assertSame('>>> 任務處理中 <<<', $this->app->lang->get('>>> 任务处理中 <<<'));
        $this->assertSame('選擇菜單圖標', $this->app->lang->get('选择菜单图标'));
        $this->assertSame('點擊刷新服務狀態', $this->app->lang->get('点击刷新服务状态'));
        $this->assertSame('系統日誌管理', $this->app->lang->get('系统日志管理'));
        $this->assertSame('建議使用 Chrome 或 Edge 瀏覽器訪問', sprintf($this->app->lang->get('推荐使用 %s 或 %s 浏览器访问'), 'Chrome', 'Edge'));
    }

    protected function defineSchema(): void
    {
        $this->createSystemBaseTable();
    }

    protected function afterSchemaCreated(): void
    {
        $this->app->initialize();
    }

    private function runRbacAccess(): void
    {
        RequestContext::instance()->setAuth([
            'id' => 10000,
            'username' => 'admin',
        ], '', true);

        $request = (new Request())
            ->setMethod('GET')
            ->setController('config')
            ->setAction('index');
        $this->app->instance('request', $request);

        $middleware = new RbacAccess($this->app);
        $middleware->handle($request, static function (Request $request): Response {
            return Response::create('ok');
        });
    }
}
