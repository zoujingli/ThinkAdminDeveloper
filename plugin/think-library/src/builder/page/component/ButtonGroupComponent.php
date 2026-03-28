<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\BuilderLang;
use think\admin\builder\page\PageNode;

/**
 * 按钮组组件。
 * @class ButtonGroupComponent
 */
class ButtonGroupComponent extends AbstractPageComponent
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
     * @param array<string, mixed> $item
     */
    public function item(array $item): static
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
        $wrap = $parent->div()->class(trim(strval($this->config['class'] ?? 'layui-btn-group nowrap')));
        foreach ($this->items as $item) {
            $tag = !empty($item['url']) ? 'a' : 'button';
            $button = $wrap->node($tag)->class(trim(strval($item['class'] ?? 'layui-btn layui-btn-sm layui-btn-primary')));
            foreach (BuilderLang::attrs((array)($item['attrs'] ?? [])) as $name => $value) {
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
            $button->text($this->text(strval($item['label'] ?? '')));
        }
        return $wrap;
    }
}
