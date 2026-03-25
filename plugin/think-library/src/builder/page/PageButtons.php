<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面按钮定义器.
 * @class PageButtons
 */
class PageButtons
{
    public function __construct(private PageBuilder $builder)
    {
    }

    /**
     * @param array<string, mixed> $button
     */
    public function create(array $button = []): PageAction
    {
        return $this->builder->createButtonAction($button);
    }

    /**
     * @param array<string, mixed>|PageAction $button
     */
    public function append(array|PageAction $button): PageAction
    {
        $action = $button instanceof PageAction ? $button : $this->create($button);
        return $this->builder->attachButtonAction($action);
    }

    public function modal(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addModalButton($label, $url, $title, $attrs, $auth);
        return $this;
    }

    public function open(string $label, string $url, array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addOpenButton($label, $url, $attrs, $auth);
        return $this;
    }

    public function load(string $label, string $url, array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addLoadButton($label, $url, $attrs, $auth);
        return $this;
    }

    public function batchAction(string $label, string $url, string $rule, string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addBatchActionButton($label, $url, $rule, $confirm, $attrs, $auth);
        return $this;
    }

    public function action(string $label, string $url, string $value = '', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        $this->builder->addActionButton($label, $url, $value, $confirm, $attrs, $auth);
        return $this;
    }

    public function html(string $html): self
    {
        $this->builder->addButtonHtml($html);
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

    public function openItem(string $label, string $url, array $attrs = [], ?string $auth = null): PageAction
    {
        return $this->append([
            'type' => 'open',
            'label' => $label,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function loadItem(string $label, string $url, array $attrs = [], ?string $auth = null): PageAction
    {
        return $this->append([
            'type' => 'load',
            'label' => $label,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function batchActionItem(string $label, string $url, string $rule, string $confirm = '', array $attrs = [], ?string $auth = null): PageAction
    {
        return $this->append([
            'type' => 'batch-action',
            'label' => $label,
            'url' => $url,
            'rule' => $rule,
            'confirm' => $confirm,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function actionItem(string $label, string $url, string $value = '', string $confirm = '', array $attrs = [], ?string $auth = null): PageAction
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

    public function item(array $button): self
    {
        $this->builder->addButton($button);
        return $this;
    }
}
