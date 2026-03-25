<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面行操作定义器.
 * @class PageRowActions
 */
class PageRowActions
{
    public function __construct(private PageBuilder $builder)
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
        return $this->builder->attachRowAction($item);
    }

    public function modal(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addRowModalAction($label, $url, $title, $attrs, $auth);
        return $this;
    }

    public function open(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addRowOpenAction($label, $url, $title, $attrs, $auth);
        return $this;
    }

    public function action(string $label, string $url, string $value = 'id#{{d.id}}', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addRowActionButton($label, $url, $value, $confirm, $attrs, $auth);
        return $this;
    }

    public function html(string $html): self
    {
        $this->builder->addRowActionHtml($html);
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

    public function item(array $action): self
    {
        $this->builder->addRowAction($action);
        return $this;
    }
}
