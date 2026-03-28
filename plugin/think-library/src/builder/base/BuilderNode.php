<?php

declare(strict_types=1);

namespace think\admin\builder\base;

use think\admin\builder\base\render\BuilderAttributes;

/**
 * Builder 通用节点基类.
 * @class BuilderNode
 */
abstract class BuilderNode
{
    /**
     * 节点属性.
     * @var array<string, mixed>
     */
    protected array $attrs = [];

    /**
     * 模块配置.
     * @var array<int, array<string, mixed>>
     */
    protected array $modules = [];

    /**
     * 子节点.
     * @var array<int, object>
     */
    protected array $children = [];

    /**
     * 原始 HTML.
     */
    protected string $html = '';

    /**
     * 父级节点.
     */
    protected ?self $parentNode = null;

    public function __construct(
        protected object $builder,
        protected string $type = 'element',
        protected string $tag = 'div'
    ) {
    }

    public function attr(string $name, mixed $value = null): static
    {
        $name = trim($name);
        if ($name !== '') {
            $this->attrs[$name] = $value;
            $this->afterMutate();
        }
        return $this;
    }

    public function removeAttr(string $name): static
    {
        $name = trim($name);
        if ($name !== '' && array_key_exists($name, $this->attrs)) {
            unset($this->attrs[$name]);
            $this->afterMutate();
        }
        return $this;
    }

    public function attrs(array $attrs): static
    {
        foreach ($attrs as $name => $value) {
            if ($name === 'class') {
                $this->class($value);
            } else {
                $this->attr(strval($name), $value);
            }
        }
        return $this;
    }

    public function class(string|array $class): static
    {
        $this->attrs = BuilderAttributes::make($this->attrs)->class($class)->all();
        $this->afterMutate();
        return $this;
    }

    public function removeClass(string|array $class): static
    {
        $this->attrs = BuilderAttributes::make($this->attrs)->removeClass($class)->all();
        $this->afterMutate();
        return $this;
    }

    public function toggleClass(string|array $class, ?bool $force = null): static
    {
        $this->attrs = BuilderAttributes::make($this->attrs)->toggleClass($class, $force)->all();
        $this->afterMutate();
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

    public function removeData(string $name): static
    {
        $name = trim($name);
        if ($name !== '') {
            $this->removeAttr('data-' . ltrim($name, '-'));
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

    public function clear(): static
    {
        foreach ($this->children as $child) {
            if ($child instanceof self) {
                $child->parentNode = null;
                $child->onDetached();
            }
            $this->onChildDetached($child);
        }
        $this->children = [];
        $this->afterMutate();
        return $this;
    }

    public function parentNode(): ?self
    {
        return $this->parentNode;
    }

    /**
     * @return array<int, object>
     */
    public function children(): array
    {
        return $this->children;
    }

    public function firstChild(): ?object
    {
        return $this->children[0] ?? null;
    }

    public function lastChild(): ?object
    {
        return $this->children[count($this->children) - 1] ?? null;
    }

    public function appendNode(object $node): object
    {
        return $this->appendChild($node);
    }

    public function prependNode(object $node): object
    {
        return $this->prependChild($node);
    }

    public function beforeNode(object $node): object
    {
        return $this->insertSibling($node, false);
    }

    public function afterNode(object $node): object
    {
        return $this->insertSibling($node, true);
    }

    public function remove(): static
    {
        return $this->detachFromParent();
    }

    /**
     * 追加子节点.
     */
    protected function appendChild(object $node): object
    {
        if (!$this->canAttachNode($node)) {
            return $node;
        }
        if ($node instanceof self) {
            $node->detachFromParent(true);
            $node->parentNode = $this;
        }
        $this->children[] = $node;
        $this->afterMutate();
        return $node;
    }

    /**
     * 前置插入子节点.
     */
    protected function prependChild(object $node): object
    {
        if (!$this->canAttachNode($node)) {
            return $node;
        }
        if ($node instanceof self) {
            $node->detachFromParent(true);
            $node->parentNode = $this;
        }
        array_unshift($this->children, $node);
        $this->afterMutate();
        return $node;
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
        $this->afterMutate();
        return ['attrs' => $this->attrs];
    }

    protected function createModule(string $name, array $config = []): BuilderModule
    {
        return new BuilderModule($name, $config, $this);
    }

    protected function attachModule(BuilderModule $module): BuilderModule
    {
        $normalized = $this->normalizeModule($module->export());
        if ($normalized['name'] === '') {
            return $module;
        }
        $index = count($this->modules);
        $this->modules[$index] = $normalized;
        $this->afterMutate();
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
            $this->afterMutate();
        }
        return $this->modules[$index] ?? $normalized;
    }

    /**
     * 导出 HTML 节点数组.
     * @return array<string, mixed>
     */
    protected function exportHtmlNode(): array
    {
        return ['type' => 'html', 'html' => $this->html];
    }

    /**
     * 导出普通节点数组.
     * @return array<string, mixed>
     */
    protected function exportElementNode(): array
    {
        return [
            'type' => $this->type,
            'tag' => $this->tag,
            'attrs' => $this->buildAttrs(),
            'modules' => $this->modules,
            'children' => $this->exportChildren(),
        ];
    }

    /**
     * 导出子节点数组.
     * @return array<int, array<string, mixed>>
     */
    public function exportChildren(): array
    {
        return array_map(static fn(object $node) => $node->export(), $this->children);
    }

    /**
     * 获取节点属性.
     * @return array<string, mixed>
     */
    protected function buildAttrs(): array
    {
        return BuilderAttributes::make($this->attrs)->modules($this->modules)->all();
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

    protected function removeChild(object $node, bool $moving = false): bool
    {
        foreach ($this->children as $index => $child) {
            if ($child === $node) {
                if ($child instanceof self) {
                    $child->parentNode = null;
                    if (!$moving) {
                        $child->onDetached();
                    }
                }
                array_splice($this->children, $index, 1);
                $this->onChildDetached($child);
                $this->afterMutate();
                return true;
            }
        }
        return false;
    }

    protected function insertSibling(object $node, bool $after = true): object
    {
        if (!$this->parentNode instanceof self) {
            return $node;
        }
        return $this->parentNode->insertChildAround($this, $node, $after);
    }

    protected function insertChildAround(object $pivot, object $node, bool $after = true): object
    {
        if (!$this->canAttachNode($node)) {
            return $node;
        }
        foreach ($this->children as $index => $child) {
            if ($child === $pivot) {
                if ($node instanceof self) {
                    $node->detachFromParent(true);
                    $node->parentNode = $this;
                }
                array_splice($this->children, $after ? $index + 1 : $index, 0, [$node]);
                $this->afterMutate();
                return $node;
            }
        }
        return $after ? $this->appendChild($node) : $this->prependChild($node);
    }

    /**
     * 节点变更后同步.
     */
    protected function afterMutate(): void
    {
    }

    protected function onDetached(): void
    {
    }

    protected function onChildDetached(object $child): void
    {
    }

    protected function detachFromParent(bool $moving = false): static
    {
        if ($this->parentNode instanceof self) {
            $this->parentNode->removeChild($this, $moving);
        }
        return $this;
    }

    private function canAttachNode(object $node): bool
    {
        return !$node instanceof self || ($node !== $this && !$this->isDescendantOf($node));
    }

    private function isDescendantOf(self $node): bool
    {
        $parent = $this->parentNode;
        while ($parent instanceof self) {
            if ($parent === $node) {
                return true;
            }
            $parent = $parent->parentNode;
        }
        return false;
    }
}
