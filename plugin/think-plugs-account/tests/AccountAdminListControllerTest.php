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

use plugin\account\controller\Device as DeviceController;
use plugin\account\controller\Master as MasterController;
use plugin\account\model\PluginAccountBind;
use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class AccountAdminListControllerTest extends SqliteIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->configureAccountAccess();
    }

    public function testMasterIndexFiltersActiveUsersByKeywordAndDateRange(): void
    {
        $older = $this->createAccountUser([
            'username' => 'master-filter-older',
            'nickname' => '主账号检索',
            'phone' => '13888000001',
        ]);
        $newer = $this->createAccountUser([
            'username' => 'master-filter-newer',
            'nickname' => '主账号检索',
            'phone' => '13888000002',
        ]);
        $outRange = $this->createAccountUser([
            'username' => 'master-filter-out-range',
            'nickname' => '主账号检索',
            'phone' => '13888000003',
        ]);
        $disabled = $this->createAccountUser([
            'username' => 'master-filter-disabled',
            'nickname' => '主账号检索',
            'phone' => '13888000004',
            'status' => 0,
        ]);
        $deleted = $this->createAccountUser([
            'username' => 'master-filter-deleted',
            'nickname' => '主账号检索',
            'phone' => '13888000005',
        ]);

        PluginAccountUser::mk()->where(['id' => $older->getAttr('id')])->update([
            'create_time' => '2026-03-10 08:00:00',
            'update_time' => '2026-03-10 08:00:00',
        ]);
        PluginAccountUser::mk()->where(['id' => $newer->getAttr('id')])->update([
            'create_time' => '2026-03-10 18:00:00',
            'update_time' => '2026-03-10 18:00:00',
        ]);
        PluginAccountUser::mk()->where(['id' => $outRange->getAttr('id')])->update([
            'create_time' => '2026-03-09 18:00:00',
            'update_time' => '2026-03-09 18:00:00',
        ]);
        PluginAccountUser::mk()->where(['id' => $disabled->getAttr('id')])->update([
            'create_time' => '2026-03-10 12:00:00',
            'update_time' => '2026-03-10 12:00:00',
        ]);
        PluginAccountUser::mk()->where(['id' => $deleted->getAttr('id')])->update([
            'create_time' => '2026-03-10 20:00:00',
            'update_time' => '2026-03-10 20:00:00',
            'delete_time' => '2026-03-11 09:00:00',
        ]);

        $result = $this->callIndexController(MasterController::class, [
            'output' => 'json',
            'username' => 'master-filter',
            'create_time' => '2026-03-10 - 2026-03-10',
            '_field_' => 'create_time',
            '_order_' => 'desc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(2, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame([
            'master-filter-newer',
            'master-filter-older',
        ], array_column($result['data']['list'] ?? [], 'username'));
    }

    public function testMasterIndexHistoryViewPaginatesDisabledUsers(): void
    {
        for ($i = 1; $i <= 11; ++$i) {
            $this->createAccountUser([
                'username' => sprintf('master-history-%02d', $i),
                'nickname' => '主账号历史',
                'phone' => sprintf('1397700%04d', $i),
                'status' => 0,
            ]);
        }

        $this->createAccountUser([
            'username' => 'master-history-active',
            'nickname' => '主账号历史',
            'phone' => '13977009999',
            'status' => 1,
        ]);

        $result = $this->callIndexController(MasterController::class, [
            'output' => 'json',
            'type' => 'history',
            'username' => 'master-history',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 10,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(11, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(10, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('master-history-11', $result['data']['list'][0]['username'] ?? '');
    }

    public function testDeviceIndexFiltersByTypeAndPhoneAndIncludesUserRelation(): void
    {
        $owner = $this->createAccountUser([
            'username' => 'device-owner-hit',
            'nickname' => '设备归属用户',
        ]);
        $other = $this->createAccountUser([
            'username' => 'device-owner-other',
            'nickname' => '其他设备用户',
        ]);

        $this->createDeviceBindFixture(intval($owner->getAttr('id')), [
            'type' => Account::WAP,
            'phone' => '13788000001',
            'nickname' => '命中设备',
            'create_time' => '2026-03-10 08:00:00',
            'update_time' => '2026-03-10 08:00:00',
        ]);
        $this->createDeviceBindFixture(intval($owner->getAttr('id')), [
            'type' => Account::WEB,
            'phone' => '13788000002',
            'nickname' => '不同类型设备',
        ]);
        $this->createDeviceBindFixture(intval($other->getAttr('id')), [
            'type' => Account::WAP,
            'phone' => '13788100001',
            'nickname' => '其他用户设备',
        ]);
        $this->createDeviceBindFixture(intval($owner->getAttr('id')), [
            'type' => Account::WAP,
            'phone' => '13788000003',
            'nickname' => '已删除设备',
            'delete_time' => '2026-03-11 09:00:00',
        ]);

        $result = $this->callIndexController(DeviceController::class, [
            'output' => 'json',
            'utype' => Account::WAP,
            'phone' => '1378800',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 1,
            'limit' => 20,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(1, intval($result['data']['page']['total'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('13788000001', $result['data']['list'][0]['phone'] ?? '');
        $this->assertSame(Account::WAP, $result['data']['list'][0]['type'] ?? '');
        $this->assertSame('device-owner-hit', $result['data']['list'][0]['user']['username'] ?? '');
    }

    public function testDeviceIndexHistoryViewPaginatesDisabledDevices(): void
    {
        $owner = $this->createAccountUser([
            'username' => 'device-history-owner',
            'nickname' => '设备历史用户',
        ]);

        for ($i = 1; $i <= 11; ++$i) {
            $this->createDeviceBindFixture(intval($owner->getAttr('id')), [
                'type' => Account::WEB,
                'phone' => sprintf('1366600%04d', $i),
                'nickname' => sprintf('历史设备%02d', $i),
                'status' => 0,
                'create_time' => sprintf('2026-03-10 %02d:00:00', min($i, 23)),
                'update_time' => sprintf('2026-03-10 %02d:00:00', min($i, 23)),
            ]);
        }

        $this->createDeviceBindFixture(intval($owner->getAttr('id')), [
            'type' => Account::WEB,
            'phone' => '13666009999',
            'status' => 1,
        ]);

        $result = $this->callIndexController(DeviceController::class, [
            'output' => 'json',
            'type' => 'history',
            'utype' => Account::WEB,
            'phone' => '1366600',
            '_field_' => 'id',
            '_order_' => 'asc',
            'page' => 2,
            'limit' => 10,
        ]);

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('JSON-DATA', $result['info'] ?? '');
        $this->assertSame(11, intval($result['data']['page']['total'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['pages'] ?? 0));
        $this->assertSame(2, intval($result['data']['page']['current'] ?? 0));
        $this->assertSame(10, intval($result['data']['page']['limit'] ?? 0));
        $this->assertCount(1, $result['data']['list'] ?? []);
        $this->assertSame('13666000011', $result['data']['list'][0]['phone'] ?? '');
        $this->assertSame(Account::WEB, $result['data']['list'][0]['type'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createAccountTables();
    }

    /**
     * @param class-string $controllerClass
     */
    private function callIndexController(string $controllerClass, array $query): array
    {
        $parts = explode('\\', $controllerClass);
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController(strtolower(strval(end($parts))))
            ->setAction('index');

        $this->setRequestPayload($request, $query);
        RequestContext::clear();
        $this->app->instance('request', $request);

        try {
            $controller = new $controllerClass($this->app);
            $controller->index();
            self::fail("Expected {$controllerClass}::index to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return json_decode($exception->getResponse()->getContent(), true) ?: [];
        }
    }

    private function createDeviceBindFixture(int $unid, array $overrides = []): PluginAccountBind
    {
        $bind = PluginAccountBind::mk();
        $bind->save(array_merge([
            'unid' => $unid,
            'type' => Account::WAP,
            'phone' => $this->randomPhone('1375500'),
            'appid' => '',
            'openid' => '',
            'unionid' => '',
            'headimg' => 'https://example.com/device.png',
            'nickname' => '测试设备',
            'password' => '',
            'extra' => [],
            'sort' => 0,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
            'delete_time' => null,
        ], $overrides));

        return PluginAccountBind::mk()->withTrashed()->findOrEmpty($bind->getKey());
    }

    private function setRequestPayload(Request $request, array $data): void
    {
        $property = new \ReflectionProperty(Request::class, 'request');
        $property->setAccessible(true);
        $property->setValue($request, $data);
    }
}
