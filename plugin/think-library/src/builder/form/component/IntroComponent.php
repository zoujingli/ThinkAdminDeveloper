<?php

declare(strict_types=1);

namespace think\admin\builder\form\component;

use think\admin\builder\form\FormNode;

/**
 * 表单引导组件。
 * @class IntroComponent
 */
class IntroComponent extends AbstractFormComponent
{
    /**
     * @var array<string, mixed>
     */
    private array $config = [];

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

    public function mount(FormNode $parent): FormNode
    {
        $node = $parent->section()->class(trim(strval($this->config['class'] ?? 'layui-card mb15')));
        $body = $node->div()->class(trim(strval($this->config['body_class'] ?? 'layui-card-body')));
        $eyebrow = trim(strval($this->config['eyebrow'] ?? ''));
        if ($eyebrow !== '') {
            $body->div()->class(trim(strval($this->config['eyebrow_class'] ?? 'color-desc fs12')))->html($this->escape($eyebrow));
        }
        $title = trim(strval($this->config['title'] ?? ''));
        if ($title !== '') {
            $body->node('h2')->class(trim(strval($this->config['title_class'] ?? 'mt10 mb10')))->html($this->escape($title));
        }
        $description = trim(strval($this->config['description'] ?? ''));
        if ($description !== '') {
            $body->div()->class(trim(strval($this->config['description_class'] ?? 'color-desc lh24')))->html($this->escape($description));
        }
        return $node;
    }
}
