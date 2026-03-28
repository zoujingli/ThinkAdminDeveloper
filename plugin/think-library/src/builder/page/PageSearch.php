<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面搜索区定义器.
 * @class PageSearch
 */
class PageSearch extends PageNode
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $fields = [];

    private ?string $legendText = null;

    private ?bool $legendVisible = null;

    private string $formId;

    public function __construct(PageBuilder $builder)
    {
        parent::__construct($builder, 'search', 'form');
        $this->formId = $this->builder->nextSearchFormId();
    }

    public function legend(string $legend): self
    {
        $this->legendText = $legend;
        return $this;
    }

    public function showLegend(bool $show = true): self
    {
        $this->legendVisible = $show;
        return $this;
    }

    public function input(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        $this->fields[] = $this->normalizeField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
        return $this;
    }

    public function inputField(string $name, string $label, string $placeholder = '', array $attrs = []): PageSearchField
    {
        $field = $this->createField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
        return $this->appendField($field);
    }

    public function select(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): self
    {
        $this->fields[] = $this->normalizeField([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'attrs' => $attrs,
            'source' => $source,
        ]);
        return $this;
    }

    public function selectField(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): PageSearchField
    {
        $field = $this->createField([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'attrs' => $attrs,
            'source' => $source,
        ]);
        return $this->appendField($field);
    }

    public function dateRange(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        $attrs['data-date-range'] = $attrs['data-date-range'] ?? null;
        $this->fields[] = $this->normalizeField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
        return $this;
    }

    public function dateRangeField(string $name, string $label, string $placeholder = '', array $attrs = []): PageSearchField
    {
        $attrs['data-date-range'] = $attrs['data-date-range'] ?? null;
        $field = $this->createField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
        return $this->appendField($field);
    }

    public function hidden(string $name, string $value = ''): self
    {
        $this->fields[] = $this->normalizeField([
            'type' => 'hidden',
            'name' => $name,
            'attrs' => ['value' => $value],
        ]);
        return $this;
    }

    public function hiddenField(string $name, string $value = ''): PageSearchField
    {
        $field = $this->createField([
            'type' => 'hidden',
            'name' => $name,
            'attrs' => ['value' => $value],
        ]);
        return $this->appendField($field);
    }

    public function submit(string $label = '搜 索', array $attrs = []): self
    {
        $this->fields[] = $this->normalizeField([
            'type' => 'submit',
            'label' => $label,
            'attrs' => $attrs,
        ]);
        return $this;
    }

    public function submitField(string $label = '搜 索', array $attrs = []): PageSearchField
    {
        $field = $this->createField([
            'type' => 'submit',
            'label' => $label,
            'attrs' => $attrs,
        ]);
        return $this->appendField($field);
    }

    public function field(array $field): self
    {
        $this->fields[] = $this->normalizeField($field);
        return $this;
    }

    /**
     * @param array<string, mixed>|PageSearchField $field
     */
    public function appendField(array|PageSearchField $field): PageSearchField
    {
        $searchField = $field instanceof PageSearchField ? $field : $this->createField($field);
        $index = count($this->fields);
        $this->fields[$index] = $this->normalizeField($searchField->export());
        return $searchField->attachSync(fn(array $state): array => $this->replaceField($index, $state));
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'type' => 'search',
            'formId' => $this->formId,
            'attrs' => $this->buildAttrs(),
            'modules' => $this->modules,
            'fields' => $this->fields,
            'legend' => $this->legendText,
            'legendEnabled' => $this->legendVisible,
        ];
    }

    /**
     * @param array<string, mixed> $field
     */
    private function createField(array $field): PageSearchField
    {
        return new PageSearchField($this->builder, $this->normalizeField($field));
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function normalizeField(array $field): array
    {
        return (new PageSearchFieldNormalizer())->field($field);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    private function replaceField(int $index, array $field): array
    {
        $this->fields[$index] = $this->normalizeField($field);
        return $this->fields[$index];
    }
}
