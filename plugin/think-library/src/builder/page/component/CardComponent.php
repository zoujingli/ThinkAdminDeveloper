<?php

declare(strict_types=1);

namespace think\admin\builder\page\component;

use think\admin\builder\page\PageNode;

/**
 * 页面卡片组件。
 * @class CardComponent
 */
class CardComponent extends AbstractPageComponent
{
    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @var null|callable(PageNode): void
     */
    private $bodyCallback = null;

    /**
     * @param array<string, mixed> $config
     */
    public function config(array $config): static
    {
        $this->config = $this->mergeConfig($this->config, $config);
        return $this;
    }

    public function title(string $title): static
    {
        $this->config['title'] = $title;
        return $this;
    }

    public function remark(string $remark): static
    {
        $this->config['remark'] = $remark;
        return $this;
    }

    public function class(string|array $class): static
    {
        $this->appendClass($this->config, 'class', $class);
        return $this;
    }

    public function headerClass(string|array $class): static
    {
        $this->appendClass($this->config, 'header_class', $class);
        return $this;
    }

    public function bodyClass(string|array $class): static
    {
        $this->appendClass($this->config, 'body_class', $class);
        return $this;
    }

    public function labelClass(string|array $class): static
    {
        $this->appendClass($this->config, 'label_class', $class);
        return $this;
    }

    /**
     * @param callable(PageNode): void $callback
     */
    public function body(callable $callback): static
    {
        $this->bodyCallback = $callback;
        return $this;
    }

    public function mount(PageNode $parent): PageNode
    {
        $node = $parent->div()->class(trim(strval($this->config['class'] ?? 'layui-card')));
        $title = trim(strval($this->config['title'] ?? ''));
        $remark = trim(strval($this->config['remark'] ?? ''));

        if ($title !== '' || $remark !== '') {
            $header = $node->div()->class(trim(strval($this->config['header_class'] ?? 'layui-card-header notselect')));
            $label = $header->node('span')->class(trim(strval($this->config['label_class'] ?? 'help-label')));
            if ($title !== '') {
                $label->node('b')->text($this->text($title));
            }
            if ($remark !== '') {
                $label->text($title === '' ? $this->text($remark) : ' (' . $this->text($remark) . ')');
            }
        }

        $body = $node->div()->class(trim(strval($this->config['body_class'] ?? 'layui-card-body')));
        if (is_callable($this->bodyCallback)) {
            ($this->bodyCallback)($body);
        }
        return $node;
    }
}
