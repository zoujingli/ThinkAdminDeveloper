<?php

declare(strict_types=1);

namespace plugin\system\builder;

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

        return PageBuilder::make()
            ->define(function ($page) use ($requestBaseUrl, $users, $actions) {
                $page->title('系统日志管理')
                    ->contentClass('')
                    ->showSearchLegend(false)
                    ->searchAttrs(['action' => $requestBaseUrl])
                    ->buttons(function ($buttons) use ($requestBaseUrl) {
                        $buttons->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定要删除选中的日志吗？', [], 'remove')
                            ->action('清空数据', url('clear')->build(), '', '确定要清空所有日志吗？', [], 'clear')
                            ->html(sprintf('<button type="button" data-form-export="%s" class="layui-btn layui-btn-sm layui-btn-primary"><i class="layui-icon layui-icon-export"></i> 导 出</button>', $requestBaseUrl));
                    });

                $page->tabsList(SystemListTabs::single('系统日志'), 'OplogTable', $requestBaseUrl, function ($search) use ($users, $actions) {
                    $search->select('username', '操作账号', self::normalizeOptions($users), ['lay-search' => null])
                        ->select('action', '操作行为', self::normalizeOptions($actions), ['lay-search' => null])
                        ->input('node', '操作节点', '请输入操作节点')
                        ->input('content', '操作内容', '请输入操作内容')
                        ->input('geoip', '访问地址', '请输入访问地址')
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
                        ->column(['field' => 'geoip', 'title' => '访问地址', 'minWidth' => 110])
                        ->column(['field' => 'geoisp', 'title' => '网络服务商', 'minWidth' => 100])
                        ->column(SystemTablePreset::timeColumn())
                        ->rows(function ($rows) {
                            $rows->action('删 除', url('remove')->build(), 'id#{{d.id}}', '确认要删除这条记录吗？', ['class' => 'layui-btn-danger'], 'remove');
                        })
                        ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板', 90));
                });
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
        return <<<'SCRIPT'
$.module.use(['excel'], function (excel) {
    excel.bind(function (data) {
        data.forEach(function (item, index) {
            data[index] = [item.id, item.username, item.node, item.geoip, item.geoisp, item.action, item.content, item.create_time];
        });
        data.unshift(['ID', '操作账号', '操作节点', '访问地址', '网络服务商', '操作行为', '操作内容', '创建时间']);
        return this.withStyle(data, {A: 60, B: 80, C: 99, E: 120, G: 120});
    }, '操作日志' + layui.util.toDateString(Date.now(), '_yyyyMMdd_HHmmss'));
});
SCRIPT;
    }
}
