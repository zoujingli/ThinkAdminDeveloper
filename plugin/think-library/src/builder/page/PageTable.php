<?php

declare(strict_types=1);

namespace think\admin\builder\page;

/**
 * 页面表格区定义器.
 * @class PageTable
 */
class PageTable extends PageNode
{
    private string $tableId;

    private ?string $tableUrl;

    public function __construct(PageBuilder $builder, string $id = 'PageDataTable', ?string $url = null, array $attrs = [])
    {
        parent::__construct($builder, 'table', 'table');
        $this->tableId = trim($id) ?: 'PageDataTable';
        $this->tableUrl = $url;
        $this->attrs($attrs);
        $this->builder->setTable($this->tableId, $this->tableUrl, $this->buildAttrs());
    }

    public function options(array $options): self
    {
        $this->builder->setTableOptions($options);
        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function optionsItem(array $options = []): PageTableOptions
    {
        return $this->builder->attachTableOptions($this->builder->createTableOptions($options));
    }

    /**
     * @param array<string, mixed> $options
     */
    public function config(array $options = []): PageTableOptions
    {
        return $this->optionsItem($options);
    }

    public function toolbarId(string $toolbarId): self
    {
        $this->builder->setToolbarId($toolbarId);
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

    public function checkbox(array $options = []): self
    {
        $this->builder->addCheckboxColumn($options);
        return $this;
    }

    public function line(int $lines = 1): self
    {
        $value = max(1, min(3, $lines));
        $this->attr('data-line', strval($value));
        return $this;
    }

    public function sortInput(string $actionUrl = '{:sysuri()}', array $options = []): self
    {
        $this->builder->addSortInputColumn($actionUrl, $options);
        return $this;
    }

    public function sortInputItem(string $actionUrl = '{:sysuri()}', array $options = []): PageSortInputColumn
    {
        return $this->builder->attachSortInputColumn($this->builder->createSortInputColumn($actionUrl, $options));
    }

    public function column(array $column): self
    {
        $this->builder->addColumn($column);
        return $this;
    }

    /**
     * @param array<string, mixed>|PageColumn $column
     */
    public function columnItem(array|PageColumn $column = []): PageColumn
    {
        $item = $column instanceof PageColumn ? $column : $this->builder->createColumn($column);
        return $this->builder->attachColumn($item);
    }

    public function statusSwitch(string $actionUrl, array $options = []): self
    {
        $this->builder->addStatusSwitchColumn($actionUrl, $options);
        return $this;
    }

    public function statusSwitchItem(string $actionUrl, array $options = []): PageStatusSwitchColumn
    {
        return $this->builder->attachStatusSwitchColumn($this->builder->createStatusSwitchColumn($actionUrl, $options));
    }

    public function toolbar(string $title = '操作面板', array $options = []): self
    {
        $this->builder->addToolbarColumn($title, $options);
        return $this;
    }

    public function toolbarItem(string $title = '操作面板', array $options = []): PageToolbarColumn
    {
        return $this->builder->attachToolbarColumn($this->builder->createToolbarColumn($title, $options));
    }

    public function template(string $id, string $html): self
    {
        $this->builder->addTemplate($id, $html);
        return $this;
    }

    public function rowActions(): PageRowActions
    {
        return new PageRowActions($this->builder);
    }

    /**
     * @param callable(PageRowActions): void $callback
     */
    public function rows(callable $callback): self
    {
        $callback($this->rowActions());
        return $this;
    }

    /**
     * 导出节点数组.
     * @return array<string, mixed>
     */
    public function export(): array
    {
        return [
            'type' => 'table',
            'id' => $this->tableId,
            'url' => $this->tableUrl ?? '',
            'attrs' => $this->buildAttrs(),
            'modules' => $this->modules,
        ];
    }

    protected function afterMutate(): void
    {
        $this->builder->setTable($this->tableId, $this->tableUrl, $this->buildAttrs());
    }
}
