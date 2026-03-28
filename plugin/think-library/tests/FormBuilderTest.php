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

use PHPUnit\Framework\TestCase;
use think\admin\Controller;
use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\FormComponents;
use think\admin\builder\form\FormChoiceField;
use think\admin\builder\form\FormSelectField;
use think\admin\builder\form\FormTextField;
use think\admin\builder\form\FormUploadField;
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
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->text('appid', '小程序', 'AppId', true, '必填应用标识', '^wx[0-9a-z]{16}$', ['maxlength' => 18])
                    ->field([
                        'type' => 'textarea',
                        'name' => 'remark',
                        'title' => '备注',
                        'required' => true,
                        'rules' => ['max:100' => '备注最多100个字符！'],
                        'attrs' => ['maxlength' => 100],
                    ]);
            });
        })->build();

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

    public function testFieldDefaultsCanRenderAndValidateWithoutInitialVo(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->text('sort', '排序', 'Sort', false, '', null, ['type' => 'number'])->defaultValue(0);
                $fields->select('driver', '驱动', 'Driver', false, '', [
                    'local' => '本地',
                    'qiniu' => '七牛',
                ])->defaultValue('local');
                $fields->radio('status', '状态', 'Status', '', false)
                    ->options([1 => '启用', 0 => '禁用'])
                    ->defaultValue('1');
                $fields->checkbox('scene', '场景', 'Scene', '', false)
                    ->options(['index' => '列表', 'form' => '表单'])
                    ->defaultValue(['index']);
            });
        })->build();

        $requestRules = $builder->getRequestRules();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('0', $requestRules['sort.default']);
        $this->assertSame('local', $requestRules['driver.default']);
        $this->assertSame('1', $requestRules['status.default']);
        $this->assertSame(['index'], $requestRules['scene.default']);
        $this->assertStringContainsString('value="{$vo.sort|default=0}"', $html);
        $this->assertStringContainsString("!isset(\$vo.driver) and strval('local') eq 'local'", $html);
        $this->assertStringContainsString("!isset(\$vo.status) and strval('1')==strval('1')", $html);
        $this->assertStringContainsString("!isset(\$vo.scene) and in_array('index',['index'])", $html);
    }

    public function testCheckboxTemplateUsesRealFieldNameAndVariable()
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->action('/submit')
                ->variable('data')
                ->fields(function ($fields) {
                    $fields->checkbox('roles', '角色', '', 'roles', true);
                })->actions(function ($actions) {
                    $actions->submit();
                });
        })->build();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('isset($data.roles)', $html);
        $this->assertStringContainsString('name="roles[]"', $html);
        $this->assertStringContainsString('{notempty name="data.id"}', $html);
        $this->assertStringContainsString('form-builder-schema', $html);
    }

    public function testUrlPatternRuleEscapesRegexDelimiterForBackendValidation()
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->text('auth_url', '授权跳转入口', 'Getway', true, 'URL pattern', '^https?://.*?auth.*?source=SOURCE');
            });
        })->build();

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
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->field([
                    'type' => 'image',
                    'name' => 'headimg',
                    'title' => '头像',
                    'remark' => '上传头像',
                ])->field([
                    'type' => 'checkbox',
                    'name' => 'types',
                    'title' => '通道',
                    'vname' => 'types',
                    'remark' => '选择可用通道',
                ]);
            });
        })->build();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('上传头像', $html);
        $this->assertStringContainsString('选择可用通道', $html);
    }

    public function testUploadFieldsCanRenderRuntimeInitializers(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->image('headimg', '头像')->types('jpg,png');
                $fields->video('intro_video', '介绍视频');
                $fields->images('gallery', '图集');
            });
        })->build();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('data-file="image"', $html);
        $this->assertStringContainsString('data-field="headimg"', $html);
        $this->assertStringContainsString('data-type="jpg,png"', $html);
        $this->assertStringContainsString('data-field="intro_video"', $html);
        $this->assertStringContainsString("join('|', \$vo.gallery)", $html);
        $this->assertStringNotContainsString('uploadOneImage()', $html);
        $this->assertStringContainsString('uploadOneVideo()', $html);
        $this->assertStringContainsString('uploadMultipleImage()', $html);
    }

    public function testImageUploadFieldCanRenderPreviewOnlyMode(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->image('headimg', '头像')->previewOnly();
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('preview', $schema['fields'][0]['upload']['display'] ?? null);
        $this->assertMatchesRegularExpression('/<input[^>]*name="headimg"[^>]*type="hidden"[^>]*data-upload-display="preview"/', $html);
        $this->assertStringNotContainsString('data-field="headimg"', $html);
        $this->assertStringContainsString('uploadOneImage()', $html);
    }

    public function testTextFieldInputContentCanRenderRightAddon(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $field = $fields->text('browser_icon', '浏览器小图标', 'Browser Icon', true, '', 'url', [
                    'placeholder' => '请上传浏览器图标',
                ]);
                $field->inputRightIcon('layui-icon-upload-drag', [
                    'data-file' => 'btn',
                    'data-type' => 'png,jpg,jpeg',
                    'data-field' => 'browser_icon',
                ]);
            });
        })->build();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('name="browser_icon"', $html);
        $this->assertStringContainsString('class="pr40 layui-input"', $html);
        $this->assertStringContainsString('data-field="browser_icon"', $html);
        $this->assertStringContainsString('layui-icon-upload-drag', $html);
        $this->assertStringContainsString('onmousedown="event.preventDefault();event.stopPropagation();"', $html);
        $this->assertStringContainsString('ontouchstart="event.preventDefault();event.stopPropagation();"', $html);
    }

    public function testSelectAndStaticChoiceFieldsCanRenderWithoutTemplateVariables()
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->select('table_name', '数据表', 'Table', true, '', [
                    'system_user' => 'system_user',
                    'system_menu' => '系统<菜单>',
                ])->field([
                    'type' => 'radio',
                    'name' => 'status',
                    'title' => '状态',
                    'options' => [1 => '启用&公开', 0 => '禁用'],
                ]);
            });
        })->build();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringContainsString('name="table_name"', $html);
        $this->assertStringContainsString('value="system_user"', $html);
        $this->assertStringContainsString('系统&lt;菜单&gt;', $html);
        $this->assertStringContainsString('name="status"', $html);
        $this->assertStringContainsString('启用&amp;公开', $html);
        $this->assertStringContainsString('禁用', $html);
    }

    public function testActionsCanCustomizeAttrsAndHtml(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->actions(function ($actions) {
                $actions->submit('保存', '', ['data-scene' => 'submit'], 'layui-btn-warm')
                    ->cancel('关闭', '确定关闭吗？', ['data-close-mode' => 'drawer'], 'layui-btn-primary')
                    ->button('预览', 'button', '', ['data-preview' => 'true'], 'layui-btn-normal')
                    ->html('<button type="button" class="layui-btn layui-btn-xs" data-extra="1">更多</button>');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('submit', $schema['buttons'][0]['type']);
        $this->assertSame('submit', $schema['buttons'][0]['attrs']['type']);
        $this->assertSame('submit', $schema['buttons'][0]['attrs']['data-scene']);
        $this->assertSame('drawer', $schema['buttons'][1]['attrs']['data-close-mode']);
        $this->assertSame('button', $schema['buttons'][2]['type']);
        $this->assertSame('html', $schema['buttons'][3]['type']);
        $this->assertStringContainsString('data-scene="submit"', $html);
        $this->assertStringContainsString('data-close-mode="drawer"', $html);
        $this->assertStringContainsString('data-preview="true"', $html);
        $this->assertStringContainsString('data-extra="1"', $html);
    }

    public function testFormLayoutCanSetRootAttrsAndModules(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->attr('id', 'ProfileForm')
                ->class('profile-form')
                ->class(['profile-form', 'profile-form-shell'])
                ->data('scene', 'profile')
                ->module('editor', ['field' => 'content'])
                ->fields(function ($fields) {
                    $fields->text('content', '内容');
                });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('ProfileForm', $schema['attrs']['id']);
        $this->assertSame('profile', $schema['attrs']['data-scene']);
        $this->assertSame('profile-form profile-form-shell layui-form layui-card', $schema['attrs']['class']);
        $this->assertSame('form', $schema['attrs']['data-builder-scope']);
        $this->assertSame('editor', $schema['modules'][0]['name']);
        $this->assertSame('content', $schema['modules'][0]['config']['field']);
        $this->assertStringContainsString('id="ProfileForm"', $html);
        $this->assertStringContainsString('class="profile-form profile-form-shell layui-form layui-card"', $html);
        $this->assertStringContainsString('data-scene="profile"', $html);
        $this->assertStringContainsString('data-builder-scope="form"', $html);
        $this->assertStringContainsString('data-builder-modules=', $html);
    }

    public function testFormCanRenderStructuredContentNodes(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $group = $form->section()->class('profile-section')->data('scene', 'profile')->module('region', ['code' => 'cn']);
            $group->fields(function ($fields) {
                $fields->text('nickname', '用户名称', 'Nickname', true, '请输入用户名称');
            });
            $form->actionBar(function ($actions) {
                $actions->submit('保存', '', ['data-scene' => 'submit']);
            })->class('profile-actions')->data('scene', 'actions');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('element', $schema['content'][0]['type']);
        $this->assertSame('section', $schema['content'][0]['tag']);
        $this->assertSame('profile', $schema['content'][0]['attrs']['data-scene']);
        $this->assertSame('region', $schema['content'][0]['modules'][0]['name']);
        $this->assertSame('actions', $schema['content'][1]['type']);
        $this->assertSame('actions', $schema['content'][1]['attrs']['data-scene']);
        $this->assertStringContainsString('class="profile-section"', $html);
        $this->assertStringContainsString('class="layui-form-item text-center profile-actions"', $html);
        $this->assertStringContainsString('data-scene="actions"', $html);
        $this->assertStringContainsString('data-builder-modules=', $html);
    }

    public function testPageModeCanRenderTitleAndDefaultPadding(): void
    {
        $builder = $this->newBuilder('form', 'page');
        $builder->define(function ($form) {
            $form->title('资料编辑')
                ->headerButton('返回列表', 'button', '', ['data-target-backup' => null], 'layui-btn-primary layui-btn-sm')
                ->fields(function ($fields) {
                    $fields->text('nickname', '用户名称');
                });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormPage');

        $this->assertSame('资料编辑', $schema['title']);
        $this->assertSame('pa40', $schema['body_attrs']['class']);
        $this->assertSame('返回列表', $schema['header_buttons'][0]['name']);
        $this->assertStringContainsString('data-builder-scope="page"', $html);
        $this->assertStringContainsString('layui-card-header', $html);
        $this->assertStringContainsString('layui-card-line', $html);
        $this->assertStringContainsString('pull-right', $html);
        $this->assertStringContainsString('layui-icon font-s10 color-desc mr5', $html);
        $this->assertStringContainsString('class="layui-card-body"', $html);
        $this->assertStringContainsString('class="layui-card-table"', $html);
        $this->assertStringContainsString('class="think-box-shadow"', $html);
        $this->assertStringContainsString('资料编辑', $html);
        $this->assertStringContainsString('返回列表', $html);
        $this->assertStringContainsString('class="pa40"', $html);
    }

    public function testModalModeUsesPa40AsDefaultPadding(): void
    {
        $builder = $this->newBuilder('form', 'modal');
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->text('nickname', '用户名称');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('layui-card-body pa40', $schema['body_attrs']['class']);
        $this->assertStringContainsString('class="layui-card-body pa40"', $html);
    }

    public function testModuleObjectsCanMutateRootNodeAndFieldParts(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->moduleItem('editor', ['field' => 'content'])
                ->option('mode', 'markdown');

            $section = $form->section()->class('module-section');
            $section->moduleItem('region', ['code' => 'cn'])
                ->option('level', 2);

            $form->fields(function ($fields) {
                $field = $fields->text('nickname', '用户名称');
                $field->label()->moduleItem('tooltip', ['target' => 'nickname'])->option('placement', 'top');
                $field->input()->moduleItem('picker', ['target' => 'nickname'])->option('mode', 'dialog');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('editor', $schema['modules'][0]['name'] ?? null);
        $this->assertSame('markdown', $schema['modules'][0]['config']['mode'] ?? null);
        $this->assertSame('region', $schema['content'][0]['modules'][0]['name'] ?? null);
        $this->assertSame(2, $schema['content'][0]['modules'][0]['config']['level'] ?? null);
        $this->assertSame('tooltip', $schema['fields'][0]['parts']['label']['modules'][0]['name'] ?? null);
        $this->assertSame('top', $schema['fields'][0]['parts']['label']['modules'][0]['config']['placement'] ?? null);
        $this->assertSame('picker', $schema['fields'][0]['parts']['input']['modules'][0]['name'] ?? null);
        $this->assertSame('dialog', $schema['fields'][0]['parts']['input']['modules'][0]['config']['mode'] ?? null);
        $this->assertStringContainsString('data-builder-modules=', $html);
        $this->assertStringContainsString('markdown', $html);
        $this->assertStringContainsString('dialog', $html);
    }

    public function testAttributeObjectsCanMutateRootAndFieldParts(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->attrsItem()
                ->id('AttrForm')
                ->class('attr-form')
                ->data('scene', 'root');

            $form->fields(function ($fields) {
                $field = $fields->text('nickname', '用户名称');
                $field->attrsItem()->class('field-shell')->data('scene', 'field');
                $field->label()->attrsItem()->class('label-bag')->data('scene', 'label');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('AttrForm', $schema['attrs']['id'] ?? null);
        $this->assertSame('root', $schema['attrs']['data-scene'] ?? null);
        $this->assertSame('attr-form layui-form layui-card', $schema['attrs']['class'] ?? null);
        $this->assertSame('field', $schema['content'][0]['attrs']['data-scene'] ?? null);
        $this->assertSame('label-bag', $schema['fields'][0]['parts']['label']['attrs']['class'] ?? null);
        $this->assertSame('label', $schema['fields'][0]['parts']['label']['attrs']['data-scene'] ?? null);
        $this->assertStringContainsString('id="AttrForm"', $html);
        $this->assertStringContainsString('class="attr-form layui-form layui-card"', $html);
        $this->assertStringContainsString('class="field-shell layui-form-item block relative"', $html);
        $this->assertStringContainsString('class="label-bag help-label"', $html);
        $this->assertStringContainsString('data-scene="label"', $html);
    }

    public function testFormCanRenderRawHtmlNodes(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->section(function ($group) {
                $group->class('profile-section');
                $group->html('<div class="profile-tip" data-scene="tip">表单说明</div>');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('html', $schema['content'][0]['children'][0]['type']);
        $this->assertStringContainsString('class="profile-tip"', $html);
        $this->assertStringContainsString('data-scene="tip"', $html);
    }

    public function testFieldNodeCanMutateStructuredParts(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->text('nickname', '用户名称', 'Nickname', true, '请输入用户名称')
                    ->class('field-shell')
                    ->data('scene', 'profile')
                    ->module('picker', ['target' => 'nickname'])
                    ->label()->class('field-label')->class(['field-label', 'field-label-strong'])->module('tooltip', ['target' => 'nickname'])->end()
                    ->body()->class('field-body')->data('body', 'profile')->end()
                    ->input()->class('input-lg')->data('role', 'primary')->attr('placeholder', '请填写用户名称')->end()
                    ->remarkNode()->class('field-remark')->html('请填写用户名称')->end()
                    ->text('email', '联系邮箱', 'Email');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('field', $schema['content'][0]['type']);
        $this->assertSame('profile', $schema['content'][0]['attrs']['data-scene']);
        $this->assertSame('picker', $schema['content'][0]['modules'][0]['name']);
        $this->assertSame('field-label field-label-strong', $schema['fields'][0]['parts']['label']['attrs']['class']);
        $this->assertSame('tooltip', $schema['fields'][0]['parts']['label']['modules'][0]['name']);
        $this->assertSame('field-body', $schema['fields'][0]['parts']['body']['attrs']['class']);
        $this->assertSame('input-lg', $schema['fields'][0]['parts']['input']['attrs']['class']);
        $this->assertSame('primary', $schema['fields'][0]['parts']['input']['attrs']['data-role']);
        $this->assertSame('请填写用户名称', $schema['fields'][0]['parts']['input']['attrs']['placeholder']);
        $this->assertSame('field-remark', $schema['fields'][0]['parts']['remark']['attrs']['class']);
        $this->assertStringContainsString('class="field-shell layui-form-item block relative"', $html);
        $this->assertStringContainsString('data-scene="profile"', $html);
        $this->assertStringContainsString('data-builder-modules=', $html);
        $this->assertStringContainsString('class="field-label field-label-strong help-label label-required-prev"', $html);
        $this->assertStringContainsString('class="field-body"', $html);
        $this->assertStringContainsString('data-body="profile"', $html);
        $this->assertStringContainsString('data-role="primary"', $html);
        $this->assertStringContainsString('class="input-lg layui-input"', $html);
        $this->assertStringContainsString('class="field-remark help-block"', $html);
        $this->assertStringContainsString('placeholder="请填写用户名称"', $html);
    }

    public function testTypedFieldsCanMutateRuntimeSchemaAndRules(): void
    {
        $builder = $this->newBuilder();
        $nodes = [];
        $builder->define(function ($form) use (&$nodes) {
            $form->fields(function ($fields) use (&$nodes) {
                $nodes['text'] = $fields->text('appid', '应用')
                    ->title('应用标识')
                    ->required()
                    ->pattern('^wx[0-9a-z]{16}$')
                    ->rule('max:18', '应用标识最多18位字符！')
                    ->placeholder('请输入 AppId');
                $nodes['text']->maxlength(18)->readonly();

                $nodes['select'] = $fields->select('status', '状态', 'Status', false, '', [1 => '启用', 0 => '禁用']);
                $nodes['select']->source('statusOptions')->search()->option(2, '待审核');

                $nodes['choice'] = $fields->checkbox('roles', '角色', '', 'roles');
                $nodes['choice']->source('roleOptions')->required();

                $nodes['upload'] = $fields->image('cover', '封面');
                $nodes['upload']->types('png,webp');
            });
        })->build();

        $schema = $builder->toArray();
        $rules = $builder->getValidateRules();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertInstanceOf(FormTextField::class, $nodes['text']);
        $this->assertInstanceOf(FormSelectField::class, $nodes['select']);
        $this->assertInstanceOf(FormChoiceField::class, $nodes['choice']);
        $this->assertInstanceOf(FormUploadField::class, $nodes['upload']);

        $this->assertSame('请输入 AppId', $schema['fields'][0]['attrs']['placeholder']);
        $this->assertSame(18, $schema['fields'][0]['attrs']['maxlength']);
        $this->assertArrayHasKey('readonly', $schema['fields'][0]['attrs']);
        $this->assertSame('statusOptions', $schema['fields'][1]['vname']);
        $this->assertArrayHasKey('lay-search', $schema['fields'][1]['attrs']);
        $this->assertSame('待审核', $schema['fields'][1]['options']['2']);
        $this->assertSame('roleOptions', $schema['fields'][2]['vname']);
        $this->assertSame('png,webp', $schema['fields'][3]['upload']['types']);

        $this->assertSame('应用标识不能为空！', $rules['appid.require']);
        $this->assertSame('应用标识格式错误！', $rules['appid.regex:/^wx[0-9a-z]{16}$/']);
        $this->assertSame('应用标识最多18位字符！', $rules['appid.max:18']);
        $this->assertSame('角色不能为空！', $rules['roles.require']);
        $this->assertStringContainsString('placeholder="请输入 AppId"', $html);
        $this->assertStringContainsString('data-type="png,webp"', $html);
    }

    public function testUploadConfigObjectCanMutateSchemaAndRuntime(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->image('cover', '封面')
                    ->uploadConfig()
                    ->types('png,webp')
                    ->triggerClass('upload-trigger')
                    ->triggerIcon('layui-icon-camera')
                    ->triggerAttr('data-scene', 'cover-upload')
                    ->runtimeSelector('#cover-upload')
                    ->runtimeMethod('uploadCoverImage');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('png,webp', $schema['fields'][0]['upload']['types'] ?? null);
        $this->assertSame('upload-trigger', $schema['fields'][0]['upload']['trigger']['class'] ?? null);
        $this->assertSame('layui-icon-camera', $schema['fields'][0]['upload']['trigger']['icon'] ?? null);
        $this->assertSame('cover-upload', $schema['fields'][0]['upload']['trigger']['attrs']['data-scene'] ?? null);
        $this->assertSame('#cover-upload', $schema['fields'][0]['upload']['runtime']['selector'] ?? null);
        $this->assertSame('uploadCoverImage', $schema['fields'][0]['upload']['runtime']['method'] ?? null);
        $this->assertStringContainsString('class="layui-icon layui-icon-camera input-right-icon upload-trigger"', $html);
        $this->assertStringContainsString('data-scene="cover-upload"', $html);
        $this->assertStringContainsString('data-type="png,webp"', $html);
        $this->assertStringContainsString('$("#cover-upload").uploadCoverImage()', $html);
    }

    public function testFieldOptionsObjectsCanMutateSourceAndOptions(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->fields(function ($fields) {
                $fields->select('status', '状态', 'Status', false, '', [1 => '启用', 0 => '禁用'])
                    ->optionsItem()
                    ->source('statusOptions')
                    ->option(2, '待审核')
                    ->removeOption(0);

                $fields->checkbox('roles', '角色', '', 'roles')
                    ->optionsItem()
                    ->source('roleOptions')
                    ->option('admin', '管理员');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('statusOptions', $schema['fields'][0]['vname'] ?? null);
        $this->assertSame('启用', $schema['fields'][0]['options']['1'] ?? null);
        $this->assertSame('待审核', $schema['fields'][0]['options']['2'] ?? null);
        $this->assertArrayNotHasKey('0', $schema['fields'][0]['options']);
        $this->assertSame('roleOptions', $schema['fields'][1]['vname'] ?? null);
        $this->assertSame('管理员', $schema['fields'][1]['options']['admin'] ?? null);
        $this->assertStringContainsString('{foreach $statusOptions as $k=>$v}', $html);
        $this->assertStringContainsString('<!--{foreach $roleOptions as $k=>$v}item-->', $html);
    }

    public function testFormPresetsAndComponentsCanRenderStructuredNodes(): void
    {
        $builder = $this->newBuilder('form', 'page')->preset('page-form');
        $builder->define(function ($form) {
            $form->title('资料设置');

            $form->component(FormComponents::intro()->config([
                'title' => '基础资料',
                'description' => '通过组件对象输出表单结构。',
            ]));

            $section = $form->component(FormComponents::section()->config([
                'title' => '账号信息',
                'description' => '支持 DOM 级插入和字段组合。',
            ])->body(function ($body) {
                $body->component(FormComponents::note('请确认账号信息后再保存。'));
                $body->fields(function ($fields) {
                    $fields->text('nickname', '用户名称');
                });
            }));

            $section->prepend('div', function ($node) {
                $node->class('section-prefix')->html('前置提示');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormPage');

        $this->assertSame('page-form', $schema['preset']);
        $this->assertStringContainsString('data-builder-mode="page"', $html);
        $this->assertStringContainsString('data-builder-preset="page-form"', $html);
        $this->assertStringContainsString('通过组件对象输出表单结构。', $html);
        $this->assertStringContainsString('前置提示', $html);
        $this->assertStringContainsString('请确认账号信息后再保存。', $html);
    }

    public function testDialogFormPresetCanExposeExpectedSchema(): void
    {
        $builder = $this->newBuilder('form', 'modal')->preset('dialog-form')->build();
        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('dialog-form', $schema['preset'] ?? null);
        $this->assertStringContainsString('data-builder-mode="modal"', $html);
        $this->assertStringContainsString('data-builder-preset="dialog-form"', $html);
    }

    public function testMultipleActionBarsOnlyRenderIdentityFieldOnce(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->variable('data')
                ->fields(function ($fields) {
                    $fields->text('nickname', '用户名称');
                });
            $form->actionBar(function ($actions) {
                $actions->submit('保存');
            });
            $form->actionBar(function ($actions) {
                $actions->cancel('关闭');
            });
        })->build();

        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame(1, substr_count($html, 'name="id"'));
        $this->assertSame(1, substr_count($html, '<div class="hr-line-dashed"></div>'));
        $this->assertStringContainsString('>保存</button>', $html);
        $this->assertStringContainsString('>关闭</button>', $html);
    }

    public function testFormCanUseDirectObjectAccessors(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->action('/submit')->variable('data');
            $form->fieldsNode()->text('nickname', '用户名称')->placeholder('请输入用户名称');
            $form->actionsNode()->submit('保存', '', ['data-scene' => 'direct-submit']);
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame('/submit', $schema['action']);
        $this->assertSame('nickname', $schema['fields'][0]['name']);
        $this->assertSame('direct-submit', $schema['buttons'][0]['attrs']['data-scene'] ?? null);
        $this->assertStringContainsString('placeholder="请输入用户名称"', $html);
        $this->assertStringContainsString('data-scene="direct-submit"', $html);
    }

    public function testFormBodyAttributesCanBeConfiguredByBuilder(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->bodyClass('pa20')
                ->bodyClass(['profile-body', 'pa20'])
                ->bodyData('scene', 'profile')
                ->bodyAttrsItem()
                ->id('ProfileFormBody')
                ->attr('data-mode', 'modal')
                ->end();

            $form->fields(function ($fields) {
                $fields->text('nickname', '用户名称');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $bodyClass = strval($schema['body_attrs']['class'] ?? '');
        foreach (['layui-card-body', 'pa40', 'pa20', 'profile-body'] as $class) {
            $this->assertStringContainsString($class, $bodyClass);
        }
        $this->assertSame('profile', $schema['body_attrs']['data-scene'] ?? null);
        $this->assertSame('modal', $schema['body_attrs']['data-mode'] ?? null);
        $this->assertSame('ProfileFormBody', $schema['body_attrs']['id'] ?? null);
        $this->assertStringContainsString('id="ProfileFormBody"', $html);
        foreach (['layui-card-body', 'pa40', 'pa20', 'profile-body'] as $class) {
            $this->assertStringContainsString($class, $html);
        }
        $this->assertStringContainsString('data-scene="profile"', $html);
        $this->assertStringContainsString('data-mode="modal"', $html);
    }

    public function testRemovingFormNodesKeepsSchemaAndHtmlConsistent(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $field = $form->fieldsNode()->text('name', '名称');
            $form->actions(function ($actions) {
                $actions->submit('保存');
            });

            $field->remove();
            $form->actionBar()->remove();
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame([], $schema['content']);
        $this->assertSame([], $schema['fields']);
        $this->assertSame([], $schema['buttons']);
        $this->assertStringNotContainsString('name="name"', $html);
        $this->assertStringNotContainsString('>保存</button>', $html);
    }

    public function testActionBarCanBeRecreatedAfterRemoval(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $bar = $form->actionBar();
            $bar->class('first-bar');
            $bar->remove();

            $form->actionBar()->class('second-bar');
            $form->actions(function ($actions) {
                $actions->submit('保存');
            });
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertSame(1, count($schema['buttons']));
        $this->assertStringNotContainsString('first-bar', $html);
        $this->assertStringContainsString('second-bar', $html);
        $this->assertStringContainsString('>保存</button>', $html);
    }

    public function testFormLayoutRemoveApisCanUpdateRootAttributes(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $form->class('alpha')
                ->attr('data-scene', 'demo')
                ->data('mode', 'modal')
                ->removeClass('alpha')
                ->removeAttr('data-scene')
                ->removeData('mode');
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertStringNotContainsString('alpha', strval($schema['attrs']['class'] ?? ''));
        $this->assertArrayNotHasKey('data-scene', $schema['attrs']);
        $this->assertArrayNotHasKey('data-mode', $schema['attrs']);
        $this->assertStringNotContainsString('data-scene="demo"', $html);
        $this->assertStringNotContainsString('data-mode="modal"', $html);
    }

    public function testFormNodesPreventCyclicReparenting(): void
    {
        $builder = $this->newBuilder();
        $builder->define(function ($form) {
            $outer = $form->div()->class('outer');
            $inner = $outer->div()->class('inner');
            $inner->appendNode($form);
        })->build();

        $schema = $builder->toArray();
        $html = $this->invokePrivate($builder, '_buildFormModal');

        $this->assertCount(1, $schema['content']);
        $this->assertSame('outer', $schema['content'][0]['attrs']['class'] ?? null);
        $this->assertSame('inner', $schema['content'][0]['children'][0]['attrs']['class'] ?? null);
        $this->assertStringContainsString('class="outer"', $html);
        $this->assertStringContainsString('class="inner"', $html);
    }

    private function newBuilder(string $type = 'form', string $mode = 'modal'): FormBuilder
    {
        $controller = $this->getMockBuilder(Controller::class)->disableOriginalConstructor()->getMock();
        return new FormBuilder($type, $mode, $controller);
    }

    private function invokePrivate(object $object, string $method): mixed
    {
        $ref = new \ReflectionMethod($object, $method);
        $ref->setAccessible(true);
        return $ref->invoke($object);
    }
}
