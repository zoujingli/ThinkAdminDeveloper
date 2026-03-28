<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\BuilderLang;
use think\admin\builder\page\PageBuilder;

/**
 * 系统日志页面构建器.
 * @class OplogBuilder
 */
class OplogBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $users = is_array($context['users'] ?? null) ? $context['users'] : [];
        $actions = is_array($context['actions'] ?? null) ? $context['actions'] : [];

        return PageBuilder::tablePage()
            ->define(function ($page) use ($requestBaseUrl, $users, $actions) {
                SystemListPage::apply($page, '系统日志管理', $requestBaseUrl)
                    ->buttons(function ($buttons) use ($requestBaseUrl) {
                        $buttons->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定要删除选中的日志吗？', [], 'remove')
                            ->action('清空数据', url('clear')->build(), '', '确定要清空所有日志吗？', [], 'clear')
                            ->button(sprintf('<i class="layui-icon layui-icon-export"></i> %s', self::escape(BuilderLang::text('导 出'))), [
                                'type' => 'button',
                                'data-form-export' => $requestBaseUrl,
                            ], null, 'button');
                    });

                $page->tabsList(SystemListTabs::single('系统日志'), 'OplogTable', $requestBaseUrl, function ($search) use ($users, $actions) {
                    $search->select('username', '操作账号', self::normalizeOptions($users), ['lay-search' => null])
                        ->select('action', '操作行为', self::normalizeOptions($actions), ['lay-search' => null])
                        ->input('node', '操作节点', '请输入操作节点')
                        ->input('content', '操作内容', '请输入操作内容')
                        ->input('request_ip', '访问地址', '请输入访问地址')
                        ->dateRange('create_time', '创建时间', '请选择创建时间');
                }, function ($table) {
                    $table->options([
                        'even' => true,
                        'height' => 'full',
                        'sort' => ['field' => 'id', 'type' => 'desc'],
                    ])->checkbox()
                        ->line(1)
                        ->column(SystemTablePreset::idColumn())
                        ->column(['field' => 'username', 'title' => '操作账号', 'minWidth' => 100, 'sort' => true, 'align' => 'center'])
                        ->column(['field' => 'node', 'title' => '操作节点', 'minWidth' => 120])
                        ->column(['field' => 'action', 'title' => '操作行为', 'minWidth' => 120])
                        ->column(['field' => 'content', 'title' => '操作内容', 'minWidth' => 150])
                        ->column(['field' => 'request_ip', 'title' => '访问地址', 'minWidth' => 110])
                        ->column(['field' => 'request_region', 'title' => '网络服务商', 'minWidth' => 100])
                        ->column(SystemTablePreset::timeColumn())
                        ->rows(function ($rows) {
                            $rows->action('删除', url('remove')->build(), 'id#{{d.id}}', '确认要删除这条记录吗？', [], 'remove');
                        })
                        ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板', 180, ['fixed' => 'right']));
                });
                $page->script(self::renderToolbarAlignScript());
                $page->script(self::renderExportScript());
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

    private static function renderExportScript(): string
    {
        return sprintf(<<<'SCRIPT'
$.module.use(['excel'], function (excel) {
    excel.bind(function (data) {
        data.forEach(function (item, index) {
            data[index] = [item.id, item.username, item.node, item.request_ip, item.request_region, item.action, item.content, item.create_time];
        });
        data.unshift(%s);
        return this.withStyle(data, {A: 60, B: 80, C: 99, E: 120, G: 120});
    }, %s + layui.util.toDateString(Date.now(), '_yyyyMMdd_HHmmss'));
});
SCRIPT, self::json([
            'ID',
            BuilderLang::text('操作账号'),
            BuilderLang::text('操作节点'),
            BuilderLang::text('访问地址'),
            BuilderLang::text('网络服务商'),
            BuilderLang::text('操作行为'),
            BuilderLang::text('操作内容'),
            BuilderLang::text('创建时间'),
        ]), self::json(BuilderLang::text('操作日志')));
    }

    private static function renderToolbarAlignScript(): string
    {
        return <<<'SCRIPT'
$(function () {
    if ($('#OplogToolbarAlignStyle').length > 0) return;
    $('<style id="OplogToolbarAlignStyle">#OplogTable+.layui-table-view .layui-table-body td:last-child .layui-table-cell{display:flex;align-items:center;justify-content:center;min-height:38px;padding-top:0;padding-bottom:0}#OplogTable+.layui-table-view .layui-table-body td:last-child .layui-btn{margin:0}</style>').appendTo('head');
});
SCRIPT;
    }

    /**
     * @param mixed $value
     */
    private static function json($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'null';
    }

    private static function escape(string $content): string
    {
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
}
