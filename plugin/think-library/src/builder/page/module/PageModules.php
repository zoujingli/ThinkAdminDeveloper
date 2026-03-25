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
                'class' => trim(strval($config['card_class'] ?? 'layui-card ta-plugin-card')),
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
}
