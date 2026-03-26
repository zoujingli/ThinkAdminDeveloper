<?php

declare(strict_types=1);

namespace plugin\system\builder;

use think\admin\builder\page\PageBuilder;
use think\admin\builder\page\PageNode;
use think\admin\builder\page\module\PageModules;

/**
 * 插件中心页面构建器。
 * @class PluginBuilder
 */
class PluginBuilder
{
    /**
     * @param array<string, mixed> $context
     */
    public static function buildIndexPage(array $context): PageBuilder
    {
        $items = array_values(is_array($context['items'] ?? null) ? $context['items'] : []);

        return PageBuilder::make()
            ->define(function ($page) use ($items) {
                $page->title('插件应用中心')
                    ->contentClass('')
                    ->buttons(function ($buttons) {
                        $buttons->modal('中心配置', sysuri('system/config/system'), '中心配置');
                    });

                PageModules::card($page, [
                    'title' => '插件中心',
                    'remark' => '统一查看已安装插件并进入工作台',
                    'class' => 'layui-card mb15',
                ], function (PageNode $body) use ($items) {
                    PageModules::paragraphs($body, [
                        '统一查看已安装插件，手动进入可用工作台，并在系统参数中管理启用状态与菜单展示策略。',
                    ], ['class' => 'ta-desc mt0']);

                    PageModules::kvGrid($body, [
                        ['label' => '插件数量', 'value' => strval(count($items))],
                        ['label' => '进入方式', 'value' => '手动选择'],
                    ]);
                });

                PageModules::card($page, [
                    'title' => '插件列表',
                    'class' => 'layui-card',
                ], function (PageNode $body) use ($items) {
                    PageModules::pluginCenterCards($body, $items);
                });

                $page->script(<<<'SCRIPT'
$(function () {
    $('body').off('click', '[data-plugs-click]').on('click', '[data-plugs-click]', function () {
        $('#p' + (this.dataset.plugsClick || 'plugin-encode')).trigger('click');
    });
});
SCRIPT);
            })
            ->build();
    }

    public static function buildDisabledPage(): PageBuilder
    {
        return PageBuilder::make()
            ->define(function ($page) {
                $page->title('插件应用中心')
                    ->contentClass('')
                    ->buttons(function ($buttons) {
                        $buttons->modal('系统参数配置', sysuri('system/config/system'), '系统参数配置');
                    });

                PageModules::card($page, [
                    'title' => '插件中心状态',
                    'class' => 'layui-card',
                ], function (PageNode $body) {
                    $notice = $body->div()->class('think-box-notify')->attr('type', 'info');
                    $notice->node('b')->text('插件中心已禁用');
                    $notice->node('span')->text('当前入口已并入系统管理，可在“系统参数配置”中重新启用或隐藏菜单。');
                });
            })
            ->build();
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function buildErrorPage(array $context): PageBuilder
    {
        $content = trim(strval($context['content'] ?? ''));
        $returnUrl = strval($context['returnUrl'] ?? sysuri('system/plugin/index'));

        return PageBuilder::make()
            ->define(function ($page) use ($content, $returnUrl) {
                $page->title('插件页面暂时无法打开')
                    ->contentClass('')
                    ->buttons(function ($buttons) use ($returnUrl) {
                        $buttons->open('返回插件中心', $returnUrl);
                    });

                $page->div(function (PageNode $body) use ($content) {
                    $body->div()->class('color-desc lh24')->text($content === '' ? '当前插件页面暂时不可用，请稍后再试。' : $content);
                })->class('ta-shell-page');
            })
            ->build();
    }
}
