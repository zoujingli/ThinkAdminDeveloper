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

use plugin\account\controller\Message as MessageController;
use plugin\account\model\PluginAccountMsms;
use plugin\account\service\Message as AccountMessage;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class AccountMessageControllerTest extends SqliteIntegrationTestCase
{
    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
        $this->executeStatements([
            <<<'SQL'
CREATE TABLE plugin_account_msms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    unid INTEGER DEFAULT 0,
    usid INTEGER DEFAULT 0,
    type TEXT DEFAULT '',
    scene TEXT DEFAULT '',
    smsid TEXT DEFAULT '',
    phone TEXT DEFAULT '',
    result TEXT DEFAULT '',
    params TEXT DEFAULT '',
    status INTEGER DEFAULT 0,
    create_time TEXT DEFAULT NULL,
    update_time TEXT DEFAULT NULL
)
SQL,
        ]);
    }

    protected function afterSchemaCreated(): void
    {
        $this->app->setAppPath(TEST_PROJECT_ROOT . '/plugin/think-plugs-account/src/');
        $this->configureView([
            'view_path' => TEST_PROJECT_ROOT . '/plugin/think-plugs-account/src/view' . DIRECTORY_SEPARATOR,
        ]);
    }

    public function testIndexJsonTranslatesSceneNameWhenLangSetIsEnUs(): void
    {
        $this->switchAccountLang('en-us');
        $this->createMessageFixture([
            'scene' => AccountMessage::tLogin,
            'status' => 1,
            'phone' => '13800000001',
            'smsid' => 'SMS-EN-001',
        ]);

        $result = $this->callJson('index', [
            'output' => 'json',
            'scene' => AccountMessage::tLogin,
            '_field_' => 'id',
            '_order_' => 'desc',
            'page' => 1,
            'limit' => 10,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame('User Login Verification', $result['data']['list'][0]['scene_name'] ?? '');
    }

    public function testIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchAccountLang('en-us');

        $html = $this->callHtml('index');

        $this->assertStringContainsString('SMS Message Management', $html);
        $this->assertStringContainsString('Search Filters', $html);
        $this->assertStringContainsString('Message Code', $html);
        $this->assertStringContainsString('Business Scene', $html);
        $this->assertStringContainsString('Execution Result', $html);
        $this->assertStringContainsString('Send Failed', $html);
        $this->assertStringContainsString('Send Success', $html);
        $this->assertStringContainsString('Failed', $html);
        $this->assertStringContainsString('Success', $html);
        $this->assertStringNotContainsString('手机短信管理', $html);
    }

    public function testConfigRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchAccountLang('en-us');

        $html = $this->callHtml('config');

        $this->assertStringContainsString('Service Region', $html);
        $this->assertStringContainsString('Aliyun Account', $html);
        $this->assertStringContainsString('Aliyun Secret Key', $html);
        $this->assertStringContainsString('SMS Signature', $html);
        $this->assertStringContainsString('User Login Verification', $html);
        $this->assertStringContainsString('North China 1 (Qingdao)', $html);
        $this->assertStringContainsString('Save Configuration', $html);
        $this->assertStringContainsString('Cancel Modification', $html);
        $this->assertStringNotContainsString('短信配置', $html);
    }

    public function testConfigPostReturnsTranslatedSuccessMessageAndPersistsPayload(): void
    {
        $this->switchAccountLang('en-us');

        $result = $this->callJson('config', [
            'alisms_region' => 'cn-qingdao',
            'alisms_keyid' => 'demo-key',
            'alisms_secret' => 'demo-secret',
            'alisms_signtx' => 'DemoSign',
            'scene_login' => 'SMS_LOGIN',
            'scene_forget' => 'SMS_FORGET',
            'scene_register' => 'SMS_REGISTER',
        ], 'POST');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('Configuration updated successfully', $result['info'] ?? '');

        $payload = (array) sysdata('plugin.account.smscfg');

        $this->assertSame('cn-qingdao', $payload['alisms_region'] ?? '');
        $this->assertSame('demo-key', $payload['alisms_keyid'] ?? '');
        $this->assertSame('SMS_LOGIN', $payload['alisms_scenes']['LOGIN'] ?? '');
        $this->assertSame('SMS_FORGET', $payload['alisms_scenes']['FORGET'] ?? '');
        $this->assertSame('SMS_REGISTER', $payload['alisms_scenes']['REGISTER'] ?? '');
    }

    private function callHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('message')
            ->setAction($action);

        $this->setRequestPayload($request, $query);
        RequestContext::clear();
        $this->activateApplicationContext($request);

        try {
            $controller = new MessageController($this->app);
            $controller->{$action}();
            self::fail("Expected MessageController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callJson(string $action, array $data = [], string $method = 'GET'): array
    {
        $request = (new Request())
            ->setMethod($method)
            ->setController('message')
            ->setAction($action);

        if ($method === 'GET') {
            $request = $request->withGet($data);
        } else {
            $request = $request->withGet($data)->withPost($data);
        }

        $this->setRequestPayload($request, $data);
        RequestContext::clear();
        $this->activateApplicationContext($request);

        try {
            $controller = new MessageController($this->app);
            $controller->{$action}();
            self::fail("Expected MessageController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function createMessageFixture(array $overrides = []): PluginAccountMsms
    {
        $message = PluginAccountMsms::mk();
        $message->save(array_merge([
            'unid' => 0,
            'usid' => 0,
            'type' => 'Alisms',
            'scene' => AccountMessage::tLogin,
            'smsid' => 'SMS-' . random_int(1000, 9999),
            'phone' => '13800000000',
            'result' => '{}',
            'params' => '{"code":"123456"}',
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ], $overrides));

        return $message->refresh();
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }

    private function switchAccountLang(string $langSet): void
    {
        $this->app->lang->switchLangSet($langSet);
        $file = TEST_PROJECT_ROOT . "/plugin/think-plugs-account/src/lang/{$langSet}.php";
        if (is_file($file)) {
            $this->app->lang->load($file, $langSet);
        }
    }
}
