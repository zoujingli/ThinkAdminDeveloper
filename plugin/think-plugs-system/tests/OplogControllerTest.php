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

use plugin\system\controller\Oplog as OplogController;
use plugin\system\model\SystemOplog;
use plugin\system\service\LangService;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class OplogControllerTest extends SqliteIntegrationTestCase
{
    public function testIndexGetRendersPageBuilderMarkup(): void
    {
        $html = $this->callActionHtml('index');

        $this->assertStringContainsString('page-builder-schema', $html);
        $this->assertStringContainsString('id="OplogTable"', $html);
        $this->assertStringContainsString('class="layui-tab-content"', $html);
        $this->assertStringContainsString('data-line="1"', $html);
        $this->assertStringContainsString('系统日志', $html);
        $this->assertStringContainsString('批量删除', $html);
        $this->assertStringContainsString('data-form-export=', $html);
        $this->assertStringContainsString('name="request_ip"', $html);
    }

    public function testIndexRendersEnglishTextsWhenLangSetIsEnUs(): void
    {
        $this->switchSystemLang('en-us');

        $html = $this->callActionHtml('index');

        $this->assertStringContainsString('Log Management', $html);
        $this->assertStringContainsString('Logs', $html);
        $this->assertStringContainsString('Batch Delete', $html);
        $this->assertStringContainsString('Clear Data', $html);
        $this->assertStringContainsString('Export', $html);
        $this->assertStringContainsString('Operation Log', $html);
        $this->assertStringNotContainsString('导 出', $html);
    }

    public function testIndexFiltersLogsByUsernameActionKeywordAndDateRange(): void
    {
        $this->createSystemOplogFixture([
            'username' => 'alice',
            'action' => '导出日志',
            'content' => '命中日志内容',
            'node' => 'system/oplog/index',
            'geoip' => '8.8.8.8',
            'create_time' => '2026-03-10 08:00:00',
        ]);
        $this->createSystemOplogFixture([
            'username' => 'alice',
            'action' => '导出日志',
            'content' => '跨日日志内容',
            'node' => 'system/oplog/index',
            'geoip' => '8.8.4.4',
            'create_time' => '2026-03-09 08:00:00',
        ]);
        $this->createSystemOplogFixture([
            'username' => 'alice',
            'action' => '清理日志',
            'content' => '行为不匹配',
            'node' => 'system/oplog/clear',
            'geoip' => '1.1.1.1',
            'create_time' => '2026-03-10 09:00:00',
        ]);
        $this->createSystemOplogFixture([
            'username' => 'bob',
            'action' => '导出日志',
            'content' => '用户不匹配',
            'node' => 'system/oplog/index',
            'geoip' => '127.0.0.1',
            'create_time' => '2026-03-10 10:00:00',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'username' => 'alice',
            'action' => '导出日志',
            'content' => '命中日志',
            'request_ip' => '8.8.8.8',
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
        $this->assertSame('alice', $result['data']['list'][0]['username'] ?? '');
        $this->assertSame('导出日志', $result['data']['list'][0]['action'] ?? '');
        $this->assertSame('命中日志内容', $result['data']['list'][0]['content'] ?? '');
        $this->assertSame('8.8.8.8', $result['data']['list'][0]['request_ip'] ?? '');
        $this->assertArrayHasKey('request_region', $result['data']['list'][0] ?? []);
        $this->assertIsString($result['data']['list'][0]['request_region'] ?? '');
    }

    public function testIndexSupportsBusinessNamedRequestIpField(): void
    {
        $this->createSystemOplogFixture([
            'username' => 'carol',
            'action' => '查看日志',
            'content' => '请求地址来自新字段',
            'node' => 'system/oplog/index',
            'request_ip' => '9.9.9.9',
            'geoip' => '',
            'create_time' => '2026-03-11 08:00:00',
        ]);

        $result = $this->callIndexController([
            'output' => 'json',
            'username' => 'carol',
            'request_ip' => '9.9.9.9',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame('9.9.9.9', $result['data']['list'][0]['request_ip'] ?? '');
        $this->assertIsString($result['data']['list'][0]['request_region'] ?? '');
    }

    public function testIndexPaginatesLogsAndFallsBackToDefaultLimit(): void
    {
        for ($i = 1; $i <= 21; ++$i) {
            $this->createSystemOplogFixture([
                'username' => 'pager',
                'action' => '分页行为',
                'content' => sprintf('page-log-%02d', $i),
                'node' => 'system/oplog/index',
                'geoip' => '127.0.0.1',
                'create_time' => sprintf('2026-03-10 10:%02d:00', $i % 60),
            ]);
        }

        $result = $this->callIndexController([
            'output' => 'json',
            'username' => 'pager',
            'action' => '分页行为',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 999,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(21, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(20, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('page-log-21', $result['data']['list'][0]['content'] ?? '');
    }

    public function testRemoveDeletesSelectedLogs(): void
    {
        $first = $this->createSystemOplogFixture(['content' => 'remove-first']);
        $second = $this->createSystemOplogFixture(['content' => 'remove-second']);
        $keep = $this->createSystemOplogFixture(['content' => 'remove-keep']);

        $result = $this->callActionController('remove', [
            'id' => $first->getAttr('id') . ',' . $second->getAttr('id'),
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('数据删除成功！', $result['info'] ?? '');
        $this->assertSame(0, SystemOplog::mk()->whereIn('id', [$first->getAttr('id'), $second->getAttr('id')])->count());
        $this->assertTrue(SystemOplog::mk()->where(['id' => $keep->getAttr('id')])->findOrEmpty()->isExists());
    }

    public function testClearEmptiesAllLogs(): void
    {
        $this->createSystemOplogFixture(['content' => 'clear-first']);
        $this->createSystemOplogFixture(['content' => 'clear-second']);

        $result = $this->callActionController('clear');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('日志清理成功！', $result['info'] ?? '');
        $this->assertSame(0, SystemOplog::mk()->count());
    }

    protected function defineSchema(): void
    {
        $this->createSystemOplogTable();
    }

    private function callIndexController(array $query): array
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('oplog')
            ->setAction('index');

        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new OplogController($this->app);
            $controller->index();
            self::fail('Expected OplogController::index to throw HttpResponseException.');
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
            ->setController('oplog')
            ->setAction($action);

        $this->setRequestPayload($request, $post);
        $this->app->instance('request', $request);

        try {
            $controller = new OplogController($this->app);
            $controller->{$action}();
            self::fail("Expected OplogController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function callActionHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('oplog')
            ->setAction($action);

        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new OplogController($this->app);
            $controller->{$action}();
            self::fail("Expected OplogController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
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
