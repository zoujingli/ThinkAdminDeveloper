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

namespace think\admin\helper;

use think\admin\Controller;
use think\admin\Library;
use think\exception\HttpResponseException;

/**
 * PageBuilder 原始 JS 片段包装.
 * @internal
 */
final class PageBuilderRaw
{
    public string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }
}

/**
 * 轻量列表页视图构建器。
 * @class PageBuilder
 */
class PageBuilder
{
    /**
     * 当前控制器.
     * @var Controller
     */
    private $class;

    /**
     * 页面标题.
     * @var string
     */
    private $title = '';

    /**
     * 头部按钮 HTML.
     * @var array
     */
    private $buttons = [];

    /**
     * 头部按钮配置.
     * @var array
     */
    private $buttonItems = [];

    /**
     * 搜索字段配置.
     * @var array
     */
    private $searchFields = [];

    /**
     * 搜索表单属性.
     * @var array
     */
    private $searchAttrs = [];

    /**
     * 搜索图例.
     * @var string
     */
    private $searchLegend = '条件搜索';

    /**
     * 是否显示搜索图例.
     * @var bool
     */
    private $searchLegendEnabled = true;

    /**
     * 表格 ID.
     * @var string
     */
    private $tableId = 'PageDataTable';

    /**
     * 表格 URL.
     * @var string
     */
    private $tableUrl;

    /**
     * 表格属性.
     * @var array
     */
    private $tableAttrs = [];

    /**
     * 表格参数.
     * @var array
     */
    private $tableOptions = [];

    /**
     * 表格列配置.
     * @var array
     */
    private $columns = [];

    /**
     * 行工具条模板 ID.
     * @var string
     */
    private $toolbarId = 'toolbar';

    /**
     * 行工具条 HTML.
     * @var array
     */
    private $rowActions = [];

    /**
     * 模板片段.
     * @var array
     */
    private $templates = [];

    /**
     * 表格前内容.
     * @var array
     */
    private $beforeTable = [];

    /**
     * 表格后内容.
     * @var array
     */
    private $afterTable = [];

    /**
     * 初始化前脚本.
     * @var array
     */
    private $bootScripts = [];

    /**
     * 初始化后脚本.
     * @var array
     */
    private $initScripts = [];

    /**
     * 附加脚本.
     * @var array
     */
    private $scripts = [];

    /**
     * 内容包裹样式.
     * @var string
     */
    private $contentClass = 'think-box-shadow';

    /**
     * 渲染时附带变量.
     * @var array
     */
    private $renderVars = [];

    /**
     * 构造函数.
     */
    public function __construct(Controller $class)
    {
        $this->class = $class;
        $this->tableOptions = ['even' => true, 'height' => 'full'];
    }

    /**
     * 创建列表页生成器.
     */
    public static function mk(): self
    {
        return Library::$sapp->invokeClass(static::class);
    }

    /**
     * 创建原始 JS 片段.
     */
    public static function raw(string $script): PageBuilderRaw
    {
        return new PageBuilderRaw($script);
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
        $attrs = array_merge($attrs, [
            'class' => trim(($attrs['class'] ?? '') . ' layui-btn layui-btn-sm layui-btn-primary'),
            'data-modal' => $url,
        ]);
        if ($title !== '') {
            $attrs['data-title'] = $title;
        }
        return $this->addButtonHtml($this->wrapAuth(sprintf('<a %s>%s</a>', $this->attrs($attrs), $label), $auth), [
            'type' => 'modal',
            'label' => $label,
            'url' => $url,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
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
     * 添加批量操作按钮.
     * @return $this
     */
    public function addBatchActionButton(string $label, string $url, string $rule, string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        $attrs = array_merge($attrs, [
            'class' => trim(($attrs['class'] ?? '') . ' layui-btn layui-btn-sm layui-btn-primary'),
            'data-table-id' => $this->tableId,
            'data-action' => $url,
            'data-rule' => $rule,
        ]);
        if ($confirm !== '') {
            $attrs['data-confirm'] = $confirm;
        }
        return $this->addButtonHtml($this->wrapAuth(sprintf('<a %s>%s</a>', $this->attrs($attrs), $label), $auth), [
            'type' => 'batch-action',
            'label' => $label,
            'url' => $url,
            'rule' => $rule,
            'confirm' => $confirm,
            'auth' => $auth,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 添加搜索输入框.
     * @return $this
     */
    public function addSearchInput(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        return $this->addSearchField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 添加搜索字段.
     * @return $this
     */
    public function addSearchField(array $field): self
    {
        $field = array_merge([
            'type' => 'input',
            'name' => '',
            'label' => '',
            'placeholder' => '',
            'attrs' => [],
            'options' => [],
            'source' => '',
            'class' => '',
            'wrapClass' => '',
        ], $field);
        $field['type'] = strtolower((string)$field['type']);
        $field['name'] = trim((string)$field['name']);
        $field['label'] = (string)$field['label'];
        $field['placeholder'] = (string)$field['placeholder'];
        $field['attrs'] = is_array($field['attrs']) ? $field['attrs'] : [];
        $field['options'] = is_array($field['options']) ? $field['options'] : [];
        $field['source'] = trim((string)$field['source']);
        $field['class'] = trim((string)$field['class']);
        $field['wrapClass'] = trim((string)$field['wrapClass']);
        $this->searchFields[] = $field;
        return $this;
    }

    /**
     * 添加搜索下拉框.
     * @return $this
     */
    public function addSearchSelect(string $name, string $label, array $options = [], array $attrs = [], string $source = ''): self
    {
        return $this->addSearchField([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $options,
            'attrs' => $attrs,
            'source' => $source,
        ]);
    }

    /**
     * 添加搜索日期范围.
     * @return $this
     */
    public function addSearchDateRange(string $name, string $label, string $placeholder = '', array $attrs = []): self
    {
        $attrs['data-date-range'] = $attrs['data-date-range'] ?? null;
        return $this->addSearchField([
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => $placeholder,
            'attrs' => $attrs,
        ]);
    }

    /**
     * 添加搜索隐藏字段.
     * @return $this
     */
    public function addSearchHidden(string $name, string $value = ''): self
    {
        return $this->addSearchField([
            'type' => 'hidden',
            'name' => $name,
            'attrs' => ['value' => $value],
        ]);
    }

    /**
     * 添加搜索按钮.
     * @return $this
     */
    public function addSearchSubmitButton(string $label = '搜 索', array $attrs = []): self
    {
        return $this->addSearchField([
            'type' => 'submit',
            'label' => $label,
            'attrs' => $attrs,
        ]);
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
     * 添加表格列.
     * @return $this
     */
    public function addColumn(array $column): self
    {
        $this->columns[] = $column;
        return $this;
    }

    /**
     * 添加工具条列.
     * @return $this
     */
    public function addToolbarColumn(string $title = '操作面板', array $options = []): self
    {
        return $this->addColumn(array_merge([
            'toolbar' => "#{$this->toolbarId}",
            'title' => $title,
            'align' => 'center',
            'minWidth' => 150,
            'fixed' => 'right',
        ], $options));
    }

    /**
     * 添加行弹窗按钮.
     * @return $this
     */
    public function addRowModalAction(string $label, string $url, string $title = '', array $attrs = [], ?string $auth = null): self
    {
        $attrs = array_merge($attrs, [
            'class' => trim(($attrs['class'] ?? '') . ' layui-btn layui-btn-sm'),
            'data-modal' => $url,
        ]);
        if ($title !== '') {
            $attrs['data-title'] = $title;
        }
        return $this->addRowActionHtml($this->wrapAuth(sprintf('<a %s>%s</a>', $this->attrs($attrs), $label), $auth));
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
     * 添加行操作按钮.
     * @return $this
     */
    public function addRowActionButton(string $label, string $url, string $value = 'id#{{d.id}}', string $confirm = '', array $attrs = [], ?string $auth = null): self
    {
        $attrs = array_merge($attrs, [
            'class' => trim(($attrs['class'] ?? '') . ' layui-btn layui-btn-sm layui-btn-danger'),
            'data-action' => $url,
            'data-value' => $value,
        ]);
        if ($confirm !== '') {
            $attrs['data-confirm'] = $confirm;
        }
        return $this->addRowActionHtml($this->wrapAuth(sprintf('<a %s>%s</a>', $this->attrs($attrs), $label), $auth));
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
     * 添加表格前内容.
     * @return $this
     */
    public function addBeforeTableHtml(string $html): self
    {
        $this->beforeTable[] = $html;
        return $this;
    }

    /**
     * 添加表格后内容.
     * @return $this
     */
    public function addAfterTableHtml(string $html): self
    {
        $this->afterTable[] = $html;
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
        $vars['title'] = $vars['title'] ?? $this->title;
        $vars['pageBuilder'] = $vars['pageBuilder'] ?? $this;
        $vars['pageSchema'] = $vars['pageSchema'] ?? $this->toArray();
        foreach (get_object_vars($this->class) as $k => $v) {
            $vars[$k] = $v;
        }
        $this->renderVars = $vars;
        throw new HttpResponseException(display($this->render(), $vars));
    }

    /**
     * 获取页面配置.
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'buttons' => $this->buttonItems,
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
     * 包装权限节点.
     */
    private function wrapAuth(string $html, ?string $auth): string
    {
        if (empty($auth)) {
            return $html;
        }
        if (function_exists('auth')) {
            try {
                return auth($auth) ? $html : '';
            } catch (\Throwable) {
            }
        }
        return $html;
    }

    /**
     * 属性转换.
     */
    private function attrs(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $key => $value) {
            $name = htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8');
            $html .= is_null($value) ? " {$name}" : sprintf(' %s="%s"', $name, htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'));
        }
        return ltrim($html);
    }

    /**
     * Schema 值标准化.
     * @param mixed $value
     */
    private function normalizeSchemaValue($value)
    {
        if ($value instanceof PageBuilderRaw) {
            return ['type' => 'js', 'code' => $value->value];
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
     * 渲染页面.
     */
    private function render(): string
    {
        $html = '<div class="layui-card">';
        if ($this->title !== '' || count($this->buttons) > 0) {
            $html .= "\n\t" . '<div class="layui-card-header">';
            if ($this->title !== '') {
                $html .= sprintf('<span class="layui-icon font-s10 color-desc margin-right-5">&#xe65b;</span>%s', $this->title);
            }
            if (count($this->buttons) > 0) {
                $html .= sprintf('<div class="pull-right">%s</div>', join("\n", $this->buttons));
            }
            $html .= '</div>';
        }
        $html .= "\n\t" . '<div class="layui-card-line"></div>';
        $html .= "\n\t" . '<div class="layui-card-body">';
        $html .= "\n\t\t" . '<div class="layui-card-table">';
        if (($message = strval($this->renderVars['showErrorMessage'] ?? '')) !== '') {
            $html .= "\n\t\t\t\t" . sprintf(
                '<div class="think-box-notify" type="error"><b>%s</b><span>%s</span></div>',
                htmlspecialchars('系统提示：', ENT_QUOTES, 'UTF-8'),
                $message
            );
        }
        if ($this->contentClass !== '') {
            $html .= "\n\t\t\t" . sprintf('<div class="%s">', htmlspecialchars($this->contentClass, ENT_QUOTES, 'UTF-8'));
        }
        if (count($this->beforeTable)) {
            $html .= "\n\t\t\t\t" . join("\n\t\t\t\t", $this->beforeTable);
        }
        if ($search = $this->renderSearchForm()) {
            $html .= "\n\t\t\t\t" . $search;
        }
        $html .= "\n\t\t\t\t" . $this->renderTable();
        if (count($this->afterTable)) {
            $html .= "\n\t\t\t\t" . join("\n\t\t\t\t", $this->afterTable);
        }
        if ($this->contentClass !== '') {
            $html .= "\n\t\t\t" . '</div>';
        }
        $html .= "\n\t\t" . '</div>';
        $html .= "\n\t" . '</div>';
        $html .= $this->renderTemplates();
        $html .= $this->renderScripts();
        $html .= "\n\t" . $this->renderSchemaScript();
        return $html . "\n</div>";
    }

    /**
     * 渲染搜索表单.
     */
    private function renderSearchForm(): string
    {
        if (count($this->searchFields) < 1) {
            return '';
        }
        $attrs = array_merge([
            'action' => $this->searchAttrs['action'] ?? $this->resolveCurrentUrl(),
            'data-table-id' => $this->tableId,
            'autocomplete' => 'off',
            'class' => trim(($this->searchAttrs['class'] ?? '') . ' layui-form layui-form-pane form-search'),
            'method' => 'get',
            'onsubmit' => 'return false',
        ], $this->searchAttrs);
        $attrs['class'] = trim(($attrs['class'] ?? '') . ' layui-form layui-form-pane form-search');
        $fields = [];
        $hasSubmit = false;
        foreach ($this->searchFields as $field) {
            $fields[] = $this->renderSearchField($field);
            $hasSubmit = $hasSubmit || $field['type'] === 'submit';
        }
        if (!$hasSubmit) {
            $fields[] = $this->renderSearchField(['type' => 'submit', 'label' => '搜 索', 'attrs' => []]);
        }
        $html = '';
        if ($this->searchLegendEnabled) {
            $html .= '<fieldset><legend>' . htmlspecialchars($this->searchLegend, ENT_QUOTES, 'UTF-8') . '</legend>';
        }
        $html .= sprintf('<form %s>', $this->attrs($attrs));
        $html .= "\n\t" . join("\n\t", array_filter($fields));
        $html .= "\n</form>";
        if ($this->searchLegendEnabled) {
            $html .= '</fieldset>';
        }
        return $html;
    }

    /**
     * 解析当前 URL.
     */
    private function resolveCurrentUrl(): string
    {
        return url()->build();
    }

    /**
     * 渲染搜索字段.
     */
    private function renderSearchField(array $field): string
    {
        $field = array_merge([
            'type' => 'input',
            'name' => '',
            'label' => '',
            'placeholder' => '',
            'attrs' => [],
            'options' => [],
            'source' => '',
            'class' => '',
            'wrapClass' => '',
        ], $field);
        $type = strtolower((string)$field['type']);
        $attrs = is_array($field['attrs']) ? $field['attrs'] : [];
        if ($type === 'hidden') {
            $attrs['type'] = 'hidden';
            $attrs['name'] = $field['name'];
            $attrs['value'] = $attrs['value'] ?? $this->searchValue($field['name']);
            return sprintf('<input %s>', $this->attrs($attrs));
        }
        if ($type === 'submit') {
            $attrs['class'] = trim(($attrs['class'] ?? '') . ' layui-btn layui-btn-primary');
            $label = $field['label'] ?: '搜 索';
            return '<div class="layui-form-item layui-inline"><button ' . $this->attrs($attrs) . '><i class="layui-icon">&#xe615;</i> ' . $label . '</button></div>';
        }
        $wrapClass = trim("layui-form-item layui-inline {$field['wrapClass']}");
        $label = $field['label'];
        if ($type === 'select') {
            $attrs['name'] = $field['name'];
            $attrs['class'] = trim(($attrs['class'] ?? '') . ' layui-select');
            $html = '<div class="' . $wrapClass . '">';
            if ($label !== '') {
                $html .= '<label class="layui-form-label">' . $label . '</label>';
            }
            $html .= '<div class="layui-input-inline">';
            $html .= '<select ' . $this->attrs($attrs) . '>';
            $html .= '<option value="">-- 全部 --</option>';
            $html .= $this->renderSearchOptions($field);
            $html .= '</select></div></div>';
            return $html;
        }
        $attrs['name'] = $field['name'];
        $attrs['value'] = $attrs['value'] ?? $this->searchValue($field['name']);
        $attrs['placeholder'] = $field['placeholder'] ?: ($label === '' ? '' : "请输入{$label}");
        $attrs['class'] = trim(($attrs['class'] ?? '') . ' layui-input');
        $html = '<div class="' . $wrapClass . '">';
        if ($label !== '') {
            $html .= '<label class="layui-form-label">' . $label . '</label>';
        }
        $html .= '<label class="layui-input-inline"><input ' . $this->attrs($attrs) . '></label></div>';
        return $html;
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
     * 渲染搜索下拉选项.
     */
    private function renderSearchOptions(array $field): string
    {
        $name = $field['name'];
        $html = '';
        $current = $this->searchValue($name);
        foreach ($this->resolveSearchOptions($field) as $value => $label) {
            $value = htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$label, ENT_QUOTES, 'UTF-8');
            $selected = strval($current) !== '' && strval($current) === html_entity_decode($value, ENT_QUOTES, 'UTF-8') ? ' selected' : '';
            $html .= sprintf('<option%s value="%s">%s</option>', $selected, $value, $label);
        }
        return $html;
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
        $attrs = array_merge([
            'id' => $this->tableId,
            'data-url' => $this->tableUrl ?? $this->resolveCurrentUrl(),
        ], $this->tableAttrs);
        if (count($this->searchFields) > 0 && !isset($attrs['data-target-search'])) {
            $attrs['data-target-search'] = 'form.form-search';
        }
        return sprintf('<table %s></table>', $this->attrs($attrs));
    }

    /**
     * 渲染模板片段.
     */
    private function renderTemplates(): string
    {
        $html = '';
        if (count($this->rowActions) > 0 && !isset($this->templates[$this->toolbarId])) {
            $this->templates[$this->toolbarId] = join("\n", $this->rowActions);
        }
        foreach ($this->templates as $id => $tpl) {
            $html .= sprintf("\n<script type=\"text/html\" id=\"%s\">\n%s\n</script>", htmlspecialchars((string)$id, ENT_QUOTES, 'UTF-8'), $tpl);
        }
        return $html;
    }

    /**
     * 渲染脚本.
     */
    private function renderScripts(): string
    {
        $options = $this->buildTableOptions();
        $script = "\n<script>\n    $(function () {";
        foreach ($this->bootScripts as $line) {
            $script .= "\n        {$line}";
        }
        $script .= "\n        let \$table = $('#" . addslashes($this->tableId) . "').layTable(" . $this->encodeJs($options) . ');';
        foreach ($this->initScripts as $line) {
            $script .= "\n        {$line}";
        }
        $script .= "\n    });\n</script>";
        foreach ($this->scripts as $line) {
            $script .= "\n<script>\n{$line}\n</script>";
        }
        return $script;
    }

    /**
     * JS 值编码.
     * @param mixed $value
     */
    private function encodeJs($value): string
    {
        if ($value instanceof PageBuilderRaw) {
            return $value->value;
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

    /**
     * 渲染页面配置 JSON.
     */
    private function renderSchemaScript(): string
    {
        $json = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
        return $json ? sprintf('<script type="application/json" class="page-builder-schema">%s</script>', $json) : '';
    }
}
