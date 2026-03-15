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

use PHPUnit\Framework\TestCase;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\Validate;

/**
 * @internal
 * @coversNothing
 */
class FormBuilderTest extends TestCase
{
    public function testCanCollectSchemaAndPortableValidateRules()
    {
        $builder = $this->newBuilder();
        $builder->addTextInput('appid', '小程序', 'AppId', true, '必填应用标识', '^wx[0-9a-z]{16}$', ['maxlength' => 18]);
        $builder->addField([
            'type' => 'textarea',
            'name' => 'remark',
            'title' => '备注',
            'required' => true,
            'rules' => ['max:100' => '备注最多100个字符！'],
            'attrs' => ['maxlength' => 100],
        ]);

        $schema = $builder->toArray();
        $rules = $builder->getValidateRules();
        $requestRules = $builder->getRequestRules();

        $this->assertCount(2, $schema['fields']);
        $this->assertSame('$vo', $schema['variable']);
        $this->assertSame('appid', $schema['fields'][0]['name']);
        $this->assertTrue($schema['fields'][0]['required']);
        $this->assertSame('^wx[0-9a-z]{16}$', $schema['fields'][0]['pattern']);
        $this->assertSame('', $requestRules['appid.default']);
        $this->assertSame('', $requestRules['remark.default']);
        $this->assertSame('小程序不能为空！', $rules['appid.require']);
        $this->assertSame('小程序格式错误！', $rules['appid.regex:/^wx[0-9a-z]{16}$/']);
        $this->assertSame('备注不能为空！', $rules['remark.require']);
        $this->assertSame('备注最多100个字符！', $rules['remark.max:100']);
    }

    public function testCheckboxTemplateUsesRealFieldNameAndVariable()
    {
        $builder = $this->newBuilder();
        $builder->setAction('/submit')->setVariable('data');
        $builder->addCheckInput('roles', '角色', '', 'roles', true);
        $builder->addSubmitButton();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('isset($data.roles)', $html);
        $this->assertStringContainsString('name="roles[]"', $html);
        $this->assertStringContainsString('{notempty name="data.id"}', $html);
        $this->assertStringContainsString('form-builder-schema', $html);
    }

    public function testUrlPatternRuleEscapesRegexDelimiterForBackendValidation()
    {
        $builder = $this->newBuilder();
        $builder->addTextInput('auth_url', '授权跳转入口', 'Getway', true, 'URL pattern', '^https?://.*?auth.*?source=SOURCE');

        $rules = $builder->getValidateRules();
        $rule = 'regex:/^https?:\/\/.*?auth.*?source=SOURCE$/';

        $this->assertArrayHasKey("auth_url.{$rule}", $rules);

        $validate = new Validate();
        $this->assertTrue($validate->rule(['auth_url' => $rule])->check([
            'auth_url' => 'https://open.cuci.cc/auth?source=SOURCE',
        ]));
    }

    public function testCheckAndUploadFieldsCanRenderRemarks()
    {
        $builder = $this->newBuilder();
        $builder->addField([
            'type' => 'image',
            'name' => 'headimg',
            'title' => '头像',
            'remark' => '上传头像',
        ]);
        $builder->addField([
            'type' => 'checkbox',
            'name' => 'types',
            'title' => '通道',
            'vname' => 'types',
            'remark' => '选择可用通道',
        ]);

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('上传头像', $html);
        $this->assertStringContainsString('选择可用通道', $html);
    }

    public function testSelectAndStaticChoiceFieldsCanRenderWithoutTemplateVariables()
    {
        $builder = $this->newBuilder();
        $builder->addSelectInput('table_name', '数据表', 'Table', true, '', [
            'system_user' => 'system_user',
            'system_menu' => 'system_menu',
        ]);
        $builder->addField([
            'type' => 'radio',
            'name' => 'status',
            'title' => '状态',
            'options' => [1 => '启用', 0 => '禁用'],
        ]);

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('name="table_name"', $html);
        $this->assertStringContainsString('value="system_user"', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('启用', $html);
        $this->assertStringContainsString('禁用', $html);
    }

    private function newBuilder(): FormBuilder
    {
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMock();
        return (new FormBuilder('form', 'modal', $controller))->setAction('/submit');
    }

    private function invokePrivate(object $object, string $method): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invoke($object);
    }
}
