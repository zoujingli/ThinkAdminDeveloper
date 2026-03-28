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

    /**
     * @var array<string, mixed>
     */
    private array $tableOptions = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $columns = [];

    private string $toolbarId;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $rowActions = [];

    /**
     * @var array<string, string>
     */
    private array $templates = [];

    /**
     * @var array<string, string>
     */
    private array $generatedTemplates = [];

    /**
     * @var array<int, array<int, string>>
     */
    private array $generatedTemplateKeys = [];

    /**
     * @var array<int, string>
     */
    private array $bootScripts = [];

    /**
     * @var array<int, string>
     */
    private array $scripts = [];

    /**
     * @var array<string, string>
     */
    private array $generatedScripts = [];

    /**
     * @var array<int, array<int, string>>
     */
    private array $generatedScriptKeys = [];

    public function __construct(PageBuilder $builder, string $id = 'PageDataTable', ?string $url = null, array $attrs = [])
    {
        parent::__construct($builder, 'table', 'table');
        $this->tableId = trim($id) ?: 'PageDataTable';
        $this->tableUrl = $url;
        $this->tableOptions = $this->normalizeTableOptions([]);
        $this->toolbarId = $this->buildDefaultToolbarId();
        $this->attrs($attrs);
    }

    public function options(array $options): self
    {
        $this->tableOptions = $this->mergeAssoc($this->tableOptions, $options);
        return $this;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function optionsItem(array $options = []): PageTableOptions
    {
        $item = new PageTableOptions($this->builder, $this->mergeAssoc($this->tableOptions, $options));
        return $item->attachSync(fn(array $state): array => $this->replaceTableOptions($state));
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
        $this->toolbarId = trim($toolbarId) ?: $this->buildDefaultToolbarId();
        return $this;
    }

    public function bootScript(string $script): self
    {
        $script = trim($script);
        if ($script !== '') {
            $this->bootScripts[] = $script;
        }
        return $this;
    }

    public function script(string $script): self
    {
        $script = trim($script);
        if ($script !== '') {
            $this->scripts[] = $script;
        }
        return $this;
    }

    public function checkbox(array $options = []): self
    {
        $this->columns[] = array_merge(['checkbox' => true, 'fixed' => true], $options);
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
        $this->attachSortInputColumn($this->createSortInputColumn($actionUrl, $options));
        return $this;
    }

    public function sortInputItem(string $actionUrl = '{:sysuri()}', array $options = []): PageSortInputColumn
    {
        return $this->attachSortInputColumn($this->createSortInputColumn($actionUrl, $options));
    }

    public function column(array $column): self
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * @param array<string, mixed>|PageColumn $column
     */
    public function columnItem(array|PageColumn $column = []): PageColumn
    {
        $item = $column instanceof PageColumn ? $column : new PageColumn($this->builder, $column);
        $index = count($this->columns);
        $this->columns[$index] = $item->export();
        return $item->attachSync(fn(array $state): array => $this->replaceColumn($index, $state));
    }

    public function statusSwitch(string $actionUrl, array $options = []): self
    {
        $this->attachStatusSwitchColumn($this->createStatusSwitchColumn($actionUrl, $options));
        return $this;
    }

    public function statusSwitchItem(string $actionUrl, array $options = []): PageStatusSwitchColumn
    {
        return $this->attachStatusSwitchColumn($this->createStatusSwitchColumn($actionUrl, $options));
    }

    public function toolbar(string $title = '操作面板', array $options = []): self
    {
        $this->attachToolbarColumn($this->createToolbarColumn($title, $options));
        return $this;
    }

    public function toolbarItem(string $title = '操作面板', array $options = []): PageToolbarColumn
    {
        return $this->attachToolbarColumn($this->createToolbarColumn($title, $options));
    }

    public function template(string $id, string $html): self
    {
        $id = $this->resolveTemplateId($id);
        if ($id !== '') {
            $this->templates[$id] = $html;
        }
        return $this;
    }

    public function rowActions(): PageRowActions
    {
        return new PageRowActions($this->builder, $this);
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
            'options' => $this->tableOptions,
            'columns' => $this->columns,
            'toolbarId' => $this->toolbarId,
            'rowActions' => $this->rowActions,
            'templates' => $this->buildTemplates(),
            'bootScripts' => array_values($this->bootScripts),
            'scripts' => $this->buildScripts(),
        ];
    }

    /**
     * @param array<string, mixed> $options
     */
    public function replaceTableOptions(array $options): array
    {
        $this->tableOptions = $this->normalizeTableOptions($options);
        return $this->tableOptions;
    }

    /**
     * @param array<string, mixed> $column
     */
    public function replaceColumn(int $index, array $column): array
    {
        $this->columns[$index] = $column;
        return $this->columns[$index];
    }

    /**
     * @param array<string, mixed> $action
     */
    public function createRowAction(array $action = []): PageAction
    {
        return new PageAction($this->builder, 'row', $action);
    }

    public function attachRowAction(PageAction $action): PageAction
    {
        $index = count($this->rowActions);
        $this->rowActions[$index] = $this->normalizeRowAction($action->export());
        return $action->attachSync(fn(array $state): array => $this->replaceRowAction($index, $state));
    }

    /**
     * @param array<string, mixed> $action
     */
    public function addRowAction(array $action): self
    {
        $this->rowActions[] = $this->normalizeRowAction($action);
        return $this;
    }

    public function addRowActionHtml(string $html): self
    {
        $this->rowActions[] = $this->normalizeRowAction(['type' => 'html', 'html' => $html]);
        return $this;
    }

    public function addRowOpenAction(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->addRowAction([
            'type' => 'open',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function addRowModalAction(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->addRowAction([
            'type' => 'modal',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    public function addRowActionButton(string $label, string $url, string $value = 'id#{{d.id}}', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->addRowAction([
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
     * @param array<string, mixed> $action
     */
    public function replaceRowAction(int $index, array $action): array
    {
        $this->rowActions[$index] = $this->normalizeRowAction($action);
        return $this->rowActions[$index];
    }

    public function createSortInputColumn(string $actionUrl = '{:sysuri()}', array $options = []): PageSortInputColumn
    {
        return new PageSortInputColumn($this->builder, $actionUrl, $options);
    }

    public function attachSortInputColumn(PageSortInputColumn $column): PageSortInputColumn
    {
        $result = $this->storeSortInputColumn($column->getActionUrl(), $column->export());
        return $column->attachSync(fn(PageSortInputColumn $state): array => $this->storeSortInputColumn($state->getActionUrl(), $state->export(), intval($result['index'] ?? 0)))
            ->attachResult($result);
    }

    public function createToolbarColumn(string $title = '操作面板', array $options = []): PageToolbarColumn
    {
        return new PageToolbarColumn($this->builder, $title, $options);
    }

    public function attachToolbarColumn(PageToolbarColumn $column): PageToolbarColumn
    {
        $result = $this->storeToolbarColumn($column->export());
        return $column->attachSync(fn(PageToolbarColumn $state): array => $this->storeToolbarColumn($state->export(), intval($result['index'] ?? 0)))
            ->attachResult($result);
    }

    public function createStatusSwitchColumn(string $actionUrl, array $options = []): PageStatusSwitchColumn
    {
        return new PageStatusSwitchColumn($this->builder, $actionUrl, $options);
    }

    public function attachStatusSwitchColumn(PageStatusSwitchColumn $column): PageStatusSwitchColumn
    {
        $result = $this->storeStatusSwitchColumn($column->getActionUrl(), $column->export());
        return $column->attachSync(fn(PageStatusSwitchColumn $state): array => $this->storeStatusSwitchColumn($state->getActionUrl(), $state->export(), intval($result['index'] ?? 0)))
            ->attachResult($result);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildTableOptions(): array
    {
        $options = $this->tableOptions;
        if (count($this->columns) > 0) {
            $options['cols'] = [$this->columns];
        }
        return $options;
    }

    private function buildDefaultToolbarId(): string
    {
        $tableId = preg_replace('/[^\w]+/', '', $this->tableId);
        return trim('toolbar' . ($tableId ?: ''));
    }

    private function resolveTemplateId(string $id): string
    {
        $id = trim($id);
        if ($id === 'toolbar') {
            return $this->toolbarId;
        }
        return $id;
    }

    /**
     * @return array<string, string>
     */
    private function buildTemplates(): array
    {
        return $this->templates + array_diff_key($this->generatedTemplates, $this->templates);
    }

    /**
     * @return array<int, string>
     */
    private function buildScripts(): array
    {
        return array_values(array_filter(array_merge(array_values($this->generatedScripts), $this->scripts), static fn(string $script): bool => trim($script) !== ''));
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    private function normalizeRowAction(array $action): array
    {
        return (new PageActionNormalizer($this->tableId))->row($action);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function storeSortInputColumn(string $actionUrl, array $options, ?int $index = null): array
    {
        return $this->storeColumnPreset($this->columnNormalizer()->sortInput($actionUrl, $options), $index);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function storeToolbarColumn(array $options, ?int $index = null): array
    {
        $title = strval($options['title'] ?? '操作面板');
        return $this->storeColumnPreset($this->columnNormalizer()->toolbar($title, $options), $index);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function storeStatusSwitchColumn(string $actionUrl, array $options, ?int $index = null): array
    {
        return $this->storeColumnPreset($this->columnNormalizer()->statusSwitch($actionUrl, $options), $index);
    }

    /**
     * @param array<string, mixed> $preset
     * @return array<string, mixed>
     */
    private function storeColumnPreset(array $preset, ?int $index = null): array
    {
        $columnIndex = $index ?? count($this->columns);
        if (is_array($preset['column'] ?? null)) {
            $this->columns[$columnIndex] = $preset['column'];
        }

        foreach ($this->generatedTemplateKeys[$columnIndex] ?? [] as $key) {
            unset($this->generatedTemplates[$key]);
        }
        $this->generatedTemplateKeys[$columnIndex] = [];
        foreach ((array)($preset['templates'] ?? []) as $id => $html) {
            $id = trim(strval($id));
            if ($id !== '') {
                $this->generatedTemplates[$id] = strval($html);
                $this->generatedTemplateKeys[$columnIndex][] = $id;
            }
        }

        foreach ($this->generatedScriptKeys[$columnIndex] ?? [] as $key) {
            unset($this->generatedScripts[$key]);
        }
        $this->generatedScriptKeys[$columnIndex] = [];
        foreach (array_values((array)($preset['scripts'] ?? [])) as $offset => $script) {
            $script = trim(strval($script));
            if ($script !== '') {
                $key = "preset:{$columnIndex}:{$offset}";
                $this->generatedScripts[$key] = $script;
                $this->generatedScriptKeys[$columnIndex][] = $key;
            }
        }

        return [
            'index' => $columnIndex,
            'column' => $this->columns[$columnIndex] ?? [],
            'templateKeys' => $this->generatedTemplateKeys[$columnIndex] ?? [],
            'scriptIndexes' => $this->generatedScriptKeys[$columnIndex] ?? [],
        ];
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function normalizeTableOptions(array $options): array
    {
        return $this->mergeAssoc(['even' => true, 'height' => 'full'], $options);
    }

    private function columnNormalizer(): PageColumnNormalizer
    {
        return new PageColumnNormalizer($this->tableId, $this->toolbarId, fn($value): string => $this->builder->encodeJsValue($value));
    }

    /**
     * @param array<string, mixed> $origin
     * @param array<string, mixed> $append
     * @return array<string, mixed>
     */
    private function mergeAssoc(array $origin, array $append): array
    {
        foreach ($append as $key => $value) {
            if (isset($origin[$key]) && is_array($origin[$key]) && is_array($value) && !array_is_list($origin[$key]) && !array_is_list($value)) {
                $origin[$key] = $this->mergeAssoc($origin[$key], $value);
            } else {
                $origin[$key] = $value;
            }
        }
        return $origin;
    }
}
