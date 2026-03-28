<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 表单分区组件。
 * @class SectionComponent
 */
class SectionComponent extends AbstractFormComponent
{
    /**
     * @var array<string, mixed>
     */
    private array $config = [];

    /**
     * @var null|callable(FormNode): void
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

    public function description(string $description): static
    {
        $this->config['description'] = $description;
        return $this;
    }

    public function class(string|array $class): static
    {
        $this->appendClass($this->config, 'class', $class);
        return $this;
    }

    /**
     * @param callable(FormNode): void $callback
     */
    public function body(callable $callback): static
    {
        $this->bodyCallback = $callback;
        return $this;
    }

    public function mount(FormNode $parent): FormNode
    {
        $node = $parent->section()->class(trim(strval($this->config['class'] ?? 'mb20')));
        $head = $node->div()->class(trim(strval($this->config['head_class'] ?? 'mb15')));
        $title = trim(strval($this->config['title'] ?? ''));
        if ($title !== '') {
            $head->node('h3')->class(trim(strval($this->config['title_class'] ?? 'mb5')))->html($this->escape($title));
        }
        $description = trim(strval($this->config['description'] ?? ''));
        if ($description !== '') {
            $head->node('p')->class(trim(strval($this->config['description_class'] ?? 'color-desc lh24')))->html($this->escape($description));
        }
        $body = $node->div()->class(trim(strval($this->config['body_class'] ?? '')));
        if (is_callable($this->bodyCallback)) {
            ($this->bodyCallback)($body);
        }
        return $node;
    }
}
