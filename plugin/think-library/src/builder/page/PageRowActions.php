<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面行操作定义器.
 * @class PageRowActions
 */
class PageRowActions
{
    public function __construct(private PageBuilder $builder, private ?PageTable $table = null)
    {
    }

    /**
     * @param array<string, mixed> $action
     */
    public function create(array $action = []): PageAction
    {
        return $this->builder->createRowAction($action);
    }

    /**
     * @param array<string, mixed>|PageAction $action
     */
    public function append(array|PageAction $action): PageAction
    {
        $item = $action instanceof PageAction ? $action : $this->create($action);
        if ($this->table instanceof PageTable) {
            return $this->table->attachRowAction($item);
        }
        return $this->active() ? $this->builder->attachRowAction($item) : $item;
    }

    public function modal(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        if ($this->table instanceof PageTable) {
            $this->table->addRowModalAction($label, $url, $title, $attrs, $auth);
        } elseif ($this->active()) {
            $this->builder->addRowModalAction($label, $url, $title, $attrs, $auth);
        }
        return $this;
    }

    public function open(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        if ($this->table instanceof PageTable) {
            $this->table->addRowOpenAction($label, $url, $title, $attrs, $auth);
        } elseif ($this->active()) {
            $this->builder->addRowOpenAction($label, $url, $title, $attrs, $auth);
        }
        return $this;
    }

    public function action(string $label, string $url, string $value = 'id#{{d.id}}', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        if ($this->table instanceof PageTable) {
            $this->table->addRowActionButton($label, $url, $value, $confirm, $attrs, $auth);
        } elseif ($this->active()) {
            $this->builder->addRowActionButton($label, $url, $value, $confirm, $attrs, $auth);
        }
        return $this;
    }

    public function html(string $html): self
    {
        if ($this->table instanceof PageTable) {
            $this->table->addRowActionHtml($html);
        } elseif ($this->active()) {
            $this->builder->addRowActionHtml($html);
        }
        return $this;
    }

    public function button(string $label, array $attrs = [], ?string $auth = null, string $tag = 'a'): self
    {
        $this->append([
            'type' => 'button',
            'label' => $label,
            'tag' => $tag,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
        return $this;
    }

    public function modalItem(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): PageAction
    {
        return $this->append([
            'type' => 'modal',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function openItem(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): PageAction
    {
        return $this->append([
            'type' => 'open',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function actionItem(string $label, string $url, string $value = 'id#{{d.id}}', string $confirm = '', array $attrs = [], ?string $auth = null): PageAction
    {
        return $this->append([
            'type' => 'action',
            'label' => $label,
            'url' => $url,
            'value' => $value,
            'confirm' => $confirm,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    /**
     * @param array<string, mixed> $schema
     */
    public function htmlItem(string $html, array $schema = []): PageAction
    {
        return $this->append(array_merge($schema, ['type' => 'html', 'html' => $html]));
    }

    public function buttonItem(string $label, array $attrs = [], ?string $auth = null, string $tag = 'a'): PageAction
    {
        return $this->append([
            'type' => 'button',
            'label' => $label,
            'tag' => $tag,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function item(array $action): self
    {
        if ($this->table instanceof PageTable) {
            $this->table->addRowAction($action);
        } elseif ($this->active()) {
            $this->builder->addRowAction($action);
        }
        return $this;
    }

    private function active(): bool
    {
        return $this->table === null || $this->builder->isActiveTableNode($this->table);
    }
}
