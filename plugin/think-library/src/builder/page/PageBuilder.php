<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace think\admin\builder\page;

use think\admin\builder\base\render\BuilderAttributesRenderer;
use think\admin\builder\page\render\PageNodeRenderContext;
use think\admin\builder\page\render\PageNodeRendererFactory;
use think\admin\builder\page\render\PageRenderPipeline;
use think\admin\builder\page\render\PageRenderState;
use think\admin\builder\page\render\PageSearchRenderContext;
use think\admin\builder\page\render\PageScriptRenderContext;
use think\admin\builder\page\render\PageTableRenderContext;
use think\admin\Controller;
use think\admin\Library;
use think\admin\service\AppService;
use think\exception\HttpResponseException;

/**
 * 列表页面视图构建器.
 *
 * 用于快速构建数据列表页面，支持表格展示、搜索筛选、操作按钮等功能
 *
 * @class PageBuilder
 */
class PageBuilder
{
    /**
     * 当前控制器.
     */
    private Controller $class;

    /**
     * 页面标题.
     */
    private string $title = '';

    /**
     * 头部按钮 HTML.
     */
    private array $buttons = [];

    /**
     * 头部按钮配置.
     */
    private array $buttonItems = [];

    /**
     * 搜索字段配置.
     */
    private array $searchFields = [];

    /**
     * 搜索表单属性.
     */
    private array $searchAttrs = [];

    /**
     * 搜索图例.
     */
    private string $searchLegend = '条件搜索';

    /**
     * 是否显示搜索图例.
     */
    private bool $searchLegendEnabled = true;

    /**
     * 表格 ID.
     */
    private string $tableId = 'PageDataTable';

    /**
     * 表格 URL.
     */
    private string $tableUrl;

    /**
     * 表格属性.
     */
    private array $tableAttrs = [];

    /**
     * 表格参数.
     */
    private array $tableOptions = [];

    /**
     * 表格列配置.
     */
    private array $columns = [];

    /**
     * 行工具条模板 ID.
     */
    private string $toolbarId = 'toolbar';

    /**
     * 行工具条 HTML.
     */
    private array $rowActions = [];

    /**
     * 模板片段.
     */
    private array $templates = [];

    /**
     * 内容节点.
     */
    private array $contentNodes = [];

    /**
     * 初始化前脚本.
     */
    private array $bootScripts = [];

    /**
     * 初始化后脚本.
     */
    private array $initScripts = [];

    /**
     * 附加脚本.
     */
    private array $scripts = [];

    /**
     * 内容包裹样式.
     */
    private string $contentClass = 'think-box-shadow';

    /**
     * 渲染时附带变量.
     */
    private array $renderVars = [];

    /**
     * 当前页面节点渲染上下文.
     */
    private ?PageRenderState $renderState = null;

    /**
     * PageBuilder 构造函数.
     *
     * @param Controller $class 当前控制器实例
     */
    public function __construct(Controller $class)
    {
        $this->class = $class;
        $this->tableOptions = $this->normalizeTableOptions([]);
    }

    /**
     * 创建列表页生成器.
     */
    public static function make(): self
    {
        return Library::$sapp->invokeClass(static::class);
    }

    /**
     * 创建原始 JS 片段包装对象
     *
     * @param string $script JavaScript 代码
     * @return array 返回包装数组
     */
    public static function js(string $script): array
    {
        return ['__raw__' => $script];
    }

    /**
     * 定义页面结构.
     * @param callable(PageLayout): void $callback
     * @return $this
     */
    public function define(callable $callback): self
    {
        $layout = new PageLayout($this);
        $callback($layout);
        $this->contentNodes = array_merge($this->contentNodes, $layout->exportChildren());
        return $this;
    }

    /**
     * 完成页面构建.
     * @return $this
     */
    public function build(): self
    {
        return $this;
    }

    /**
     * 设置页面标题.
     * @return $this
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置内容样式类.
     * @return $this
     */
    public function setContentClass(string $class): self
    {
        $this->contentClass = trim($class);
        return $this;
    }

    /**
     * 设置搜索表单属性.
     * @return $this
     */
    public function setSearchAttrs(array $attrs): self
    {
        $this->searchAttrs = array_merge($this->searchAttrs, $attrs);
        return $this;
    }

    /**
     * 设置搜索图例.
     * @return $this
     */
    public function setSearchLegend(string $legend): self
    {
        $this->searchLegend = $legend;
        return $this;
    }

    /**
     * 切换搜索图例显示.
     * @return $this
     */
    public function withSearchLegend(bool $show = true): self
    {
        $this->searchLegendEnabled = $show;
        return $this;
    }

    /**
     * 设置表格.
     * @return $this
     */
    public function setTable(string $id = 'PageDataTable', ?string $url = null, array $attrs = []): self
    {
        $this->tableId = $id;
        $this->tableUrl = $url;
        $this->tableAttrs = array_merge($this->tableAttrs, $attrs);
        return $this;
    }

    /**
     * 设置表格参数.
     * @return $this
     */
    public function setTableOptions(array $options): self
    {
        $this->tableOptions = $this->mergeAssoc($this->tableOptions, $options);
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTableOptions(): array
    {
        return $this->tableOptions;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createTableOptions(array $options = []): PageTableOptions
    {
        return new PageTableOptions($this, $this->mergeAssoc($this->tableOptions, $options));
    }

    public function attachTableOptions(PageTableOptions $options): PageTableOptions
    {
        return $options->attach($this->replaceTableOptions($options->export()));
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public function replaceTableOptions(array $options): array
    {
        $this->tableOptions = $this->normalizeTableOptions($options);
        return $this->tableOptions;
    }

    /**
     * 设置工具条模板 ID.
     * @return $this
     */
    public function setToolbarId(string $toolbarId): self
    {
        $this->toolbarId = trim($toolbarId) ?: 'toolbar';
        return $this;
    }

    /**
     * 添加弹窗按钮.
     * @return $this
     */
    public function addModalButton(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->appendButtonAction($this->actionNormalizer()->button([
            'type' => 'modal',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加跳转按钮.
     * @return $this
     */
    public function addOpenButton(string $label, string $url, array $attrs = [], ?string $auth = null): self
    {
        return $this->appendButtonAction($this->actionNormalizer()->button([
            'type' => 'open',
            'label' => $label,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加加载按钮.
     * @return $this
     */
    public function addLoadButton(string $label, string $url, array $attrs = [], ?string $auth = null): self
    {
        return $this->appendButtonAction($this->actionNormalizer()->button([
            'type' => 'load',
            'label' => $label,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加通用动作按钮.
     * @return $this
     */
    public function addActionButton(string $label, string $url, string $value = '', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->appendButtonAction($this->actionNormalizer()->button([
            'type' => 'action',
            'label' => $label,
            'url' => $url,
            'value' => $value,
            'confirm' => $confirm,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加头部按钮 HTML.
     * @return $this
     */
    public function addButtonHtml(string $html, array $schema = []): self
    {
        $this->buttons[] = $html;
        $this->buttonItems[] = array_merge(['html' => $html], $schema);
        return $this;
    }

    /**
     * 添加结构化按钮.
     * @return $this
     */
    public function addButton(array $button): self
    {
        return $this->appendButtonAction($this->actionNormalizer()->button($button));
    }

    /**
     * @param array<string, mixed> $action
     */
    public function createButtonAction(array $action = []): PageAction
    {
        return new PageAction($this, 'button', $action);
    }

    public function attachButtonAction(PageAction $action): PageAction
    {
        $index = count($this->buttonItems);
        $this->storeButtonAction($action->export(), $index);
        return $action->attach($index);
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    public function replaceButtonAction(int $index, array $action): array
    {
        return $this->storeButtonAction($action, $index);
    }

    /**
     * 添加批量操作按钮.
     * @return $this
     */
    public function addBatchActionButton(string $label, string $url, string $rule, string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->appendButtonAction($this->actionNormalizer()->button([
            'type' => 'batch-action',
            'label' => $label,
            'url' => $url,
            'rule' => $rule,
            'confirm' => $confirm,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加搜索输入框.
     * @return $this
     */
    public function addSearchInput(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        return $this->addSearchField($this->searchFieldNormalizer()->input($name, $label, $placeholder, $attrs));
    }

    /**
     * 添加搜索字段.
     * @return $this
     */
    public function addSearchField(array $field): self
    {
        $this->searchFields[] = $this->searchFieldNormalizer()->field($field);
        return $this;
    }

    /**
     * 创建搜索字段对象.
     * @param array<string, mixed> $field
     */
    public function createSearchField(array $field): PageSearchField
    {
        return new PageSearchField($this, $this->searchFieldNormalizer()->field($field));
    }

    public function attachSearchField(PageSearchField $field): PageSearchField
    {
        $index = count($this->searchFields);
        $normalized = $this->searchFieldNormalizer()->field($field->export());
        $this->searchFields[$index] = $normalized;
        return $field->attach($index, $normalized);
    }

    /**
     * @param array<string, mixed> $field
     * @return array<string, mixed>
     */
    public function replaceSearchField(int $index, array $field): array
    {
        $normalized = $this->searchFieldNormalizer()->field($field);
        $this->searchFields[$index] = $normalized;
        return $normalized;
    }

    /**
     * 添加搜索下拉框.
     * @return $this
     */
    public function addSearchSelect(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): self
    {
        return $this->addSearchField($this->searchFieldNormalizer()->select($name, $label, $options, $attrs, $source));
    }

    /**
     * 添加搜索日期范围.
     * @return $this
     */
    public function addSearchDateRange(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        return $this->addSearchField($this->searchFieldNormalizer()->dateRange($name, $label, $placeholder, $attrs));
    }

    /**
     * 添加搜索隐藏字段.
     * @return $this
     */
    public function addSearchHidden(string $name, string $value = ''): self
    {
        return $this->addSearchField($this->searchFieldNormalizer()->hidden($name, $value));
    }

    /**
     * 添加搜索按钮.
     * @return $this
     */
    public function addSearchSubmitButton(string $label = '搜 索', array $attrs = []): self
    {
        return $this->addSearchField($this->searchFieldNormalizer()->submit($label, $attrs));
    }

    /**
     * 添加勾选列.
     * @return $this
     */
    public function addCheckboxColumn(array $options = []): self
    {
        return $this->addColumn(array_merge(['checkbox' => true, 'fixed' => true], $options));
    }

    /**
     * 添加排序输入列.
     * @return $this
     */
    public function addSortInputColumn(string $actionUrl = '{:sysuri()}', array $options = []): self
    {
        $this->attachSortInputColumn($this->createSortInputColumn($actionUrl, $options));
        return $this;
    }

    /**
     * 添加表格列.
     * @return $this
     */
    public function addColumn(array $column): self
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * @param array<string, mixed> $column
     */
    public function createColumn(array $column = []): PageColumn
    {
        return new PageColumn($this, $column);
    }

    public function createSortInputColumn(string $actionUrl = '{:sysuri()}', array $options = []): PageSortInputColumn
    {
        return new PageSortInputColumn($this, $actionUrl, $options);
    }

    public function attachSortInputColumn(PageSortInputColumn $column): PageSortInputColumn
    {
        return $column->attachResult($this->storeSortInputColumn($column->getActionUrl(), $column->export()));
    }

    /**
     * @param array<string, mixed> $options
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    public function replaceSortInputColumn(int $index, string $actionUrl, array $options, array $meta = []): array
    {
        return $this->storeSortInputColumn($actionUrl, $options, $index, $meta);
    }

    public function attachColumn(PageColumn $column): PageColumn
    {
        $index = count($this->columns);
        $this->columns[$index] = $column->export();
        return $column->attach($index, $this->columns[$index]);
    }

    /**
     * @param array<string, mixed> $column
     * @return array<string, mixed>
     */
    public function replaceColumn(int $index, array $column): array
    {
        $this->columns[$index] = $column;
        return $column;
    }

    /**
     * 添加工具条列.
     * @return $this
     */
    public function addToolbarColumn(string $title = '操作面板', array $options = []): self
    {
        $this->attachToolbarColumn($this->createToolbarColumn($title, $options));
        return $this;
    }

    /**
     * 添加状态开关列.
     * @return $this
     */
    public function addStatusSwitchColumn(string $actionUrl, array $options = []): self
    {
        $this->attachStatusSwitchColumn($this->createStatusSwitchColumn($actionUrl, $options));
        return $this;
    }

    public function createToolbarColumn(string $title = '操作面板', array $options = []): PageToolbarColumn
    {
        return new PageToolbarColumn($this, $title, $options);
    }

    public function attachToolbarColumn(PageToolbarColumn $column): PageToolbarColumn
    {
        return $column->attachResult($this->storeToolbarColumn($column->export()));
    }

    /**
     * @param array<string, mixed> $options
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    public function replaceToolbarColumn(int $index, array $options, array $meta = []): array
    {
        return $this->storeToolbarColumn($options, $index, $meta);
    }

    public function createStatusSwitchColumn(string $actionUrl, array $options = []): PageStatusSwitchColumn
    {
        return new PageStatusSwitchColumn($this, $actionUrl, $options);
    }

    public function attachStatusSwitchColumn(PageStatusSwitchColumn $column): PageStatusSwitchColumn
    {
        return $column->attachResult($this->storeStatusSwitchColumn($column->getActionUrl(), $column->export()));
    }

    /**
     * @param array<string, mixed> $options
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    public function replaceStatusSwitchColumn(int $index, string $actionUrl, array $options, array $meta = []): array
    {
        return $this->storeStatusSwitchColumn($actionUrl, $options, $index, $meta);
    }

    /**
     * 添加行弹窗按钮.
     * @return $this
     */
    public function addRowModalAction(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->appendRowAction($this->actionNormalizer()->row([
            'type' => 'modal',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加行跳转按钮.
     * @return $this
     */
    public function addRowOpenAction(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->appendRowAction($this->actionNormalizer()->row([
            'type' => 'open',
            'label' => $label,
            'title' => $title,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加行工具按钮 HTML.
     * @return $this
     */
    public function addRowActionHtml(string $html): self
    {
        $this->rowActions[] = $html;
        return $this;
    }

    /**
     * 添加结构化行操作.
     * @return $this
     */
    public function addRowAction(array $action): self
    {
        return $this->appendRowAction($this->actionNormalizer()->row($action));
    }

    /**
     * @param array<string, mixed> $action
     */
    public function createRowAction(array $action = []): PageAction
    {
        return new PageAction($this, 'row', $action);
    }

    public function attachRowAction(PageAction $action): PageAction
    {
        $index = count($this->rowActions);
        $this->storeRowAction($action->export(), $index);
        return $action->attach($index);
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    public function replaceRowAction(int $index, array $action): array
    {
        return $this->storeRowAction($action, $index);
    }

    /**
     * 添加行操作按钮.
     * @return $this
     */
    public function addRowActionButton(string $label, string $url, string $value = 'id#{{d.id}}', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        return $this->appendRowAction($this->actionNormalizer()->row([
            'type' => 'action',
            'label' => $label,
            'url' => $url,
            'value' => $value,
            'confirm' => $confirm,
            'auth' => $auth,
            'attrs' => $attrs,
        ]));
    }

    /**
     * 添加模板片段.
     * @return $this
     */
    public function addTemplate(string $id, string $html): self
    {
        $this->templates[$id] = $html;
        return $this;
    }

    /**
     * 添加初始化前脚本.
     * @return $this
     */
    public function addBootScript(string $script): self
    {
        $this->bootScripts[] = trim($script);
        return $this;
    }

    /**
     * 添加初始化后脚本.
     * @return $this
     */
    public function addInitScript(string $script): self
    {
        $this->initScripts[] = trim($script);
        return $this;
    }

    /**
     * 添加附加脚本.
     * @return $this
     */
    public function addScript(string $script): self
    {
        $this->scripts[] = trim($script);
        return $this;
    }

    /**
     * 输出页面内容.
     * @return mixed
     */
    public function fetch(array $vars = [])
    {
        throw new HttpResponseException($this->renderResponse($vars));
    }

    /**
     * 渲染页面 HTML.
     * @param array<string, mixed> $vars
     */
    public function renderHtml(array $vars = []): string
    {
        return $this->renderResponse($vars)->getContent();
    }

    /**
     * 获取页面配置.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'buttons' => $this->buttonItems,
            'content' => $this->normalizeSchemaValue($this->contentNodes),
            'searchFields' => $this->normalizeSchemaValue($this->searchFields),
            'table' => [
                'id' => $this->tableId,
                'url' => $this->tableUrl ?? '',
                'attrs' => $this->tableAttrs,
                'options' => $this->normalizeSchemaValue($this->buildTableOptions()),
                'columns' => $this->normalizeSchemaValue($this->columns),
            ],
            'templates' => array_keys($this->templates),
        ];
    }

    /**
     * 合并数组配置.
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

    /**
     * @param array<string, mixed> $vars
     */
    private function renderResponse(array $vars = [])
    {
        $vars['title'] = $vars['title'] ?? $this->title;
        $vars['pageBuilder'] = $vars['pageBuilder'] ?? $this;
        $vars['pageSchema'] = $vars['pageSchema'] ?? $this->toArray();
        $vars['staticRoot'] = strval($vars['staticRoot'] ?? AppService::uri('static'));
        foreach (get_object_vars($this->class) as $k => $v) {
            $vars[$k] = $v;
        }
        $this->renderVars = $vars;
        return display($this->render(), $vars);
    }

    /**
     * Schema 值标准化.
     * @param mixed $value
     */
    private function normalizeSchemaValue($value)
    {
        if (is_array($value) && isset($value['__raw__'])) {
            return ['type' => 'js', 'code' => $value['__raw__']];
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $result[$key] = $this->normalizeSchemaValue($item);
            }
            return $result;
        }
        return $value;
    }

    /**
     * 构建表格参数.
     */
    private function buildTableOptions(): array
    {
        $options = $this->tableOptions;
        if (count($this->columns) > 0) {
            $options['cols'] = [$this->columns];
        }
        return $options;
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function normalizeTableOptions(array $options): array
    {
        return $this->mergeAssoc(['even' => true, 'height' => 'full'], $options);
    }

    /**
     * 追加头部动作.
     * @param array<string, mixed> $action
     */
    private function appendButtonAction(array $action): self
    {
        $this->storeButtonAction($action);
        return $this;
    }

    /**
     * 追加行动作.
     * @param array<string, mixed> $action
     */
    private function appendRowAction(array $action): self
    {
        $this->storeRowAction($action);
        return $this;
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    private function storeButtonAction(array $action, ?int $index = null): array
    {
        $action = $this->actionNormalizer()->button($action);
        $html = strval($action['html'] ?? '');
        $schema = array_merge($action, ['html' => $html]);

        if ($index === null) {
            $this->buttons[] = $html;
            $this->buttonItems[] = $schema;
        } else {
            $this->buttons[$index] = $html;
            $this->buttonItems[$index] = $schema;
        }

        return $schema;
    }

    /**
     * @param array<string, mixed> $action
     * @return array<string, mixed>
     */
    private function storeRowAction(array $action, ?int $index = null): array
    {
        $action = $this->actionNormalizer()->row($action);
        $html = strval($action['html'] ?? '');
        $schema = array_merge($action, ['html' => $html]);

        if ($index === null) {
            $this->rowActions[] = $html;
        } else {
            $this->rowActions[$index] = $html;
        }

        return $schema;
    }

    private function actionNormalizer(): PageActionNormalizer
    {
        return new PageActionNormalizer($this->tableId);
    }

    private function columnNormalizer(): PageColumnNormalizer
    {
        return new PageColumnNormalizer($this->tableId, $this->toolbarId, fn($value): string => $this->encodeJs($value));
    }

    private function searchFieldNormalizer(): PageSearchFieldNormalizer
    {
        return new PageSearchFieldNormalizer();
    }

    /**
     * @param array<string, mixed> $preset
     */
    private function appendColumnPreset(array $preset): self
    {
        $this->storeColumnPreset($preset);
        return $this;
    }

    /**
     * @param array<string, mixed> $options
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    private function storeSortInputColumn(string $actionUrl, array $options, ?int $index = null, array $meta = []): array
    {
        return $this->storeColumnPreset($this->columnNormalizer()->sortInput($actionUrl, $options), $index, $meta);
    }

    /**
     * @param array<string, mixed> $options
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    private function storeToolbarColumn(array $options, ?int $index = null, array $meta = []): array
    {
        $title = strval($options['title'] ?? '操作面板');
        return $this->storeColumnPreset($this->columnNormalizer()->toolbar($title, $options), $index, $meta);
    }

    /**
     * @param array<string, mixed> $options
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    private function storeStatusSwitchColumn(string $actionUrl, array $options, ?int $index = null, array $meta = []): array
    {
        return $this->storeColumnPreset($this->columnNormalizer()->statusSwitch($actionUrl, $options), $index, $meta);
    }

    /**
     * @param array<string, mixed> $preset
     * @param array{templateKeys?: array<int, string>, scriptIndexes?: array<int, int>} $meta
     * @return array<string, mixed>
     */
    private function storeColumnPreset(array $preset, ?int $index = null, array $meta = []): array
    {
        $columnIndex = $index ?? count($this->columns);
        if (is_array($preset['column'] ?? null)) {
            $this->columns[$columnIndex] = $preset['column'];
        }
        $templateKeys = $this->replacePresetTemplates((array)($preset['templates'] ?? []), (array)($meta['templateKeys'] ?? []));
        $scriptIndexes = $this->replacePresetScripts((array)($preset['scripts'] ?? []), (array)($meta['scriptIndexes'] ?? []));
        return [
            'index' => $columnIndex,
            'column' => $this->columns[$columnIndex] ?? [],
            'templateKeys' => $templateKeys,
            'scriptIndexes' => $scriptIndexes,
        ];
    }

    /**
     * @param array<string, mixed> $templates
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    private function replacePresetTemplates(array $templates, array $keys = []): array
    {
        foreach ($keys as $key) {
            if (is_string($key) && $key !== '') {
                unset($this->templates[$key]);
            }
        }
        $result = [];
        foreach ($templates as $id => $html) {
            $id = is_string($id) ? trim($id) : '';
            if ($id !== '') {
                $this->templates[$id] = strval($html);
                $result[] = $id;
            }
        }
        return $result;
    }

    /**
     * @param array<int, mixed> $scripts
     * @param array<int, int> $indexes
     * @return array<int, int>
     */
    private function replacePresetScripts(array $scripts, array $indexes = []): array
    {
        $normalized = [];
        foreach ($scripts as $script) {
            $script = trim(strval($script));
            if ($script !== '') {
                $normalized[] = $script;
            }
        }

        $result = [];
        $indexes = array_values(array_map('intval', $indexes));
        $count = max(count($normalized), count($indexes));
        for ($i = 0; $i < $count; $i++) {
            if (isset($normalized[$i])) {
                if (isset($indexes[$i])) {
                    $this->scripts[$indexes[$i]] = $normalized[$i];
                    $result[] = $indexes[$i];
                } else {
                    $this->scripts[] = $normalized[$i];
                    end($this->scripts);
                    $key = key($this->scripts);
                    $result[] = is_int($key) ? $key : count($this->scripts) - 1;
                }
            } elseif (isset($indexes[$i])) {
                unset($this->scripts[$indexes[$i]]);
            }
        }

        return $result;
    }

    /**
     * 渲染页面.
     */
    private function render(): string
    {
        $schema = $this->toArray();
        $this->renderState = $this->createRenderState($schema);
        try {
            return $this->renderPipeline()->renderShell(
                $this->renderState,
                $this->title,
                $this->buttons,
                strval($this->renderVars['showErrorMessage'] ?? ''),
                $this->contentClass,
                $this->renderPageContent(),
                $this->renderTemplates(),
                $this->renderScripts()
            );
        } finally {
            $this->renderState = null;
        }
    }

    /**
     * 渲染内容节点.
     * @param array<int, array<string, mixed>> $nodes
     */
    private function renderContentNodes(array $nodes): string
    {
        return $this->renderPipeline()->renderContentNodes($nodes, $this->currentRenderState());
    }

    /**
     * 渲染搜索表单.
     */
    private function renderSearchForm(): string
    {
        return $this->renderPipeline()->renderSearch(
            $this->searchFields,
            $this->searchAttrs,
            $this->tableId,
            strval($this->searchAttrs['action'] ?? $this->resolveCurrentUrl()),
            $this->searchLegend,
            $this->searchLegendEnabled,
            $this->currentRenderState()
        );
    }

    /**
     * 解析当前 URL.
     */
    private function resolveCurrentUrl(): string
    {
        try {
            return url()->build();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * 获取搜索值.
     */
    private function searchValue(string $name): string
    {
        if (isset($this->class->get) && is_array($this->class->get) && array_key_exists($name, $this->class->get)) {
            return strval($this->class->get[$name]);
        }
        if (isset($this->renderVars['get']) && is_array($this->renderVars['get']) && array_key_exists($name, $this->renderVars['get'])) {
            return strval($this->renderVars['get'][$name]);
        }
        if (isset($this->class->request)) {
            return strval($this->class->request->get($name, ''));
        }
        return '';
    }

    /**
     * 获取搜索选项.
     */
    private function resolveSearchOptions(array $field): array
    {
        if (!empty($field['source']) && isset($this->renderVars[$field['source']]) && is_array($this->renderVars[$field['source']])) {
            return $this->renderVars[$field['source']];
        }
        return is_array($field['options'] ?? null) ? $field['options'] : [];
    }

    /**
     * 渲染表格节点.
     */
    private function renderTable(): string
    {
        $attrs = $this->tableNormalizer()->table(
            $this->tableId,
            strval($this->tableUrl ?? $this->resolveCurrentUrl()),
            $this->tableAttrs,
            count($this->searchFields) > 0
        );
        return $this->renderPipeline()->renderTable($attrs, $this->currentRenderState());
    }

    /**
     * 渲染页面主体内容.
     */
    private function renderPageContent(): string
    {
        if (count($this->contentNodes) > 0) {
            return $this->renderContentNodes($this->contentNodes);
        }

        $content = [];
        if ($search = $this->renderSearchForm()) {
            $content[] = $search;
        }
        $content[] = $this->renderTable();
        return join("\n\t\t\t\t", $content);
    }

    /**
     * 渲染模板片段.
     */
    private function renderTemplates(): string
    {
        return $this->renderPipeline()->renderTemplates($this->templates, $this->toolbarId, $this->rowActions);
    }

    /**
     * 渲染脚本.
     */
    private function renderScripts(): string
    {
        return $this->renderPipeline()->renderScripts(
            $this->tableId,
            $this->buildTableOptions(),
            $this->bootScripts,
            $this->initScripts,
            $this->scripts,
            $this->currentRenderState()
        );
    }

    /**
     * JS 值编码.
     * @param mixed $value
     */
    private function encodeJs($value): string
    {
        if (is_array($value) && isset($value['__raw__'])) {
            return $value['__raw__'];
        }
        if (is_array($value)) {
            $items = [];
            if (array_is_list($value)) {
                foreach ($value as $item) {
                    $items[] = $this->encodeJs($item);
                }
                return '[' . join(',', $items) . ']';
            }
            foreach ($value as $key => $item) {
                $items[] = json_encode((string)$key, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ':' . $this->encodeJs($item);
            }
            return '{' . join(',', $items) . '}';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_null($value)) {
            return 'null';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        return json_encode((string)$value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function tableNormalizer(): PageTableNormalizer
    {
        return new PageTableNormalizer();
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function createRenderState(array $schema): PageRenderState
    {
        $attrsRenderer = new BuilderAttributesRenderer();
        return new PageRenderState(
            $schema,
            new PageNodeRendererFactory(),
            new PageNodeRenderContext(
                fn(array $nodes): string => $this->renderContentNodes($nodes),
                [$attrsRenderer, 'render'],
                fn(): string => $this->renderSearchForm(),
                fn(): string => $this->renderTable(),
            ),
            new PageSearchRenderContext(
                [$attrsRenderer, 'render'],
                fn(string $name): string => $this->searchValue($name),
                fn(array $field): array => $this->resolveSearchOptions($field),
            ),
            new PageTableRenderContext(
                [$attrsRenderer, 'render'],
            ),
            new PageScriptRenderContext(
                fn($value): string => $this->encodeJs($value),
            )
        );
    }

    private function currentRenderState(): PageRenderState
    {
        return $this->renderState ?? $this->createRenderState($this->toArray());
    }

    private function renderPipeline(): PageRenderPipeline
    {
        return new PageRenderPipeline();
    }
}
