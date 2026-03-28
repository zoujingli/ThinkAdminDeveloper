<?php

declare(strict_types=1);

namespace think\admin\builder\page\module;

use think\admin\builder\BuilderLang;
use think\admin\builder\page\PageComponents;
use think\admin\builder\page\PageNode;

/**
 * 页面通用展示模块。
 * @class PageModules
 */
class PageModules
{
    /**
     * @param array<string, mixed> $config
     */
    public static function hero(PageNode $parent, array $config = []): PageNode
    {
        $node = $parent->section()->class(trim(strval($config['class'] ?? 'layui-card')));
        $body = $node->div()->class(trim(strval($config['body_class'] ?? 'layui-card-body')));
        $main = $body->div()->class(trim(strval($config['main_class'] ?? '')));

        $eyebrow = trim(strval($config['eyebrow'] ?? ''));
        if ($eyebrow !== '') {
            $main->div()->class(trim(strval($config['eyebrow_class'] ?? 'color-desc fs12')))->text(BuilderLang::text($eyebrow));
        }

        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $main->node('h2')->class(trim(strval($config['title_class'] ?? 'mb10 mt10')))->text(BuilderLang::text($title));
        }

        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $main->div()->class(trim(strval($config['description_class'] ?? 'color-desc lh24')))->text(BuilderLang::text($description));
        }

        $items = is_array($config['stats'] ?? null) ? $config['stats'] : [];
        if ($items !== []) {
            self::statGrid($body, $items, [
                'class' => trim(strval($config['stats_class'] ?? 'layui-row layui-col-space15 mt20')),
                'item_class' => trim(strval($config['stat_item_class'] ?? 'layui-col-xs6 layui-col-sm3')),
                'card_class' => trim(strval($config['stat_card_class'] ?? 'layui-card layui-bg-gray')),
                'body_class' => trim(strval($config['stat_body_class'] ?? 'layui-card-body')),
                'label_class' => trim(strval($config['stat_label_class'] ?? 'color-desc fs12')),
                'value_class' => trim(strval($config['stat_value_class'] ?? 'fs16 fw700 mt10')),
            ]);
        }

        return $node;
    }

    /**
     * @param array<string, mixed> $config
     * @param null|callable(PageNode): void $callback
     */
    public static function card(PageNode $parent, array $config = [], ?callable $callback = null): PageNode
    {
        $component = PageComponents::card()->config($config);
        if (is_callable($callback)) {
            $component->body($callback);
        }
        return $component->mount($parent);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function statGrid(PageNode $parent, array $items, array $config = []): PageNode
    {
        $grid = $parent->div()->class(trim(strval($config['class'] ?? 'layui-row layui-col-space15')));
        foreach ($items as $item) {
            $col = $grid->div()->class(trim(strval($config['item_class'] ?? 'layui-col-xs6 layui-col-sm3')));
            $card = $col->div()->class(trim(strval($config['card_class'] ?? 'layui-card')));
            $body = $card->div()->class(trim(strval($config['body_class'] ?? 'layui-card-body')));
            $body->div()->class(trim(strval($config['label_class'] ?? 'color-desc fs12')))->text(BuilderLang::text(strval($item['label'] ?? '')));
            $body->div()->class(trim(strval($config['value_class'] ?? 'fs16 fw700 mt10')))->text(BuilderLang::text(strval($item['value'] ?? '')));
        }
        return $grid;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function buttonGroup(PageNode $parent, array $items, array $config = []): PageNode
    {
        return PageComponents::buttonGroup($items)->config($config)->mount($parent);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function kvGrid(PageNode $parent, array $items, array $config = []): PageNode
    {
        return PageComponents::kvGrid($items)->config($config)->mount($parent);
    }

    /**
     * @param array<int, string> $items
     * @param array<string, mixed> $config
     */
    public static function paragraphs(PageNode $parent, array $items, array $config = []): PageNode
    {
        return PageComponents::paragraphs($items)->config($config)->mount($parent);
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $config
     */
    public static function keyValueTable(PageNode $parent, array $rows, array $config = []): PageNode
    {
        return PageComponents::keyValueTable($rows)->config($config)->mount($parent);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function readonlyFields(PageNode $parent, array $items, array $config = []): PageNode
    {
        return PageComponents::readonlyFields($items)->config($config)->mount($parent);
    }

    /**
     * @param array<int, array<string, mixed>> $plugins
     * @param array<string, mixed> $config
     */
    public static function pluginCards(PageNode $parent, array $plugins, array $config = []): PageNode
    {
        $grid = $parent->div()->class(trim(strval($config['class'] ?? 'layui-row layui-col-space15')));
        foreach ($plugins as $plugin) {
            $col = $grid->div()->class(trim(strval($config['item_class'] ?? 'layui-col-xs12 layui-col-md6')));
            self::card($col, [
                'title' => strval($plugin['name'] ?? ''),
                'remark' => BuilderLang::format('版本 %s', [strval($plugin['version_text'] ?? 'unknown')]),
                'class' => trim(strval($config['card_class'] ?? 'layui-card')),
            ], function (PageNode $body) use ($plugin) {
                self::kvGrid($body, [
                    ['label' => '插件标识', 'value' => strval($plugin['code'] ?? '-')],
                    ['label' => '访问前缀', 'value' => self::joinValues((array)($plugin['prefixes'] ?? []), '、', '未暴露')],
                    ['label' => '插件包名', 'value' => strval($plugin['package'] ?? '-')],
                    ['label' => '授权协议', 'value' => strval($plugin['license_text'] ?? '-')],
                ]);
                $body->div()->class('mt10 color-desc lh24')->text(BuilderLang::text(strval($plugin['description_text'] ?? '')));
            });
        }
        return $grid;
    }

    /**
     * @param array<int, array<string, mixed>> $plugins
     * @param array<string, mixed> $config
     */
    public static function pluginCenterCards(PageNode $parent, array $plugins, array $config = []): PageNode
    {
        $grid = $parent->div()->class(trim(strval($config['class'] ?? 'plugin-center-grid')));
        if ($plugins === []) {
            $empty = $grid->div()->class('plugin-center-grid__item plugin-center-grid__item--empty');
            $box = $empty->div()->class('plugin-empty');
            $box->div()->class('plugin-empty__title')->text(BuilderLang::text('暂无可展示插件'));
            $box->div()->class('plugin-empty__desc')->text(BuilderLang::text('安装并配置菜单后，插件入口会出现在这里。'));
            return $grid;
        }

        foreach ($plugins as $plugin) {
            $item = $grid->div()->class('plugin-center-grid__item');
            $card = $item->article()->class('plugin-card');
            self::buildPluginCenterCover($card, $plugin);
            self::buildPluginCenterBody($card, $plugin);
        }

        return $grid;
    }

    /**
     * @param array<int, string> $items
     */
    private static function joinValues(array $items, string $glue = '、', string $default = '-'): string
    {
        $values = [];
        foreach ($items as $item) {
            $item = trim(strval($item));
            if ($item !== '') {
                $values[] = $item;
            }
        }
        return $values === [] ? $default : join($glue, $values);
    }

    /**
     * @param array<string, mixed> $plugin
     */
    private static function buildPluginCenterCover(PageNode $card, array $plugin): void
    {
        $coverClass = 'plugin-card__cover';
        $cover = $card->div()->class(trim($coverClass . (!empty($plugin['cover']) ? ' has-cover uploadimage' : '')));
        if (!empty($plugin['cover'])) {
            $cover->attr('data-lazy-src', strval($plugin['cover']));
        }
        if (!empty($plugin['plugmenus'])) {
            $cover->attr('data-plugs-click', strval($plugin['encode'] ?? ''));
        }

        $cover->div()->class('plugin-card__cover-mask');
        $badges = $cover->div()->class('plugin-card__badges');
        $badges->node('span')->class('plugin-card__badge plugin-card__badge--ghost')->text(BuilderLang::text(strval($plugin['code'] ?? '')));

        $version = trim(strval($plugin['version'] ?? ''));
        if ($version !== '') {
            $badges->node('span')->class('plugin-card__badge')->text(BuilderLang::text("v{$version}"));
        }

        $main = $cover->div()->class('plugin-card__cover-main');
        $main->div()->class('plugin-card__cover-kicker')->text(BuilderLang::text(strval($plugin['kind_label'] ?? '插件工作台')));
        $main->div()->class('plugin-card__cover-title')->text(BuilderLang::text(strval($plugin['name'] ?? '')));
        $main->div()->class('plugin-card__cover-hint')->text(BuilderLang::text(strval($plugin['status_hint'] ?? (!empty($plugin['plugmenus']) ? '点击卡片可直接进入插件' : '当前插件未配置可见菜单'))));
    }

    /**
     * @param array<string, mixed> $plugin
     */
    private static function buildPluginCenterBody(PageNode $card, array $plugin): void
    {
        $body = $card->div()->class('plugin-card__body');
        $titleRow = $body->div()->class('plugin-card__title-row');
        $titleRow->div()->class('plugin-card__title')->text(BuilderLang::text(strval($plugin['name'] ?? '')));

        $status = trim(strval($plugin['status_label'] ?? ''));
        if ($status !== '') {
            $titleRow->div()->class('plugin-card__tag')->text(BuilderLang::text($status));
        }

        $license = trim(strval($plugin['license_text'] ?? ($plugin['license'] ?? '')));
        if ($license !== '' && strtolower($license) !== 'unknow' && $license !== '未声明') {
            $titleRow->div()->class('plugin-card__tag')->text(BuilderLang::text(strtoupper($license)));
        }

        $remark = trim(strval($plugin['remark_text'] ?? ($plugin['remark'] ?? '')));
        $body->div()->class('plugin-card__desc')->text(BuilderLang::text($remark !== '' ? $remark : '暂无插件说明，当前页面仅展示插件入口与管理能力。'));

        $platforms = trim(strval($plugin['platform_text'] ?? ''));
        if ($platforms === '') {
            $platforms = self::joinValues(is_array($plugin['platforms'] ?? null) ? $plugin['platforms'] : [], ' / ', '通用后台');
        }
        $menuCount = strval($plugin['menu_count'] ?? count((array)($plugin['plugmenus'] ?? [])));
        $meta = $body->div()->class('plugin-card__meta');
        foreach ([
            ['label' => '菜单', 'value' => $menuCount . ' 项'],
            ['label' => '平台', 'value' => $platforms],
        ] as $row) {
            $metaItem = $meta->div()->class('plugin-card__meta-item');
            $metaItem->node('span')->class('plugin-card__meta-label')->text(BuilderLang::text($row['label']));
            $value = $metaItem->node('span')->class('plugin-card__meta-value')->text(BuilderLang::text($row['value']));
            if ($row['label'] === '平台') {
                $value->attr('title', $row['value']);
            }
        }

        $footer = $body->div()->class('plugin-card__footer');
        if (!empty($plugin['plugmenus'])) {
            $footer->node('a')->class('layui-btn layui-btn-sm plugin-card__action')
                ->attr('id', 'p' . strval($plugin['encode'] ?? ''))
                ->attr('data-href', strval($plugin['center'] ?? ''))
                ->text(BuilderLang::text(strval($plugin['action_text'] ?? '进入插件')));
            return;
        }

        $footer->node('span')->class('layui-btn layui-btn-sm layui-btn-disabled plugin-card__action plugin-card__action--disabled')->text(BuilderLang::text(strval($plugin['action_text'] ?? '未配置菜单')));
    }
}
