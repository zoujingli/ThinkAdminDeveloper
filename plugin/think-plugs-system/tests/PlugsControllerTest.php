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

use plugin\system\controller\api\Plugs as PlugsController;
use plugin\system\model\SystemOplog;
use plugin\system\service\AuthService;
use plugin\system\service\LangService;
use plugin\system\service\SystemContext as PluginSystemContext;
use plugin\worker\model\SystemQueue;
use plugin\worker\service\QueueService;
use think\admin\contract\SystemContextInterface;
use think\admin\runtime\RequestContext;
use think\admin\service\QueueService as QueueRuntime;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\Container;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class PlugsControllerTest extends SqliteIntegrationTestCase
{
    public function testIconRendersHtmlPickerWithoutTemplatePlaceholders(): void
    {
        $html = $this->callHtmlController('icon', ['field' => 'menu_icon']);

        $this->assertStringContainsString('<title>图标选择器</title>', $html);
        $this->assertStringContainsString('data-icon-picker', $html);
        $this->assertStringContainsString('data-field="menu_icon"', $html);
        $this->assertStringContainsString('/static/plugs/layui/css/layui.css', $html);
        $this->assertStringContainsString('/static/plugs/jquery/jquery.min.js', $html);
        $this->assertStringContainsString('top.$(\'[name="\' + field + \'"]\')', $html);
        $this->assertStringNotContainsString('__ROOT__', $html);
        $this->assertStringNotContainsString('{foreach', $html);
    }

    public function testIconRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchSystemLang('en-us');

        $html = $this->callHtmlController('icon', ['field' => 'menu_icon']);

        $this->assertStringContainsString('<title>Icon Picker</title>', $html);
        $this->assertStringContainsString('Select Icon', $html);
        $this->assertStringContainsString('Please enter icon name', $html);
        $this->assertStringNotContainsString('图标选择器', $html);
    }

    public function testScriptBuildsJavascriptConfigWithAbsoluteUrlsWhenUploadTokenIsValid(): void
    {
        $uptoken = AuthService::withUploadToken(321, 'jpg,png');

        $response = $this->callScriptController([
            'uptoken' => $uptoken,
        ]);
        $contentType = strval($response->getHeader('Content-Type'));
        $content = strval($response->getContent());

        $this->assertStringContainsString('application/javascript', $contentType);
        $this->assertStringContainsString(sprintf('window.taDebug = %s;', $this->app->isDebug() ? 'true' : 'false'), $content);
        $this->assertStringContainsString("window.taApiPrefix = 'api';", $content);
        $this->assertStringContainsString("window.taSystem = 'https://admin.example.com/system", $content);
        $this->assertStringContainsString("window.taStorage = 'https://admin.example.com/system/config/storage", $content);
        $this->assertStringContainsString("window.taSystemApi = 'https://admin.example.com/api/system';", $content);
        $this->assertStringContainsString("window.taStorageApi = 'https://admin.example.com/api/system';", $content);
        $this->assertStringContainsString("window.taTokenHeader = 'Authorization';", $content);
        $this->assertStringContainsString("window.taTokenScheme = 'Bearer';", $content);
        $this->assertStringContainsString('window.taTokenExpire = 604800;', $content);
        $this->assertStringContainsString("window.taEditor = 'ckeditor5';", $content);
    }

    public function testScriptUsesConfiguredEditorDriverFromSystemConfig(): void
    {
        $this->createSystemDataFixture([
            'name' => 'system.runtime',
            'value' => ['editor_driver' => 'tinymce'],
        ]);

        $content = strval($this->callScriptController()->getContent());

        $this->assertStringContainsString("window.taEditor = 'tinymce';", $content);
    }

    public function testOptimizeRegistersQueueAndBlocksDuplicatesForSuperAdmin(): void
    {
        $first = $this->callActionController('optimize', [], true);
        $second = $this->callActionController('optimize', [], true);

        $queues = SystemQueue::mk()->where(['title' => '优化数据库所有数据表'])->select();
        $oplogs = SystemOplog::mk()->where(['action' => '系统运维管理'])->order('id asc')->select();

        $this->assertSame(1, intval($first['code'] ?? 0));
        $this->assertSame('创建任务成功！', $first['info'] ?? '');
        $this->assertSame(1, intval($second['code'] ?? 0));
        $this->assertSame('任务已经存在，无需再次创建！', $second['info'] ?? '');
        $this->assertCount(1, $queues);
        $this->assertSame('xadmin:database optimize', $queues[0]->getData('command'));
        $this->assertSame(1, intval($queues[0]->getData('status')));
        $this->assertSame($queues[0]->getData('code'), $first['data'] ?? '');
        $this->assertSame($queues[0]->getData('code'), $second['data'] ?? '');
        $this->assertCount(2, $oplogs);
        $this->assertSame('创建数据库优化任务', $oplogs[0]->getData('content'));
        $this->assertSame('admin', $oplogs[0]->getData('username'));
        $this->assertSame('创建数据库优化任务', $oplogs[1]->getData('content'));
    }

    public function testOptimizeRejectsNonSuperAdmin(): void
    {
        $result = $this->callActionController('optimize', [], false);

        $this->assertSame(0, intval($result['code'] ?? 1));
        $this->assertSame('请使用超管账号操作！', $result['info'] ?? '');
        $this->assertSame(0, SystemQueue::mk()->count());
        $this->assertSame(0, SystemOplog::mk()->count());
    }

    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
        $this->createSystemOplogTable();
        $this->createSystemQueueTable();
    }

    protected function afterSchemaCreated(): void
    {
        $context = new PluginSystemContext();
        Container::getInstance()->instance(SystemContextInterface::class, $context);
        $this->app->instance(SystemContextInterface::class, $context);
        $this->app->bind([
            QueueRuntime::BIND_NAME => QueueService::class,
        ]);
    }

    private function callScriptController(array $query = [])
    {
        $request = (new Request())
            ->withGet($query)
            ->withServer(['HTTPS' => 'on'])
            ->setHost('admin.example.com')
            ->setMethod('GET')
            ->setController('plugs')
            ->setAction('script');

        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        $controller = new PlugsController($this->app);

        return $controller->script();
    }

    private function callHtmlController(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('plugs')
            ->setAction($action);

        $this->bindAdminUser(true);
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new PlugsController($this->app);
            $controller->{$action}();
            self::fail("Expected PlugsController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return strval($exception->getResponse()->getContent());
        }
    }

    private function callActionController(string $action, array $payload = [], bool $super = true): array
    {
        $request = (new Request())
            ->withGet($payload)
            ->withPost($payload)
            ->setMethod('POST')
            ->setController('plugs')
            ->setAction($action);

        $this->bindAdminUser($super);
        $this->setRequestPayload($request, $payload);
        $this->app->instance('request', $request);

        try {
            $controller = new PlugsController($this->app);
            $controller->{$action}();
            self::fail("Expected PlugsController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function bindAdminUser(bool $super): void
    {
        RequestContext::instance()->setAuth([
            'id' => $super ? 10000 : 9101,
            'username' => $super ? 'admin' : 'tester',
            'password' => $this->hashSystemPassword('changed-password'),
        ], '', true);
    }

    private function switchSystemLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        LangService::load($this->app, $langSet);
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
