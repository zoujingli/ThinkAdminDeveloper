<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\base\BuilderAttributeBag;
use think\admin\builder\base\BuilderModule;
use think\admin\builder\base\render\BuilderAttributes;

/**
 * 表单字段局部节点.
 * @class FormFieldPart
 */
class FormFieldPart
{
    /**
     * 节点属性.
     * @var array<string, mixed>
     */
    private array $attrs = [];

    /**
     * 模块配置.
     * @var array<int, array<string, mixed>>
     */
    private array $modules = [];

    /**
     * 覆盖内容.
     */
    private string $content = '';

    public function __construct(private FormField $owner, private string $name)
    {
    }

    public function attr(string $name, mixed $value = null): static
    {
        $name = trim($name);
        if ($name !== '') {
            $this->attrs[$name] = $value;
        }
        return $this;
    }

    public function attrs(array $attrs): static
    {
        $this->attrs = BuilderAttributes::make($this->attrs)->merge($attrs)->all();
        return $this;
    }

    public function class(string|array $class): static
    {
        $this->attrs = BuilderAttributes::make($this->attrs)->class($class)->all();
        return $this;
    }

    public function data(string $name, mixed $value = null): static
    {
        $name = trim($name);
        if ($name !== '') {
            $this->attr('data-' . ltrim($name, '-'), $value);
        }
        return $this;
    }

    public function id(string $id): static
    {
        return $this->attr('id', $id);
    }

    public function attrsItem(): BuilderAttributeBag
    {
        return $this->attachAttributes($this->createAttributes());
    }

    public function module(string $name, array $config = []): static
    {
        $this->attachModule($this->createModule($name, $config));
        return $this;
    }

    public function moduleItem(string $name, array $config = []): BuilderModule
    {
        return $this->attachModule($this->createModule($name, $config));
    }

    public function text(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function html(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function end(): FormField
    {
        return $this->owner;
    }

    public function configured(): bool
    {
        return count($this->attrs) > 0 || count($this->modules) > 0 || $this->content !== '';
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'name' => $this->name,
            'attrs' => BuilderAttributes::make($this->attrs)->all(),
            'modules' => $this->modules,
            'content' => $this->content,
        ];
    }

    protected function createModule(string $name, array $config = []): BuilderModule
    {
        return new BuilderModule($name, $config, $this);
    }

    protected function createAttributes(): BuilderAttributeBag
    {
        return new BuilderAttributeBag($this, $this->attrs);
    }

    protected function attachAttributes(BuilderAttributeBag $attributes): BuilderAttributeBag
    {
        return $attributes->attach(fn(array $state): array => $this->replaceAttributes($state));
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, mixed>
     */
    protected function replaceAttributes(array $state): array
    {
        $this->attrs = is_array($state['attrs'] ?? null) ? BuilderAttributes::make($state['attrs'])->all() : [];
        return ['attrs' => $this->attrs];
    }

    protected function attachModule(BuilderModule $module): BuilderModule
    {
        $normalized = $this->normalizeModule($module->export());
        if ($normalized['name'] === '') {
            return $module;
        }
        $index = count($this->modules);
        $this->modules[$index] = $normalized;
        return $module->attach($index, $normalized, fn(int $index, array $module): array => $this->replaceModule($index, $module));
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    protected function replaceModule(int $index, array $module): array
    {
        $normalized = $this->normalizeModule($module);
        if ($normalized['name'] !== '') {
            $this->modules[$index] = $normalized;
        }
        return $this->modules[$index] ?? $normalized;
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    protected function normalizeModule(array $module): array
    {
        return [
            'name' => trim(strval($module['name'] ?? '')),
            'config' => is_array($module['config'] ?? null) ? $module['config'] : [],
        ];
    }
}
