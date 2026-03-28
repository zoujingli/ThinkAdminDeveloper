<?php

declare(strict_types=1);

namespace think\admin\builder\form;

use think\admin\builder\base\BuilderNode;
use think\admin\builder\base\render\BuilderAttributes;
use think\admin\builder\form\component\FormComponentInterface;

/**
 * 表单节点定义器.
 * @class FormNode
 */
class FormNode extends BuilderNode
{
    /**
     * 默认动作条节点.
     */
    protected ?FormActionBar $actionBar = null;

    protected function createNodeInstance(string $type = 'element', string $tag = 'div'): self
    {
        return new self($this->builder, $type, $tag);
    }

    public function html(string $html): static
    {
        $child = $this->createNodeInstance('html');
        $child->html = $html;
        $this->appendChild($child);
        return $this;
    }

    public function textNode(string $text): static
    {
        return $this->html(BuilderAttributes::escape($text));
    }

    /**
     * @param (callable(FormNode): void)|null $callback
     */
    public function node(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->appendChild($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param (callable(FormNode): void)|null $callback
     */
    public function prepend(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->prependNode($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param (callable(FormNode): void)|null $callback
     */
    public function before(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->beforeNode($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param (callable(FormNode): void)|null $callback
     */
    public function after(string $tag = 'div', ?callable $callback = null): self
    {
        $child = $this->createNodeInstance('element', trim($tag) ?: 'div');
        $this->afterNode($child);
        if (is_callable($callback)) {
            $callback($child);
        }
        return $child;
    }

    /**
     * @param null|callable(FormNode): void $callback
     */
    public function component(FormComponentInterface $component, ?callable $callback = null): self
    {
        $node = $component->mount($this);
        if (is_callable($callback)) {
            $callback($node);
        }
        return $node;
    }

    public function div(?callable $callback = null): self
    {
        return $this->node('div', $callback);
    }

    public function section(?callable $callback = null): self
    {
        return $this->node('section', $callback);
    }

    public function article(?callable $callback = null): self
    {
        return $this->node('article', $callback);
    }

    public function header(?callable $callback = null): self
    {
        return $this->node('header', $callback);
    }

    public function footer(?callable $callback = null): self
    {
        return $this->node('footer', $callback);
    }

    public function fieldset(?callable $callback = null): self
    {
        return $this->node('fieldset', $callback);
    }

    public function fieldsNode(): FormFields
    {
        return new FormFields($this->builder, $this);
    }

    /**
     * @param callable(FormFields): void $callback
     */
    public function fields(callable $callback): static
    {
        $callback($this->fieldsNode());
        return $this;
    }

    public function actionsNode(): FormActions
    {
        return new FormActions($this->builder, $this->actionBar());
    }

    /**
     * @param (callable(FormActions): void)|null $callback
     */
    public function actionBar(?callable $callback = null): FormActionBar
    {
        $node = $this->actionBar ?? new FormActionBar($this->builder);
        if ($this->actionBar === null) {
            $this->appendChild($node);
            $this->actionBar = $node;
        }
        if (is_callable($callback)) {
            $callback(new FormActions($this->builder, $node));
        }
        return $node;
    }

    /**
     * @param callable(FormActions): void $callback
     */
    public function actions(callable $callback): static
    {
        $callback($this->actionsNode());
        return $this;
    }

    public function append(FormNode $node): FormNode
    {
        return $this->appendChild($node);
    }

    protected function onChildDetached(object $child): void
    {
        if ($child === $this->actionBar) {
            $this->actionBar = null;
        }
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        if ($this->type === 'html') {
            return $this->exportHtmlNode();
        }
        return $this->exportElementNode();
    }
}
