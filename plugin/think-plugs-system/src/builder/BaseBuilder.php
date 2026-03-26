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

namespace plugin\system\builder;

use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\module\FormModules;
use think\admin\builder\page\PageBuilder;

/**
 * 数据字典页面视图构建器.
 * @class BaseBuilder
 */
class BaseBuilder
{
    /**
     * 渲染数据字典列表页.
     * @param array<string, mixed> $context
     */
    public static function renderIndex(array $context): void
    {
        self::buildIndexPage($context)->fetch($context);
    }

    /**
     * 渲染数据字典表单页.
     * @param array<string, mixed> $context
     * @param array<string, mixed> $data
     */
    public static function renderForm(array $context, array $data): void
    {
        self::buildForm($context)->fetch(array_merge($context, ['vo' => $data]));
    }

    /**
     * 构建数据字典列表页面.
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $mode = strval($context['type'] ?? 'index');
        $baseType = strval($context['baseType'] ?? '');
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $pluginGroupOptions = is_array($context['pluginGroupOptions'] ?? null) ? $context['pluginGroupOptions'] : [];
        $typeOptions = self::buildTypeFilterOptions(is_array($context['types'] ?? null) ? $context['types'] : []);

        return PageBuilder::make()
            ->define(function ($page) use ($context, $mode, $baseType, $requestBaseUrl, $pluginGroupOptions, $typeOptions) {
                SystemListPage::apply($page, strval($context['title'] ?? '数据字典管理'), $requestBaseUrl)
                    ->buttons(function ($buttons) use ($mode) {
                        if ($mode === 'index') {
                            $buttons->modal('添加数据', url('add')->build(), '', ['data-table-id' => 'BaseTable'], 'add')
                                ->batchAction('批量禁用', url('state')->build(), 'id#{id};status#0', '确定要禁用这些数据吗？', [], 'state');
                        } else {
                            $buttons->batchAction('批量恢复', url('state')->build(), 'id#{id};status#1', '确定要恢复这些数据吗？', [], 'state')
                                ->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定要永久删除这些数据吗？', [], 'remove');
                        }
                    });

                $params = $baseType !== '' ? ['base_type' => $baseType] : [];
                $page->tabsList(SystemListTabs::indexRecycleByParams($mode, lang('数据字典'), $params, $params), 'BaseTable', $requestBaseUrl, function ($search) use ($mode, $pluginGroupOptions, $typeOptions) {
                    $search->hidden('type', $mode)
                        ->input('code', '数据编码', '请输入数据编码')
                        ->input('name', '数据名称', '请输入数据名称')
                        ->select('base_type', '数据类型', $typeOptions)
                        ->select('plugin_group', '所属插件', $pluginGroupOptions)
                        ->dateRange('create_time', '创建时间', '请选择创建时间');
                }, function ($table) use ($requestBaseUrl, $mode) {
                    $table->options([
                        'even' => true,
                        'height' => 'full',
                        'sort' => ['field' => 'sort desc,id', 'type' => 'asc'],
                    ])->checkbox()
                        ->sortInput($requestBaseUrl)
                        ->column(SystemTablePreset::textColumn('code', '数据编码', 150, 'left'))
                        ->column(SystemTablePreset::textColumn('type', '数据类型', 120, 'left'))
                        ->column(SystemTablePreset::textColumn('name', '数据名称', 180, 'left'))
                        ->column(SystemTablePreset::pluginColumn())
                        ->statusSwitch(url('state')->build(), SystemTablePreset::statusOptions('数据状态'))
                        ->column(SystemTablePreset::timeColumn())
                        ->rows(function ($rows) use ($mode) {
                            $rows->modal('编辑', url('edit')->build() . '?id={{d.id}}', '编辑数据', [], 'edit');
                            if ($mode === 'recycle') {
                                $rows->action('删除', url('remove')->build(), 'id#{{d.id}}', '确定要永久删除数据吗？', [], 'remove');
                            }
                        })
                        ->toolbar('数据操作', SystemTablePreset::toolbar('数据操作', 120));
                });
            })
            ->build();
    }

    /**
     * 构建数据字典表单.
     * @param array<string, mixed> $context
     */
    public static function buildForm(array $context): FormBuilder
    {
        $isEdit = !empty($context['isEdit']);
        $types = is_array($context['types'] ?? null) ? $context['types'] : [];

        return FormBuilder::make()
            ->define(function ($form) use ($context, $isEdit, $types) {
                $form->action(strval($context['actionUrl'] ?? ''))
                    ->class('system-base-form');

                FormModules::intro($form, [
                    'title' => $isEdit ? '编辑数据字典' : '新增数据字典',
                    'description' => '统一维护字典类型、数据编码、名称与插件归属，保存后会同步进入系统字典能力。',
                ]);

                FormModules::section($form, [
                    'title' => '基础信息',
                    'description' => '先确认数据类型和编码，再补充名称、插件归属与实际内容。',
                ], function ($section) use ($isEdit, $types, $context) {
                    $section->fields(function ($fields) use ($isEdit, $types, $context) {
                        if ($isEdit) {
                            $fields->text('type', '数据类型', 'Data Type', true, '请选择数据类型，数据创建后不能再次修改哦~', null, [
                                'readonly' => null,
                                'class' => 'think-bg-gray',
                            ]);
                        } else {
                            $fields->select('type_select', '数据类型', 'Data Type', false, '请选择数据类型，数据创建后不能再次修改哦~', self::buildTypeOptions($types), '', [
                                'lay-filter' => 'BaseTypeSelect',
                            ])->text('type', '数据类型', 'Data Type', true, '请输入新的数据类型，数据创建后不能再次修改哦 ~', null, [
                                'maxlength' => 20,
                                'placeholder' => '请输入数据类型',
                            ]);
                        }
                        $fields->text('code', '数据编码', 'Data Code', true, '请输入新的数据编码，数据创建后不能再次修改，同种数据类型的数据编码不能出现重复 ~', null, $isEdit ? [
                            'maxlength' => 100,
                            'readonly' => null,
                            'class' => 'think-bg-gray',
                        ] : ['maxlength' => 100])
                            ->text('name', '数据名称', 'Data Name', true, '请输入当前数据名称，请尽量保持名称的唯一性，数据名称尽量不要出现重复 ~', null, ['maxlength' => 500])
                            ->select('plugin_code', '所属插件', 'Plugin Scope', false, '可选。选择后会写入插件归属元数据，适合身份权限或插件专用字典项。', is_array($context['pluginOptions'] ?? null) ? $context['pluginOptions'] : [])
                            ->textarea('content_text', '数据内容', 'Data Content', false, '', ['placeholder' => '请输入数据内容']);
                    });
                });
                if (!$isEdit) {
                    $form->script(<<<'SCRIPT'
var $typeSelect = $('[name="type_select"]');
var $typeField = $('[data-field-name="type"]');
function syncBaseTypeField(value) {
    if (value === '--- 新增类型 ---') {
        $typeField.removeClass('layui-hide').find('input').val('').focus();
    } else {
        $typeField.addClass('layui-hide').find('input').val(value || '');
    }
}
syncBaseTypeField($typeSelect.val());
layui.form.on('select(BaseTypeSelect)', function (data) {
    syncBaseTypeField(data.value);
});
SCRIPT);
                }
                $form->actions(function ($actions) {
                    $actions->submit()->cancel();
                });
            })
            ->build();
    }

    /**
     * 构建类型选项.
     * @param array<int, string> $types
     * @return array<string, string>
     */
    public static function buildTypeOptions(array $types): array
    {
        $typeOptions = [];
        foreach ($types as $type) {
            $typeOptions[$type] = $type;
        }
        $typeOptions['--- 新增类型 ---'] = '--- 新增类型 ---';
        return $typeOptions;
    }

    /**
     * @param array<int, string> $types
     * @return array<string, string>
     */
    private static function buildTypeFilterOptions(array $types): array
    {
        $options = ['' => '全部类型'];
        foreach ($types as $type) {
            $options[$type] = $type;
        }
        return $options;
    }

}
