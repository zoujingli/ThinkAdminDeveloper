<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面搜索区定义器.
 * @class PageSearch
 */
class PageSearch extends PageNode
{
    public function __construct(PageBuilder $builder)
    {
        parent::__construct($builder, 'search', 'form');
    }

    public function legend(string $legend): self
    {
        $this->builder->setSearchLegend($legend);
        return $this;
    }

    public function showLegend(bool $show = true): self
    {
        $this->builder->withSearchLegend($show);
        return $this;
    }

    public function input(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        $this->builder->addSearchInput($name, $label, $placeholder, $attrs);
        return $this;
    }

    public function inputField(string $name, string $label, string $placeholder = '', array $attrs = []): PageSearchField
    {
        return $this->appendField($this->builder->createSearchField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]));
    }

    public function select(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): self
    {
        $this->builder->addSearchSelect($name, $label, $options, $attrs, $source);
        return $this;
    }

    public function selectField(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): PageSearchField
    {
        return $this->appendField($this->builder->createSearchField([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'attrs' => $attrs,
            'source' => $source,
        ]));
    }

    public function dateRange(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        $this->builder->addSearchDateRange($name, $label, $placeholder, $attrs);
        return $this;
    }

    public function dateRangeField(string $name, string $label, string $placeholder = '', array $attrs = []): PageSearchField
    {
        $attrs['data-date-range'] = $attrs['data-date-range'] ?? null;
        return $this->appendField($this->builder->createSearchField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]));
    }

    public function hidden(string $name, string $value = ''): self
    {
        $this->builder->addSearchHidden($name, $value);
        return $this;
    }

    public function hiddenField(string $name, string $value = ''): PageSearchField
    {
        return $this->appendField($this->builder->createSearchField([
            'type' => 'hidden',
            'name' => $name,
            'attrs' => ['value' => $value],
        ]));
    }

    public function submit(string $label = '搜 索', array $attrs = []): self
    {
        $this->builder->addSearchSubmitButton($label, $attrs);
        return $this;
    }

    public function submitField(string $label = '搜 索', array $attrs = []): PageSearchField
    {
        return $this->appendField($this->builder->createSearchField([
            'type' => 'submit',
            'label' => $label,
            'attrs' => $attrs,
        ]));
    }

    public function field(array $field): self
    {
        $this->builder->addSearchField($field);
        return $this;
    }

    /**
     * @param array<string, mixed>|PageSearchField $field
     */
    public function appendField(array|PageSearchField $field): PageSearchField
    {
        $searchField = $field instanceof PageSearchField ? $field : $this->builder->createSearchField($field);
        return $this->builder->attachSearchField($searchField);
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'type' => 'search',
            'attrs' => $this->buildAttrs(),
            'modules' => $this->modules,
        ];
    }

    protected function afterMutate(): void
    {
        $this->builder->setSearchAttrs($this->buildAttrs());
    }
}
