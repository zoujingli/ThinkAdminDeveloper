<?php

declare(strict_types=1);

namespace think\admin\builder\page\module;

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
            $main->div()->class(trim(strval($config['eyebrow_class'] ?? 'color-desc fs12')))->text($eyebrow);
        }

        $title = trim(strval($config['title'] ?? ''));
        if ($title !== '') {
            $main->node('h2')->class(trim(strval($config['title_class'] ?? 'mb10 mt10')))->text($title);
        }

        $description = trim(strval($config['description'] ?? ''));
        if ($description !== '') {
            $main->div()->class(trim(strval($config['description_class'] ?? 'color-desc lh24')))->text($description);
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
        $node = $parent->div()->class(trim(strval($config['class'] ?? 'layui-card')));
        $title = trim(strval($config['title'] ?? ''));
        $remark = trim(strval($config['remark'] ?? ''));

        if ($title !== '' || $remark !== '') {
            $header = $node->div()->class(trim(strval($config['header_class'] ?? 'layui-card-header notselect')));
            $label = $header->node('span')->class(trim(strval($config['label_class'] ?? 'help-label')));
            if ($title !== '') {
                $label->node('b')->text($title);
            }
            if ($remark !== '') {
                $label->text($title === '' ? $remark : " ({$remark})");
            }
        }

        $body = $node->div()->class(trim(strval($config['body_class'] ?? 'layui-card-body')));
        if (is_callable($callback)) {
            $callback($body);
        }
        return $node;
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
            $body->div()->class(trim(strval($config['label_class'] ?? 'color-desc fs12')))->text(strval($item['label'] ?? ''));
            $body->div()->class(trim(strval($config['value_class'] ?? 'fs16 fw700 mt10')))->text(strval($item['value'] ?? ''));
        }
        return $grid;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function buttonGroup(PageNode $parent, array $items, array $config = []): PageNode
    {
        $wrap = $parent->div()->class(trim(strval($config['class'] ?? 'layui-btn-group nowrap')));
        foreach ($items as $item) {
            $tag = !empty($item['url']) ? 'a' : 'button';
            $button = $wrap->node($tag)->class(trim(strval($item['class'] ?? 'layui-btn layui-btn-sm layui-btn-primary')));
            $attrs = is_array($item['attrs'] ?? null) ? $item['attrs'] : [];
            foreach ($attrs as $name => $value) {
                $button->attr(strval($name), $value);
            }
            if ($tag === 'button') {
                $button->attr('type', strval($item['type'] ?? 'button'));
            }
            if (!empty($item['url'])) {
                $dataKey = trim(strval($item['data_key'] ?? ''));
                if ($dataKey !== '') {
                    $button->attr($dataKey, strval($item['url']));
                } else {
                    $button->attr('href', strval($item['url']));
                }
            }
            $button->text(strval($item['label'] ?? ''));
        }
        return $wrap;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function kvGrid(PageNode $parent, array $items, array $config = []): PageNode
    {
        $wrap = $parent->div()->class(trim(strval($config['class'] ?? 'ta-kv')));
        foreach ($items as $item) {
            $row = $wrap->div()->class(trim(strval($config['item_class'] ?? 'ta-kv-item')));
            $row->node('span')->class(trim(strval($config['label_class'] ?? 'ta-kv-label')))->text(strval($item['label'] ?? ''));
            $row->node('span')->class(trim(strval($config['value_class'] ?? 'ta-kv-value')))->text(strval($item['value'] ?? ''));
        }
        return $wrap;
    }

    /**
     * @param array<int, string> $items
     * @param array<string, mixed> $config
     */
    public static function paragraphs(PageNode $parent, array $items, array $config = []): PageNode
    {
        $wrap = $parent->div()->class(trim(strval($config['class'] ?? 'ta-desc')));
        foreach ($items as $item) {
            $wrap->node('p')->text($item);
        }
        return $wrap;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param array<string, mixed> $config
     */
    public static function keyValueTable(PageNode $parent, array $rows, array $config = []): PageNode
    {
        $wrap = $parent->div()->class(trim(strval($config['wrap_class'] ?? 'layui-table-box')));
        $table = $wrap->node('table')->class(trim(strval($config['table_class'] ?? 'layui-table')));
        $tbody = $table->node('tbody');
        foreach ($rows as $row) {
            $tr = $tbody->node('tr');
            $tr->node('th')->class(trim(strval($config['label_class'] ?? 'nowrap text-center')))->text(strval($row['label'] ?? ''));
            $cell = $tr->node('td');
            if (!empty($row['url'])) {
                $cell->node('a')->attr('target', '_blank')->attr('href', strval($row['url']))->text(strval($row['value'] ?? ''));
            } else {
                $cell->text(strval($row['value'] ?? ''));
            }
        }
        return $wrap;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param array<string, mixed> $config
     */
    public static function readonlyFields(PageNode $parent, array $items, array $config = []): PageNode
    {
        $grid = $parent->div()->class(trim(strval($config['class'] ?? 'layui-row layui-col-space15')));
        foreach ($items as $item) {
            $col = $grid->div()->class(trim(strval($config['item_class'] ?? 'layui-col-xs12 layui-col-md6')));
            $field = $col->node('label')->class(trim(strval($config['field_class'] ?? 'block')));
            $label = $field->node('span')->class(trim(strval($config['label_class'] ?? 'help-label')));
            $title = trim(strval($item['label'] ?? ''));
            $meta = trim(strval($item['meta'] ?? ''));
            if ($title !== '') {
                $label->node('b')->text($title);
            }
            if ($meta !== '') {
                $label->text($title === '' ? $meta : " {$meta}");
            }
            $wrap = $field->node('label')->class('relative block');
            $wrap->node('input')->attrs([
                'readonly' => null,
                'value' => strval($item['value'] ?? ''),
                'class' => trim(strval($item['input_class'] ?? 'layui-input layui-bg-gray')),
            ]);
            $copy = trim(strval($item['copy'] ?? ''));
            if ($copy !== '') {
                $wrap->node('a')->class(trim(strval($item['copy_class'] ?? 'layui-icon layui-icon-release input-right-icon')))->attr('data-copy', $copy);
            }
            $help = trim(strval($item['help'] ?? ''));
            if ($help !== '') {
                $field->div()->class(trim(strval($config['help_class'] ?? 'help-block')))->text($help);
            }
        }
        return $grid;
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
                'remark' => '版本 ' . strval($plugin['version_text'] ?? 'unknown'),
                'class' => trim(strval($config['card_class'] ?? 'layui-card')),
            ], function (PageNode $body) use ($plugin) {
                self::kvGrid($body, [
                    ['label' => '插件标识', 'value' => strval($plugin['code'] ?? '-')],
                    ['label' => '访问前缀', 'value' => self::joinValues((array)($plugin['prefixes'] ?? []), '、', '未暴露')],
                    ['label' => '插件包名', 'value' => strval($plugin['package'] ?? '-')],
                    ['label' => '授权协议', 'value' => strval($plugin['license_text'] ?? '-')],
                ]);
                $body->div()->class('mt10 color-desc lh24')->text(strval($plugin['description_text'] ?? ''));
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
            $box->div()->class('plugin-empty__title')->text('暂无可展示插件');
            $box->div()->class('plugin-empty__desc')->text('安装并配置菜单后，插件入口会出现在这里。');
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
        $badges->node('span')->class('plugin-card__badge plugin-card__badge--ghost')->text(strval($plugin['code'] ?? ''));

        $version = trim(strval($plugin['version'] ?? ''));
        if ($version !== '') {
            $badges->node('span')->class('plugin-card__badge')->text("v{$version}");
        }

        $main = $cover->div()->class('plugin-card__cover-main');
        $main->div()->class('plugin-card__cover-kicker')->text('插件工作台');
        $main->div()->class('plugin-card__cover-title')->text(strval($plugin['name'] ?? ''));
        $main->div()->class('plugin-card__cover-hint')->text(!empty($plugin['plugmenus']) ? '点击卡片可直接进入插件' : '当前插件未配置可见菜单');
    }

    /**
     * @param array<string, mixed> $plugin
     */
    private static function buildPluginCenterBody(PageNode $card, array $plugin): void
    {
        $body = $card->div()->class('plugin-card__body');
        $titleRow = $body->div()->class('plugin-card__title-row');
        $titleRow->div()->class('plugin-card__title')->text(strval($plugin['name'] ?? ''));

        $license = trim(strval($plugin['license'] ?? ''));
        if ($license !== '' && $license !== 'unknow') {
            $titleRow->div()->class('plugin-card__tag')->text(strtoupper($license));
        }

        $remark = trim(strval($plugin['remark'] ?? ''));
        $body->div()->class('plugin-card__desc')->text($remark !== '' ? $remark : '暂无插件说明，当前页面仅展示插件入口与管理能力。');

        $platforms = self::joinValues(is_array($plugin['platforms'] ?? null) ? $plugin['platforms'] : [], ' / ', '通用后台');
        $meta = $body->div()->class('plugin-card__meta');
        foreach ([
            ['label' => '菜单', 'value' => strval(count((array)($plugin['plugmenus'] ?? []))) . ' 项'],
            ['label' => '平台', 'value' => $platforms],
        ] as $row) {
            $metaItem = $meta->div()->class('plugin-card__meta-item');
            $metaItem->node('span')->class('plugin-card__meta-label')->text($row['label']);
            $value = $metaItem->node('span')->class('plugin-card__meta-value')->text($row['value']);
            if ($row['label'] === '平台') {
                $value->attr('title', $row['value']);
            }
        }

        $footer = $body->div()->class('plugin-card__footer');
        if (!empty($plugin['plugmenus'])) {
            $footer->node('a')->class('layui-btn layui-btn-sm plugin-card__action')
                ->attr('id', 'p' . strval($plugin['encode'] ?? ''))
                ->attr('data-href', strval($plugin['center'] ?? ''))
                ->text('进入插件');
            return;
        }

        $footer->node('span')->class('layui-btn layui-btn-sm layui-btn-disabled plugin-card__action plugin-card__action--disabled')->text('未配置菜单');
    }
}
