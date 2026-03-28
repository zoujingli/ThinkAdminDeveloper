<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\page\PageNode;

/**
 * 只读字段组件。
 * @class ReadonlyFieldsComponent
 */
class ReadonlyFieldsComponent extends AbstractPageComponent
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $items = [];

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function config(array $config): static
    {
        $this->config = $this->mergeConfig($this->config, $config);
        return $this;
    }

    public function mount(PageNode $parent): PageNode
    {
        $grid = $parent->div()->class(trim(strval($this->config['class'] ?? 'layui-row layui-col-space15')));
        foreach ($this->items as $item) {
            $col = $grid->div()->class(trim(strval($this->config['item_class'] ?? 'layui-col-xs12 layui-col-md6')));
            $field = $col->node('label')->class(trim(strval($this->config['field_class'] ?? 'block')));
            $label = $field->node('span')->class(trim(strval($this->config['label_class'] ?? 'help-label')));
            $title = trim(strval($item['label'] ?? ''));
            $meta = trim(strval($item['meta'] ?? ''));
            if ($title !== '') {
                $label->node('b')->text($this->text($title));
            }
            if ($meta !== '') {
                $label->text($title === '' ? $this->text($meta) : ' ' . $this->text($meta));
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
                $field->div()->class(trim(strval($this->config['help_class'] ?? 'help-block')))->text($this->text($help));
            }
        }
        return $grid;
    }
}
