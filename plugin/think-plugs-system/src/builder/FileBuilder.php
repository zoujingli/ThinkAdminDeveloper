<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\page\PageBuilder;

/**
 * 系统文件页面构建器.
 * @class FileBuilder
 */
class FileBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $types = is_array($context['types'] ?? null) ? $context['types'] : [];
        $xexts = is_array($context['xexts'] ?? null) ? $context['xexts'] : [];

        return PageBuilder::make()
            ->define(function ($page) use ($requestBaseUrl, $types, $xexts) {
                SystemListPage::apply($page, '系统文件管理', $requestBaseUrl)
                    ->buttons(function ($buttons) {
                        $buttons->action('清理重复', url('distinct')->build(), '', '', ['data-table-id' => 'FileTable'], 'distinct')
                            ->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定删除这些记录吗？', [], 'remove');
                    });

                $page->tabsList(SystemListTabs::single('系统文件'), 'FileTable', sysuri('index'), function ($search) use ($types, $xexts) {
                    $search->input('name', '文件名称', '请输入文件名称')
                        ->input('hash', '文件哈希', '请输入文件哈希')
                        ->select('xext', '文件后缀', self::normalizeOptions($xexts), ['lay-search' => null])
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
                        ->column(['field' => 'xext', 'title' => '文件后缀', 'align' => 'center', 'minWidth' => 90, 'sort' => true])
                        ->column(SystemTablePreset::filePreviewColumn())
                        ->column(['field' => 'isfast', 'title' => '上传方式', 'align' => 'center', 'minWidth' => 100, 'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    return d.isfast ? '<b class="color-green">秒传</b>' : '<b class="color-blue">普通</b>';
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
