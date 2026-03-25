<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\base\BuilderAttributeBag;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面动作对象.
 * @class PageAction
 */
class PageAction
{
    private ?int $index = null;

    /**
     * @param array<string, mixed> $action
     */
    public function __construct(private PageBuilder $builder, private string $scope, private array $action = [])
    {
    }

    /**
     * @param array<string, mixed> $action
     */
    public function attach(int $index): self
    {
        $this->index = $index;
        return $this;
    }

    public function type(string $type): self
    {
        $this->action['type'] = trim($type);
        return $this->sync();
    }

    public function label(string $label): self
    {
        $this->action['label'] = $label;
        return $this->sync();
    }

    public function url(string $url): self
    {
        $this->action['url'] = $url;
        return $this->sync();
    }

    public function title(string $title): self
    {
        $this->action['title'] = $title;
        return $this->sync();
    }

    public function value(string $value): self
    {
        $this->action['value'] = $value;
        return $this->sync();
    }

    public function confirm(string $confirm): self
    {
        $this->action['confirm'] = $confirm;
        return $this->sync();
    }

    public function rule(string $rule): self
    {
        $this->action['rule'] = $rule;
        return $this->sync();
    }

    public function auth(?string $auth): self
    {
        $this->action['auth'] = $auth;
        return $this->sync();
    }

    public function html(string $html): self
    {
        $this->action['type'] = 'html';
        $this->action['html'] = $html;
        return $this->sync();
    }

    public function attrs(array $attrs): self
    {
        $merged = is_array($this->action['attrs'] ?? null) ? $this->action['attrs'] : [];
        foreach ($attrs as $name => $value) {
            if (is_string($name) && trim($name) !== '') {
                $merged[trim($name)] = $value;
            }
        }
        $this->action['attrs'] = $merged;
        return $this->sync();
    }

    public function attr(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }
        $attrs = is_array($this->action['attrs'] ?? null) ? $this->action['attrs'] : [];
        $attrs[$name] = $value;
        $this->action['attrs'] = $attrs;
        return $this->sync();
    }

    public function attrsItem(): BuilderAttributeBag
    {
        $attrs = is_array($this->action['attrs'] ?? null) ? $this->action['attrs'] : [];
        $class = trim(strval($this->action['class'] ?? ''));
        return (new BuilderAttributeBag($this, $attrs, true, $class))
            ->attach(fn(array $state): array => $this->replaceAttributeState($state));
    }

    public function class(string|array $class): self
    {
        $this->action['class'] = BuilderAttributes::mergeClassNames(strval($this->action['class'] ?? ''), $class);
        return $this->sync();
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return $this->action;
    }

    private function sync(): self
    {
        if ($this->index === null) {
            return $this;
        }

        if ($this->scope === 'row') {
            $this->builder->replaceRowAction($this->index, $this->action);
        } else {
            $this->builder->replaceButtonAction($this->index, $this->action);
        }

        return $this;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function replaceAttributeState(array $state): array
    {
        $this->action['attrs'] = is_array($state['attrs'] ?? null) ? BuilderAttributes::make($state['attrs'])->all() : [];
        $this->action['class'] = trim(strval($state['class'] ?? $this->action['class'] ?? ''));

        if ($this->index !== null) {
            if ($this->scope === 'row') {
                $this->action = $this->builder->replaceRowAction($this->index, $this->action);
            } else {
                $this->action = $this->builder->replaceButtonAction($this->index, $this->action);
            }
        }

        return [
            'attrs' => is_array($this->action['attrs'] ?? null) ? $this->action['attrs'] : [],
            'class' => trim(strval($this->action['class'] ?? '')),
        ];
    }
}
