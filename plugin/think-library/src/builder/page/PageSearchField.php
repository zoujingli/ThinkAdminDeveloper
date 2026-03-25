<?php

declare(strict_types=1);

namespace think\admin\builder\page;

use think\admin\builder\base\BuilderAttributeBag;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 页面搜索字段对象.
 * @class PageSearchField
 */
class PageSearchField
{
    private ?int $index = null;

    /**
     * @param array<string, mixed> $field
     */
    public function __construct(private PageBuilder $builder, private array $field)
    {
    }

    public function attach(int $index, array $field): self
    {
        $this->index = $index;
        $this->field = $field;
        return $this;
    }

    public function type(string $type): self
    {
        $this->field['type'] = trim($type);
        return $this->sync();
    }

    public function name(string $name): self
    {
        $this->field['name'] = trim($name);
        return $this->sync();
    }

    public function label(string $label): self
    {
        $this->field['label'] = $label;
        return $this->sync();
    }

    public function placeholder(string $placeholder): self
    {
        $this->field['placeholder'] = $placeholder;
        return $this->sync();
    }

    public function source(string $source): self
    {
        return $this->optionsItem()->source($source)->end();
    }

    public function options(array $options): self
    {
        return $this->optionsItem()->options($options)->end();
    }

    public function option(string|int $value, string $label): self
    {
        return $this->optionsItem()->option($value, $label)->end();
    }

    public function optionsItem(): PageSearchOptions
    {
        $options = is_array($this->field['options'] ?? null) ? $this->field['options'] : [];
        $source = trim(strval($this->field['source'] ?? ''));
        return (new PageSearchOptions($this, $options, $source))
            ->attach(fn(array $state): array => $this->replaceOptionsState($state));
    }

    public function attrs(array $attrs): self
    {
        $merged = is_array($this->field['attrs'] ?? null) ? $this->field['attrs'] : [];
        foreach ($attrs as $name => $value) {
            if (is_string($name) && trim($name) !== '') {
                $merged[trim($name)] = $value;
            }
        }
        $this->field['attrs'] = $merged;
        return $this->sync();
    }

    public function attr(string $name, mixed $value = null): self
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }
        $attrs = is_array($this->field['attrs'] ?? null) ? $this->field['attrs'] : [];
        $attrs[$name] = $value;
        $this->field['attrs'] = $attrs;
        return $this->sync();
    }

    public function attrsItem(): BuilderAttributeBag
    {
        $attrs = is_array($this->field['attrs'] ?? null) ? $this->field['attrs'] : [];
        $class = trim(strval($this->field['class'] ?? ''));
        return (new BuilderAttributeBag($this, $attrs, true, $class))
            ->attach(fn(array $state): array => $this->replaceAttributeState($state));
    }

    public function value(string $value): self
    {
        return $this->attr('value', $value);
    }

    public function class(string|array $class): self
    {
        $this->field['class'] = BuilderAttributes::mergeClassNames(strval($this->field['class'] ?? ''), $class);
        return $this->sync();
    }

    public function wrapClass(string|array $class): self
    {
        $this->field['wrapClass'] = BuilderAttributes::mergeClassNames(strval($this->field['wrapClass'] ?? ''), $class);
        return $this->sync();
    }

    /**
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return $this->field;
    }

    private function sync(): self
    {
        if ($this->index !== null) {
            $this->field = $this->builder->replaceSearchField($this->index, $this->field);
        }
        return $this;
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function replaceOptionsState(array $state): array
    {
        $this->field['options'] = is_array($state['options'] ?? null) ? $state['options'] : [];
        $this->field['source'] = trim(strval($state['source'] ?? ''));
        if ($this->index !== null) {
            $this->field = $this->builder->replaceSearchField($this->index, $this->field);
        }
        return [
            'options' => is_array($this->field['options'] ?? null) ? $this->field['options'] : [],
            'source' => trim(strval($this->field['source'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    private function replaceAttributeState(array $state): array
    {
        $this->field['attrs'] = is_array($state['attrs'] ?? null) ? BuilderAttributes::make($state['attrs'])->all() : [];
        $this->field['class'] = trim(strval($state['class'] ?? $this->field['class'] ?? ''));
        if ($this->index !== null) {
            $this->field = $this->builder->replaceSearchField($this->index, $this->field);
        }
        return [
            'attrs' => is_array($this->field['attrs'] ?? null) ? $this->field['attrs'] : [],
            'class' => trim(strval($this->field['class'] ?? '')),
        ];
    }
}
