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

use plugin\system\controller\Builder as BuilderController;
use plugin\system\model\SystemBuilder;
use think\admin\runtime\RequestContext;
use think\admin\tests\Support\SqliteIntegrationTestCase;
use think\exception\HttpResponseException;
use think\Request;

/**
 * @internal
 * @coversNothing
 */
class BuilderControllerTest extends SqliteIntegrationTestCase
{
    public function testAddGetRendersBuilderDesignerForm(): void
    {
        $html = $this->callActionHtml('add');

        $this->assertStringContainsString('form-builder-schema', $html);
        $this->assertStringContainsString('name="table_name"', $html);
        $this->assertStringContainsString('data-builder-generate="form"', $html);
    }

    public function testAddPostPersistsDynamicDefinitionWithGeneratedDefaults(): void
    {
        $result = $this->callActionJson('add', [
            'title' => '用户动态表单',
            'code' => 'user_form',
            'type' => 'form',
            'status' => '1',
            'table_name' => 'system_user',
            'form_field_names' => ['nickname', 'status'],
            'form_fields_json' => '',
            'search_field_names' => [],
            'search_fields_json' => '',
            'table_field_names' => [],
            'table_columns_json' => '',
            'table_options_json' => '',
            'remark' => 'test builder',
        ]);

        $record = SystemBuilder::mk()->where(['name' => 'SystemBuilder:user_form'])->findOrEmpty();
        $config = json_decode(strval($record->getAttr('value')), true) ?: [];

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('动态配置保存成功！', $result['info'] ?? '');
        $this->assertTrue($record->isExists());
        $this->assertSame('system_user', $config['table_name'] ?? '');
        $this->assertCount(2, $config['form_fields'] ?? []);
        $this->assertSame('nickname', $config['form_fields'][0]['name'] ?? '');
        $this->assertSame('status', $config['form_fields'][1]['name'] ?? '');
    }

    public function testFieldsReturnsSelectedTableSchema(): void
    {
        $result = $this->callActionJson('fields', ['table' => 'system_user'], 'GET');

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('字段读取成功！', $result['info'] ?? '');
        $this->assertSame('system_user', $result['data']['table'] ?? '');
        $this->assertArrayHasKey('nickname', $result['data']['fields'] ?? []);
        $this->assertSame('nickname', $result['data']['fields']['nickname']['form']['name'] ?? '');
    }

    public function testPreviewFormCanRenderAndPersistTableRow(): void
    {
        $builder = $this->createBuilderFixture([
            'title' => '用户动态表单',
            'code' => 'preview_form',
            'type' => 'form',
            'table_name' => 'system_user',
            'status' => 1,
            'form_field_names' => ['nickname', 'status'],
            'form_fields' => [
                ['type' => 'text', 'name' => 'nickname', 'title' => '昵称', 'required' => true],
                ['type' => 'radio', 'name' => 'status', 'title' => '状态', 'options' => [1 => '启用', 0 => '禁用']],
            ],
        ]);

        $html = $this->callActionHtml('preview', ['id' => intval($builder->getAttr('id'))]);
        $this->assertStringContainsString('name="nickname"', $html);
        $this->assertStringContainsString('name="status"', $html);

        $result = $this->callActionJson('preview', [
            'builder_id' => intval($builder->getAttr('id')),
            'nickname' => 'Dynamic User',
            'status' => '1',
        ]);

        $saved = $this->app->db->table('system_user')->where(['nickname' => 'Dynamic User'])->find();

        $this->assertSame(1, intval($result['code'] ?? 0));
        $this->assertSame('动态表单保存成功！', $result['info'] ?? '');
        $this->assertIsArray($saved);
        $this->assertSame('Dynamic User', $saved['nickname'] ?? '');
    }

    public function testPreviewPageReturnsLayTableRows(): void
    {
        $this->createSystemUserFixture(['nickname' => 'Alpha Builder']);
        $this->createSystemUserFixture(['nickname' => 'Beta Builder']);
        $builder = $this->createBuilderFixture([
            'title' => '用户动态列表',
            'code' => 'preview_page',
            'type' => 'page',
            'table_name' => 'system_user',
            'status' => 1,
            'search_field_names' => ['nickname'],
            'search_fields' => [
                ['type' => 'input', 'name' => 'nickname', 'label' => '昵称', 'query' => 'like'],
            ],
            'table_field_names' => ['id', 'nickname'],
            'table_columns' => [
                ['field' => 'id', 'title' => 'ID'],
                ['field' => 'nickname', 'title' => '昵称'],
            ],
        ]);

        $result = $this->callActionJson('preview', [
            'builder_id' => intval($builder->getAttr('id')),
            'output' => 'layui.table',
            'page' => 1,
            'limit' => 20,
            'nickname' => 'Alpha',
        ], 'GET');

        $this->assertSame(0, intval($result['code'] ?? -1));
        $this->assertSame(1, intval($result['count'] ?? 0));
        $this->assertSame('Alpha Builder', $result['data'][0]['nickname'] ?? '');
    }

    protected function defineSchema(): void
    {
        $this->createSystemDataTable();
        $this->createSystemUserTable();
    }

    private function createBuilderFixture(array $config): SystemBuilder
    {
        $builder = SystemBuilder::mk();
        $builder->save([
            'name' => 'SystemBuilder:' . strval($config['code']),
            'value' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        return $builder->refresh();
    }

    private function callActionHtml(string $action, array $query = []): string
    {
        $request = (new Request())
            ->withGet($query)
            ->setMethod('GET')
            ->setController('builder')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $query);
        $this->app->instance('request', $request);

        try {
            $controller = new BuilderController($this->app);
            $controller->{$action}();
            self::fail("Expected BuilderController::{$action} to throw HttpResponseException.");
        } catch (HttpResponseException $exception) {
            return $exception->getResponse()->getContent();
        }
    }

    private function callActionJson(string $action, array $data = [], string $method = 'POST'): array
    {
        $request = (new Request())
            ->withGet($data)
            ->withPost($data)
            ->setMethod($method)
            ->setController('builder')
            ->setAction($action);

        $this->bindAdminUser();
        $this->setRequestPayload($request, $data);
        $this->app->instance('request', $request);

        try {
            $controller = new BuilderController($this->app);
            $controller->{$action}();
            self::fail("Expected BuilderController::{$action} to throw HttpResponseException.");
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
}
