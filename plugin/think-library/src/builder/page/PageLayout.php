<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面结构定义器.
 * @class PageLayout
 */
class PageLayout extends PageNode
{
    public function __construct(PageBuilder $builder)
    {
        parent::__construct($builder, 'root', '');
    }

    public function title(string $title): self
    {
        $this->builder->setTitle($title);
        return $this;
    }

    public function contentClass(string $class): self
    {
        $this->builder->setContentClass($class);
        return $this;
    }

    public function searchLegend(string $legend): self
    {
        $this->builder->setSearchLegend($legend);
        return $this;
    }

    public function showSearchLegend(bool $show = true): self
    {
        $this->builder->withSearchLegend($show);
        return $this;
    }

    public function searchAttrs(array $attrs): self
    {
        $this->builder->setSearchAttrs($attrs);
        return $this;
    }

    public function bootScript(string $script): self
    {
        $this->builder->addBootScript($script);
        return $this;
    }

    public function script(string $script): self
    {
        $this->builder->addScript($script);
        return $this;
    }

    public function buttonsBar(): PageButtons
    {
        return new PageButtons($this->builder);
    }

    /**
     * @param callable(PageButtons): void $callback
     */
    public function buttons(callable $callback): self
    {
        $callback($this->buttonsBar());
        return $this;
    }

    /**
     * @param (callable(PageSearch): void)|null $callback
     */
    public function searchArea(?callable $callback = null): PageSearch
    {
        return $this->searchNode($callback);
    }

    /**
     * @param callable(PageSearch): void $callback
     */
    public function search(callable $callback): self
    {
        $this->searchArea($callback);
        return $this;
    }

    /**
     * @param (callable(PageTable): void)|null $callback
     */
    public function tableArea(string $id = 'PageDataTable', ?string $url = null, ?callable $callback = null, array $attrs = []): PageTable
    {
        return $this->tableNode($id, $url, $callback, $attrs);
    }

    /**
     * @param (callable(PageTable): void)|null $callback
     */
    public function table(string $id = 'PageDataTable', ?string $url = null, ?callable $callback = null, array $attrs = []): self
    {
        $this->tableArea($id, $url, $callback, $attrs);
        return $this;
    }

    /**
     * @param null|callable(PageSearch): void $searchCallback
     * @param null|callable(PageTable): void $tableCallback
     */
    public function tabsList(
        string $tabsHtml,
        string $tableId = 'PageDataTable',
        ?string $tableUrl = null,
        ?callable $searchCallback = null,
        ?callable $tableCallback = null,
        array $tableAttrs = ['class' => 'mt10'],
        array $tabsAttrs = []
    ): self {
        $this->tabsListCard($tabsHtml, $tableId, $tableUrl, $searchCallback, $tableCallback, $tableAttrs, $tabsAttrs);
        return $this;
    }
}
