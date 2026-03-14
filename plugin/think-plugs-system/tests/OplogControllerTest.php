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

use plugin\system\controller\Oplog as OplogController;
use plugin\system\model\SystemOplog;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class OplogControllerTest extends SqliteIntegrationTestCase
{
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
        $this->assertArrayHasKey('geoisp', $result['data']['list'][0] ?? []);
        $this->assertIsString($result['data']['list'][0]['geoisp'] ?? '');
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

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
