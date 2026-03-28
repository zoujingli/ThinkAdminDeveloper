<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\page\PageNode;

/**
 * 键值栅格组件。
 * @class KvGridComponent
 */
class KvGridComponent extends AbstractPageComponent
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

    public function item(string $label, string $value): static
    {
        $this->items[] = ['label' => $label, 'value' => $value];
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

    public function class(string|array $class): static
    {
        $this->appendClass($this->config, 'class', $class);
        return $this;
    }

    public function mount(PageNode $parent): PageNode
    {
        $wrap = $parent->div()->class(trim(strval($this->config['class'] ?? 'ta-kv')));
        foreach ($this->items as $item) {
            $row = $wrap->div()->class(trim(strval($this->config['item_class'] ?? 'ta-kv-item')));
            $row->node('span')->class(trim(strval($this->config['label_class'] ?? 'ta-kv-label')))->text($this->text(strval($item['label'] ?? '')));
            $row->node('span')->class(trim(strval($this->config['value_class'] ?? 'ta-kv-value')))->text($this->text(strval($item['value'] ?? '')));
        }
        return $wrap;
    }
}
