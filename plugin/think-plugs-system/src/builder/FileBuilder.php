<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\form\FormBuilder;
use think\admin\builder\form\module\FormModules;
use think\admin\builder\page\PageBuilder;

/**
 * 系统文件页面构建器.
 * @class FileBuilder
 */
class FileBuilder
{
    /**
     * 构建文件编辑表单。
     * @param array<string, mixed> $context
     */
    public static function buildEditForm(array $context = []): FormBuilder
    {
        return FormBuilder::dialogForm()
            ->define(function ($form) use ($context) {
                $form->action(strval($context['actionUrl'] ?? ''))
                    ->class('system-file-form');

                FormModules::section($form, [
                    'title' => '基础信息',
                    'description' => '这里只允许维护文件名称，文件大小、存储驱动和哈希值均来自上传记录，不允许手工修改。',
                ], function ($section) {
                    $section->fields(function ($fields) {
                        $fields->text('name', 'File Name', 'Name', true, '文件名称用于后台检索和人工识别。', null, [
                            'maxlength' => 100,
                            'required-error' => 'File name is required.',
                        ])->text('size_display', 'File Size', 'Size', false, '文件大小由上传结果自动计算。', null, [
                            'readonly' => null,
                            'class' => 'layui-bg-gray',
                        ])->text('type_display', 'Storage Driver', 'Type', false, '当前文件的存储驱动。', null, [
                            'readonly' => null,
                            'class' => 'layui-bg-gray',
                        ]);
                    });
                });

                FormModules::section($form, [
                    'title' => '存储信息',
                    'description' => '文件哈希和文件地址用于上传对账与资源定位，仅用于查看。',
                ], function ($section) {
                    $section->fields(function ($fields) {
                        $fields->text('hash', 'File Hash', 'Hash', false, '文件哈希用于去重与秒传校验。', null, [
                            'readonly' => null,
                            'class' => 'layui-bg-gray',
                        ])->text('storage_key', 'Storage Key', 'Key', false, '存储键名用于定位对象存储或本地路径。', null, [
                            'readonly' => null,
                            'class' => 'layui-bg-gray',
                        ])->text('file_url', 'File URL', 'Link', false, '文件链接由存储驱动生成。', null, [
                            'readonly' => null,
                            'class' => 'layui-bg-gray',
                        ]);
                    });
                });

                $form->actions(function ($actions) {
                    $actions->submit()->cancel();
                });
            })
            ->build();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $types = is_array($context['types'] ?? null) ? $context['types'] : [];
        $extensions = is_array($context['extensions'] ?? null) ? $context['extensions'] : [];

        return PageBuilder::tablePage()
            ->define(function ($page) use ($requestBaseUrl, $types, $extensions) {
                SystemListPage::apply($page, '系统文件管理', $requestBaseUrl)
                    ->buttons(function ($buttons) {
                        $buttons->action('清理重复', url('distinct')->build(), '', '', ['data-table-id' => 'FileTable'], 'distinct')
                            ->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定删除这些记录吗？', [], 'remove');
                    });

                $page->tabsList(SystemListTabs::single('系统文件'), 'FileTable', sysuri('index'), function ($search) use ($types, $extensions) {
                    $search->input('name', '文件名称', '请输入文件名称')
                        ->input('hash', '文件哈希', '请输入文件哈希')
                        ->select('extension', '文件后缀', self::normalizeOptions($extensions), ['lay-search' => null])
                        ->select('type', '存储方式', $types, ['lay-search' => null])
                        ->dateRange('create_time', '创建时间', '请选择创建时间');
                }, function ($table) {
                    $table->options([
                        'even' => true,
                        'height' => 'full',
                        'sort' => ['field' => 'id', 'type' => 'desc'],
                    ])->checkbox()
                        ->line(1)
                        ->column(SystemTablePreset::idColumn())
                        ->column(['field' => 'name', 'title' => '文件名称', 'minWidth' => 140, 'align' => 'center'])
                        ->column(['field' => 'hash', 'title' => '文件哈希', 'minWidth' => 180, 'align' => 'center', 'templet' => '<div><code>{{d.hash}}</code></div>'])
                        ->column(['field' => 'size', 'title' => '文件大小', 'align' => 'center', 'minWidth' => 100, 'sort' => true, 'templet' => '<div>{{-$.formatFileSize(d.size)}}</div>'])
                        ->column(['field' => 'extension', 'title' => '文件后缀', 'align' => 'center', 'minWidth' => 90, 'sort' => true])
                        ->column(SystemTablePreset::filePreviewColumn('file_url'))
                        ->column(['field' => 'is_fast_upload', 'title' => '上传方式', 'align' => 'center', 'minWidth' => 100, 'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    return d.is_fast_upload ? '<b class="color-green">秒传</b>' : '<b class="color-blue">普通</b>';
}
SCRIPT)])
                        ->column(['field' => 'ctype', 'title' => '存储方式', 'align' => 'center', 'minWidth' => 120])
                        ->column(SystemTablePreset::timeColumn())
                        ->rows(function ($rows) {
                            $rows->modal('编 辑', url('edit')->build() . '?id={{d.id}}', '编辑文件信息', [], 'edit')
                                ->action('删 除', url('remove')->build(), 'id#{{d.id}}', '', ['class' => 'layui-btn-danger'], 'remove');
                        })
                        ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板'));
                });
            })
            ->build();
    }

    /**
     * @param array<int, string> $items
     * @return array<string, string>
     */
    private static function normalizeOptions(array $items): array
    {
        $options = [];
        foreach ($items as $item) {
            $item = trim((string)$item);
            if ($item !== '') {
                $options[$item] = $item;
            }
        }
        return $options;
    }
}
