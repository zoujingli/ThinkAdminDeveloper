<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\page\PageNode;

/**
 * 段落组件。
 * @class ParagraphsComponent
 */
class ParagraphsComponent extends AbstractPageComponent
{
    /**
     * @var array<int, string>
     */
    private array $items = [];

    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @param array<int, string> $items
     */
    public function items(array $items): static
    {
        $this->items = array_map('strval', $items);
        return $this;
    }

    public function item(string $item): static
    {
        $this->items[] = $item;
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
        $wrap = $parent->div()->class(trim(strval($this->config['class'] ?? 'ta-desc')));
        foreach ($this->items as $item) {
            $wrap->node('p')->text($this->text($item));
        }
        return $wrap;
    }
}
