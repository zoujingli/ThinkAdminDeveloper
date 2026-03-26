<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\page\PageBuilder;

/**
 * 系统任务页面构建器.
 * @class QueueBuilder
 */
class QueueBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $requestBaseUrl = strval($context['requestBaseUrl'] ?? '');
        $super = !empty($context['super']);
        $iswin = !empty($context['iswin']);
        $command = strval($context['command'] ?? '');

        return PageBuilder::make()
            ->define(function ($page) use ($requestBaseUrl, $super, $iswin, $command) {
                SystemListPage::apply($page, '系统任务管理', $requestBaseUrl)
                    ->buttons(function ($buttons) use ($super, $iswin, $command) {
                        if ($super) {
                            $buttons->button('优化数据库', [
                                'data-table-id' => 'QueueTable',
                                'data-queue' => apiuri('system/plugs/optimize'),
                            ]);
                            if ($iswin || php_sapi_name() === 'cli') {
                                $buttons->button('启动服务', [
                                    'type' => 'button',
                                    'data-queue-service' => null,
                                    'data-service-url' => apiuri('system/queue/start'),
                                ], null, 'button');
                                $buttons->button('关闭服务', [
                                    'type' => 'button',
                                    'data-queue-service' => null,
                                    'data-service-url' => apiuri('system/queue/stop'),
                                ], null, 'button');
                            }
                            $buttons->button('定时清理', [
                                'type' => 'button',
                                'data-table-id' => 'QueueTable',
                                'data-queue' => url('clean')->build(),
                            ], null, 'button');
                        }
                        $buttons->batchAction('批量删除', url('remove')->build(), 'id#{id}', '确定批量删除记录吗？', [], 'remove');
                        if ($super) {
                            $buttons->button('复制启动命令', [
                                'data-copy' => $command,
                                'class' => 'layui-btn-disabled',
                            ], null, 'span');
                        }
                    });

                $notice = $page->div();
                $notice->class('think-box-notify mb10')->attr('type', 'info');
                $html = [];
                if ($super) {
                    $html[] = '<b>服务状态：</b><b class="mr5 pointer" data-queue-message data-tips-text="点击刷新服务状态"><span class="color-desc">检查中</span></b>';
                }
                $html[] = '<b>任务统计：</b>待处理 <b class="color-text" data-extra="pre">..</b> 个任务，处理中 <b class="color-blue" data-extra="dos">..</b> 个任务，已完成 <b class="color-green" data-extra="oks">..</b> 个任务，已失败 <b class="color-red" data-extra="ers">..</b> 个任务。';
                $notice->html(implode('', $html));
                $page->tabsList(SystemListTabs::single('系统任务'), 'QueueTable', $requestBaseUrl, function ($search) {
                    $search->input('title', '编号名称', '请输入名称或编号')
                        ->input('command', '任务指令', '请输入任务指令')
                        ->select('status', '任务状态', [
                            '1' => '等待处理',
                            '2' => '正在处理',
                            '3' => '处理完成',
                            '4' => '处理失败',
                        ])
                        ->dateRange('exec_time', '计划时间', '请选择计划时间');
                }, function ($table) {
                    $table->options([
                        'even' => true,
                        'height' => 'full',
                        'sort' => ['field' => 'loops_time desc,id', 'type' => 'desc'],
                        'filter' => PageBuilder::js(<<<'SCRIPT'
function (items, result) {
    return result && result.extra && $('[data-extra]').map(function () {
        this.innerHTML = result.extra[this.dataset.extra] || 0;
    }), items;
}
SCRIPT),
                    ])->line(2)
                        ->checkbox(['fixed' => 'left'])
                        ->column(['field' => 'id', 'title' => '任务名称', 'minWidth' => 220, 'sort' => true, 'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    if (d.loops_time > 0) {
        d.one = '<span class="pull-left layui-badge layui-badge-middle think-bg-blue">循环</span>';
    } else {
        d.one = '<span class="pull-left layui-badge layui-badge-middle think-bg-red">单次</span>';
    }
    return laytpl('{{-d.one}}任务编号：<b>{{d.code}}</b><br>任务名称：{{d.title}}').render(d);
}
SCRIPT)])
                        ->column(['field' => 'exec_time', 'title' => '任务计划', 'minWidth' => 220, 'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    d.html = '执行指令：' + d.command + '<br>计划执行：' + formatQueueTime(d.exec_time, '<span class="color-desc">未计划</span>');
    if (d.loops_time > 0) {
        return d.html + ' ( 每 <b class="color-blue">' + d.loops_time + '</b> 秒 ) ';
    } else {
        return d.html + ' <span class="color-desc">( 单次任务 )</span> ';
    }
}
SCRIPT)])
                        ->column(['field' => 'loops_time', 'title' => '任务状态', 'minWidth' => 260, 'templet' => PageBuilder::js(<<<'SCRIPT'
function (d) {
    d.html = ([
        '<span class="pull-left layui-badge layui-badge-middle layui-bg-gray">未知</span>',
        '<span class="pull-left layui-badge layui-badge-middle layui-bg-black">等待</span>',
        '<span class="pull-left layui-badge layui-badge-middle layui-bg-blue">执行</span>',
        '<span class="pull-left layui-badge layui-badge-middle layui-bg-green">完成</span>',
        '<span class="pull-left layui-badge layui-badge-middle layui-bg-red">失败</span>'
    ][d.status] || '') + '执行时间：';
    if (String(d.enter_time || '') !== '' && String(d.enter_time) !== '0' && String(d.enter_time) !== '0.0000') {
        d.html += formatQueueTime(d.enter_time) + '<span class="color-desc">' + formatQueueCost(d.enter_time, d.outer_time, d.status) + '</span>';
        d.html += ' 已执行 <b class="color-blue">' + (d.attempts || 0) + '</b> 次';
    } else {
        d.html += '<span class="color-desc">任务未执行</span>';
    }
    return d.html + '<br>执行结果：<span class="color-blue">' + (d.exec_desc || '<span class="color-desc">未获取到执行结果</span>') + '</span>';
}
SCRIPT)])
                        ->rows(function ($rows) {
                            $rows->html('<!--{if auth(\'redo\')}-->{{# if(d.status===4||d.status===3){ }}<a class="layui-btn layui-btn-sm" data-confirm="确定要重置该任务吗？" data-queue="' . url('redo')->build() . '?code={{d.code}}">重置</a>{{# }else{ }}<a class="layui-btn layui-btn-sm layui-btn-disabled">重置</a>{{# } }}<!--{/if}-->')
                                ->action('删除', url('remove')->build(), 'id#{{d.id}}', '确定要删除该记录吗？', ['class' => 'layui-btn-danger'], 'remove')
                                ->button('日志', ['onclick' => "$.loadQueue('{{d.code}}',false,this)", 'class' => 'layui-btn-normal']);
                        })
                        ->toolbar('操作面板', SystemTablePreset::toolbar('操作面板', 210, ['fixed' => 'right']));
                });
                $page->script(self::renderScript($super));
            })
            ->build();
    }

    private static function renderScript(bool $super): string
    {
        $queueStatusUrl = apiuri('system/queue/status');
        $enabled = $super ? 'true' : 'false';
        return <<<SCRIPT
$(function () {
    const queueStatusUrl = '{$queueStatusUrl}';
    const queueStatusEnabled = {$enabled};
    const \$queueMessage = \$('[data-queue-message]');
    let queueStatusTimer = 0;
    let queueStatusRequest = null;

    const setQueueStatusText = function (text, color, tips) {
        if (\$queueMessage.length < 1) return;
        \$queueMessage.attr('data-tips-text', tips || text).html('<span class="' + color + '">' + text + '</span>');
    };
    const loadQueueServiceStatus = function (times, delay) {
        if (!queueStatusEnabled || \$queueMessage.length < 1) return;
        clearTimeout(queueStatusTimer);
        if (queueStatusRequest && queueStatusRequest.readyState !== 4) queueStatusRequest.abort();
        queueStatusRequest = $.ajax({
            url: $.menu.parseUri(queueStatusUrl),
            type: 'GET',
            success: function (html) {
                \$queueMessage.attr('data-tips-text', '点击刷新服务状态').html(html);
                if (times > 1) queueStatusTimer = setTimeout(function () { loadQueueServiceStatus(times - 1, delay); }, delay);
            },
            error: function (xhr) {
                let tips = '服务状态获取失败，请点击重试';
                if (xhr && xhr.status) tips = 'E' + xhr.status + ' - ' + tips;
                setQueueStatusText('状态获取失败', 'color-red', tips);
                if (times > 1) queueStatusTimer = setTimeout(function () { loadQueueServiceStatus(times - 1, Math.min(delay + 200, 1500)); }, delay);
            }
        });
    };
    window.formatQueueTime = function (value, emptyHtml) {
        const num = Number(value || 0);
        if (!isFinite(num) || num <= 0) return emptyHtml || '<span class="color-desc">未执行</span>';
        return layui.util.toDateString(Math.round(num * 1000), 'yyyy-MM-dd HH:mm:ss');
    };
    window.formatQueueCost = function (start, finish, status) {
        const begin = Number(start || 0);
        const end = Number(finish || 0);
        if (!isFinite(begin) || begin <= 0) return '';
        let cost = 0;
        let suffix = '';
        if (isFinite(end) && end > begin) cost = end - begin;
        else if (Number(status) === 2) {
            cost = Date.now() / 1000 - begin;
            suffix = '，执行中';
        }
        if (cost <= 0) return suffix;
        if (cost >= 60) return '，耗时 ' + (cost / 60).toFixed(2) + ' 分钟' + suffix;
        if (cost >= 1) return '，耗时 ' + cost.toFixed(2) + ' 秒' + suffix;
        return '，耗时 ' + Math.round(cost * 1000) + ' ms' + suffix;
    };
    $('[data-queue-service]').off('click.queue-service').on('click.queue-service', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $.base.applyRuleValue(this, {}, function (data, elem, dset) {
            setQueueStatusText('检查中', 'color-desc', '正在刷新服务状态');
            $.form.load(dset.serviceUrl, data, 'get', function (ret) {
                if (ret && Number(ret.code) > 0) loadQueueServiceStatus(6, 400);
                else loadQueueServiceStatus(2, 400);
            }, true, dset.tips, dset.time);
        });
        return false;
    });
    \$queueMessage.off('click.queue-status').on('click.queue-status', function () {
        setQueueStatusText('检查中', 'color-desc', '正在刷新服务状态');
        loadQueueServiceStatus(2, 400);
        return false;
    });
    loadQueueServiceStatus(2, 400);
});
SCRIPT;
    }
}
