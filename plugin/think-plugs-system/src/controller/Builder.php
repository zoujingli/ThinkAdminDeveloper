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

namespace plugin\system\controller;

use plugin\system\model\SystemBuilder;
use plugin\system\service\BuilderService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\FormBuilder;
use think\admin\helper\PageBuilder;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\Query;
use think\exception\HttpResponseException;

class Builder extends Controller
{
    /**
     * 动态页面构建.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        if ($this->request->request('output', 'default') !== 'default') {
            $this->renderIndexData();
            return;
        }

        $this->buildIndexPage()->fetch([
            'statusOptions' => BuilderService::statusOptions(),
            'typeOptions' => BuilderService::typeOptions(),
        ]);
    }

    /**
     * 添加动态配置.
     * @auth true
     * @throws Exception
     */
    public function add()
    {
        $this->handleForm([]);
    }

    /**
     * 编辑动态配置.
     * @auth true
     * @throws Exception
     */
    public function edit()
    {
        $this->handleForm($this->loadDefinition());
    }

    /**
     * 预览动态配置.
     * @auth true
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function preview()
    {
        $definition = $this->loadDefinition();
        if (intval($definition['status'] ?? 0) < 1) {
            $this->error('当前动态配置已禁用！');
        }

        if (($definition['type'] ?? 'form') === 'page') {
            $this->previewPage($definition);
            return;
        }

        $this->previewForm($definition);
    }

    /**
     * 读取数据表字段.
     * @auth true
     * @throws Exception
     */
    public function fields()
    {
        $table = trim(strval($this->request->get('table', '')));
        $this->success('字段读取成功！', BuilderService::tableSchema($table));
    }

    /**
     * 修改动态配置状态.
     * @auth true
     * @throws Exception
     */
    public function state()
    {
        $data = $this->_vali([
            'id.require' => '配置记录不能为空！',
            'status.require' => '状态值不能为空！',
            'status.in:0,1' => '状态值范围异常！',
        ]);

        $definition = BuilderService::findById(intval($data['id']));
        $definition['status'] = intval($data['status']);
        $this->saveDefinition($definition, intval($data['id']));
        $this->success('状态更新成功！');
    }

    /**
     * 删除动态配置.
     * @auth true
     */
    public function remove()
    {
        $id = intval($this->request->post('id', $this->request->get('id', 0)));
        $record = SystemBuilder::mk()->where(['id' => $id])->findOrEmpty();
        if ($record->isEmpty() || !str_starts_with(strval($record->getAttr('name')), BuilderService::prefix())) {
            $this->error('动态配置不存在！');
        }

        if ($record->delete() === false) {
            $this->error('动态配置删除失败！');
        }

        $record->onAdminDelete(strval($id));
        $this->success('动态配置删除成功！');
    }

    /**
     * 列表数据处理.
     */
    protected function _page_filter(array &$data)
    {
        if ($this->request->action() !== 'index') {
            return;
        }

        foreach ($data as &$item) {
            $config = BuilderService::normalizeRecord($item);
            $item = [
                'id' => intval($config['id'] ?? 0),
                'title' => strval($config['title'] ?? ''),
                'code' => strval($config['code'] ?? ''),
                'type' => strval($config['type'] ?? 'form'),
                'type_text' => BuilderService::typeOptions()[$config['type'] ?? 'form'] ?? '动态表单',
                'table_name' => strval($config['table_name'] ?? ''),
                'status' => intval($config['status'] ?? 1),
                'status_text' => intval($config['status'] ?? 1) > 0 ? '启用' : '禁用',
                'remark' => strval($config['remark'] ?? ''),
                'create_time' => strval($config['create_time'] ?? ''),
                'update_time' => strval($config['update_time'] ?? ''),
            ];
        }
    }

    /**
     * 处理配置表单.
     * @throws Exception
     */
    private function handleForm(array $definition): void
    {
        $builder = $this->buildEditForm($definition);
        if ($this->request->isGet()) {
            $value = $this->buildFormValue($definition);
            $table = strval($value['table_name'] ?? '');
            $schema = $table !== '' ? BuilderService::tableSchema($table) : ['options' => [], 'fields' => []];
            $builder->fetch([
                'vo' => $value,
                'builderTypes' => BuilderService::typeOptions(),
                'statusOptions' => BuilderService::statusOptions(),
                'tableOptions' => BuilderService::tableOptions(),
                'tableFieldOptions' => $schema['options'] ?? [],
            ]);
            return;
        }

        $data = $builder->validate();
        $id = intval($this->request->post('id', 0));
        $definition = BuilderService::buildDefinitionPayload($data);
        $this->saveDefinition($definition, $id);
        $this->success('动态配置保存成功！', url('index')->build());
    }

    /**
     * 输出列表数据.
     */
    private function renderIndexData(): void
    {
        $keyword = trim(strval($this->request->get('keyword', '')));
        $type = trim(strval($this->request->get('type', '')));
        $status = $this->request->get('status', '');
        $output = strtolower(strval($this->request->request('output', 'layui.table')));
        $items = [];

        foreach (SystemBuilder::mk()->whereLike('name', BuilderService::prefix() . '%')->order('id desc')->select()->toArray() as $row) {
            $item = BuilderService::normalizeRecord($row);
            if ($keyword !== '') {
                $search = strtolower(join("\n", [
                    strval($item['title'] ?? ''),
                    strval($item['code'] ?? ''),
                    strval($item['table_name'] ?? ''),
                    strval($item['remark'] ?? ''),
                ]));
                if (strpos($search, strtolower($keyword)) === false) {
                    continue;
                }
            }
            if ($type !== '' && strval($item['type'] ?? '') !== $type) {
                continue;
            }
            if ($status !== '' && intval($item['status'] ?? 0) !== intval($status)) {
                continue;
            }

            $items[] = [
                'id' => intval($item['id'] ?? 0),
                'title' => strval($item['title'] ?? ''),
                'code' => strval($item['code'] ?? ''),
                'type' => strval($item['type'] ?? 'form'),
                'type_text' => BuilderService::typeOptions()[$item['type'] ?? 'form'] ?? '动态表单',
                'table_name' => strval($item['table_name'] ?? ''),
                'status' => intval($item['status'] ?? 1),
                'status_text' => intval($item['status'] ?? 1) > 0 ? '启用' : '禁用',
                'remark' => strval($item['remark'] ?? ''),
                'create_time' => strval($item['create_time'] ?? ''),
                'update_time' => strval($item['update_time'] ?? ''),
            ];
        }

        $page = max(1, intval($this->request->get('page', 1)));
        $limit = max(1, intval($this->request->get('limit', 20)));
        $list = array_slice($items, ($page - 1) * $limit, $limit);

        if ($output === 'json') {
            $this->success('JSON-DATA', [
                'page' => [
                    'current' => $page,
                    'limit' => $limit,
                    'pages' => max(1, intval(ceil(count($items) / $limit))),
                    'total' => count($items),
                ],
                'list' => $list,
            ]);
        }

        throw new HttpResponseException(json([
            'code' => 0,
            'msg' => '',
            'count' => count($items),
            'data' => $list,
        ]));
    }

    /**
     * 构建列表页面.
     */
    private function buildIndexPage(): PageBuilder
    {
        $editUrl = url('edit')->build();
        $previewUrl = url('preview')->build();
        $stateUrl = url('state')->build();
        $removeUrl = url('remove')->build();
        $tableUrl = url('index', ['output' => 'layui.table'])->build();

        return PageBuilder::mk()
            ->setTitle('动态页面构建')
            ->setTable('BuilderTable', $tableUrl)
            ->addButtonHtml(sprintf('<a class="layui-btn layui-btn-sm layui-btn-primary" data-open="%s">新增动态配置</a>', url('add')->build()))
            ->addSearchInput('keyword', '关键字', '请输入配置名称或编码')
            ->addSearchSelect('type', '构建类型', BuilderService::typeOptions())
            ->addSearchSelect('status', '配置状态', BuilderService::statusOptions())
            ->addSearchSubmitButton()
            ->addColumn(['field' => 'id', 'title' => 'ID', 'width' => 80, 'sort' => true])
            ->addColumn(['field' => 'title', 'title' => '配置名称', 'minWidth' => 180])
            ->addColumn(['field' => 'code', 'title' => '配置编码', 'minWidth' => 180])
            ->addColumn(['field' => 'type_text', 'title' => '构建类型', 'width' => 120])
            ->addColumn(['field' => 'table_name', 'title' => '绑定数据表', 'minWidth' => 180])
            ->addColumn(['field' => 'status_text', 'title' => '配置状态', 'width' => 100])
            ->addColumn(['field' => 'update_time', 'title' => '更新时间', 'minWidth' => 180, 'sort' => true])
            ->addRowActionHtml(sprintf('<a class="layui-btn layui-btn-sm" data-open="%s?id={{d.id}}">预览</a>', $previewUrl))
            ->addRowActionHtml(sprintf('<a class="layui-btn layui-btn-sm layui-btn-primary" data-open="%s?id={{d.id}}">编辑</a>', $editUrl))
            ->addRowActionHtml(sprintf('{{# if(d.status > 0){ }}<a class="layui-btn layui-btn-sm layui-btn-warm" data-action="%s" data-value="id#{{d.id}};status#0">禁用</a>{{# } else { }}<a class="layui-btn layui-btn-sm layui-btn-normal" data-action="%s" data-value="id#{{d.id}};status#1">启用</a>{{# } }}', $stateUrl, $stateUrl))
            ->addRowActionHtml(sprintf('<a class="layui-btn layui-btn-sm layui-btn-danger" data-action="%s" data-confirm="确定要删除这条动态配置吗？" data-value="id#{{d.id}}">删除</a>', $removeUrl))
            ->addToolbarColumn('操作', ['minWidth' => 280]);
    }

    /**
     * 构建配置表单.
     */
    private function buildEditForm(array $definition): FormBuilder
    {
        $jsonRemark = '<span class="color-desc">规则直接使用 Builder 数组配置，可先选字段再生成默认规则。</span>';

        return FormBuilder::mk('form', 'page')
            ->addTextInput('title', '配置名称', 'Title', true, '用于后台区分当前动态页面配置。', null, [
                'maxlength' => 60,
                'required-error' => '配置名称不能为空！',
            ])
            ->addTextInput('code', '配置编码', 'Code', true, '建议使用小写字母、数字、下划线，后续可通过编码直接访问预览。', '^[a-z][a-z0-9_]{2,30}$', [
                'maxlength' => 30,
                'required-error' => '配置编码不能为空！',
                'pattern-error' => '配置编码格式错误！',
            ])
            ->addRadioInput('type', '构建类型', 'Builder Type', 'builderTypes', true, [
                'required-error' => '构建类型不能为空！',
            ])
            ->addRadioInput('status', '配置状态', 'Status', 'statusOptions', true, [
                'required-error' => '配置状态不能为空！',
            ])
            ->addSelectInput('table_name', '绑定数据表', 'Table', true, '切换数据表后会自动刷新下方可选字段。', [], 'tableOptions', [
                'required-error' => '绑定数据表不能为空！',
                'data-fields-url' => url('fields')->build(),
            ])
            ->addCheckInput('form_field_names', '表单字段选择', 'Form Fields', 'tableFieldOptions', false, [])
            ->addTextArea('form_fields_json', 'FormBuilder 规则', 'FormBuilder', false, $jsonRemark . ' <a class="layui-btn layui-btn-xs layui-btn-primary" data-builder-generate="form">按已选字段生成</a>', [
                'rows' => 16,
                'placeholder' => '请填写 FormBuilder 字段规则 JSON 数组',
            ])
            ->addCheckInput('search_field_names', '搜索字段选择', 'Search Fields', 'tableFieldOptions', false, [])
            ->addTextArea('search_fields_json', 'PageBuilder 搜索规则', 'Search Rules', false, $jsonRemark . ' <a class="layui-btn layui-btn-xs layui-btn-primary" data-builder-generate="search">按已选字段生成</a>', [
                'rows' => 14,
                'placeholder' => '请填写 PageBuilder 搜索字段规则 JSON 数组',
            ])
            ->addCheckInput('table_field_names', '列表字段选择', 'Table Fields', 'tableFieldOptions', false, [])
            ->addTextArea('table_columns_json', 'PageBuilder 列规则', 'Table Columns', false, $jsonRemark . ' <a class="layui-btn layui-btn-xs layui-btn-primary" data-builder-generate="columns">按已选字段生成</a>', [
                'rows' => 16,
                'placeholder' => '请填写 PageBuilder 列规则 JSON 数组',
            ])
            ->addTextArea('table_options_json', '高级列表配置', 'Table Options', false, '可选，直接填写 PageBuilder::setTableOptions 使用的 JSON。若需原生 JS，使用 {"type":"js","code":"function(){...}"} 结构。', [
                'rows' => 10,
                'placeholder' => '请填写 PageBuilder 表格高级配置 JSON',
            ])
            ->addTextArea('remark', '配置备注', 'Remark', false, '', [
                'rows' => 3,
                'placeholder' => '请输入配置备注',
            ])
            ->addSubmitButton()
            ->addScript($this->buildDesignerScript($definition));
    }

    /**
     * 构建设计器脚本.
     */
    private function buildDesignerScript(array $definition): string
    {
        $table = strval($definition['table_name'] ?? '');
        if ($table === '') {
            $tables = BuilderService::tableOptions();
            $table = count($tables) > 0 ? strval(array_key_first($tables)) : '';
        }
        $schema = $table !== '' ? BuilderService::tableSchema($table) : ['table' => '', 'options' => [], 'fields' => []];
        $schemaJson = json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $fieldsUrl = json_encode(url('fields')->build(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return <<<SCRIPT
(function () {
    const fieldsUrl = {$fieldsUrl};
    let currentSchema = {$schemaJson} || {table: '', options: {}, fields: {}};

    function fieldWrap(name) {
        return $('[data-field-name="' + name + '"]');
    }

    function fieldInput(name) {
        return $('[name="' + name + '"]');
    }

    function fieldChecks(name) {
        return fieldWrap(name).find('.help-checks');
    }

    function selectedNames(name) {
        return fieldWrap(name).find('input:checked').map(function () {
            return String($(this).val() || '');
        }).get();
    }

    function escapeHtml(text) {
        return String(text || '').replace(/[&<>"']/g, function (char) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[char];
        });
    }

    function renderFieldChecks(name, selected) {
        const \$box = fieldChecks(name);
        if (\$box.length < 1) {
            return;
        }

        const values = Array.isArray(selected) ? selected : [];
        const html = Object.keys(currentSchema.options || {}).map(function (key) {
            const checked = values.indexOf(key) >= 0 ? ' checked' : '';
            return '<label class="think-checkbox label-required-null">'
                + '<input type="checkbox" lay-ignore name="' + name + '[]" value="' + escapeHtml(key) + '"' + checked + '> '
                + escapeHtml(currentSchema.options[key] || key)
                + '</label>';
        }).join('');

        \$box.html(html || '<span class="color-desc">当前数据表没有可用字段。</span>');
    }

    function buildSchemaItems(name, scene) {
        return selectedNames(name).map(function (key) {
            return currentSchema.fields && currentSchema.fields[key] ? currentSchema.fields[key][scene] : null;
        }).filter(Boolean);
    }

    function prettyJson(items) {
        return items.length ? JSON.stringify(items, null, 4) : '';
    }

    function toggleByType() {
        const type = $('input[name="type"]:checked').val() || 'form';
        ['form_field_names', 'form_fields_json'].forEach(function (name) {
            fieldWrap(name).toggle(type === 'form');
        });
        ['search_field_names', 'search_fields_json', 'table_field_names', 'table_columns_json', 'table_options_json'].forEach(function (name) {
            fieldWrap(name).toggle(type === 'page');
        });
    }

    function bindGenerate(button, name, target, scene) {
        $(document).off('click.builder-' + button, '[data-builder-generate="' + button + '"]').on('click.builder-' + button, '[data-builder-generate="' + button + '"]', function () {
            fieldInput(target).val(prettyJson(buildSchemaItems(name, scene)));
        });
    }

    function refreshFieldGroups() {
        renderFieldChecks('form_field_names', selectedNames('form_field_names'));
        renderFieldChecks('search_field_names', selectedNames('search_field_names'));
        renderFieldChecks('table_field_names', selectedNames('table_field_names'));
    }

    function loadSchema(table) {
        if (!table) {
            currentSchema = {table: '', options: {}, fields: {}};
            refreshFieldGroups();
            return;
        }

        const previous = {
            form_field_names: selectedNames('form_field_names'),
            search_field_names: selectedNames('search_field_names'),
            table_field_names: selectedNames('table_field_names')
        };

        $.getJSON(fieldsUrl, {table: table}, function (response) {
            if (!response || Number(response.code || 0) !== 1 || !response.data) {
                return;
            }
            currentSchema = response.data;
            renderFieldChecks('form_field_names', previous.form_field_names);
            renderFieldChecks('search_field_names', previous.search_field_names);
            renderFieldChecks('table_field_names', previous.table_field_names);
        });
    }

    $(document).on('change', 'select[name="table_name"]', function () {
        loadSchema($(this).val());
    });
    $(document).on('change', 'input[name="type"]', toggleByType);

    bindGenerate('form', 'form_field_names', 'form_fields_json', 'form');
    bindGenerate('search', 'search_field_names', 'search_fields_json', 'search');
    bindGenerate('columns', 'table_field_names', 'table_columns_json', 'column');

    toggleByType();
    refreshFieldGroups();
})();
SCRIPT;
    }

    /**
     * 预览动态表单.
     * @throws Exception
     */
    private function previewForm(array $definition): void
    {
        $builder = FormBuilder::mk('form', 'page')
            ->setAction(url('preview', ['builder_id' => intval($definition['id'] ?? 0)])->build());
        foreach ($definition['form_fields'] as $field) {
            if (is_array($field)) {
                $builder->addField($field);
            }
        }
        $builder->addSubmitButton('保存记录');

        if ($this->request->isGet()) {
            $builder->fetch([
                'vo' => $this->loadPreviewRow($definition),
            ]);
            return;
        }

        $data = $builder->validate();
        $payload = $this->normalizePreviewPayload($definition, $data);
        $primary = $this->resolvePrimaryField($definition['table_name']);
        $query = $this->app->db->table($definition['table_name']);
        $result = isset($payload[$primary]) && strval($payload[$primary]) !== ''
            ? $query->where([$primary => $payload[$primary]])->update($payload) !== false
            : $query->insert($payload) !== false;
        if (!$result) {
            $this->error('动态表单保存失败！');
        }

        $this->success('动态表单保存成功！', $payload);
    }

    /**
     * 预览动态列表.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function previewPage(array $definition): void
    {
        if ($this->request->request('output', 'default') !== 'default') {
            $query = $this->app->db->table($definition['table_name']);
            $this->applyPreviewFilters($query, $definition['search_fields']);
            $this->applyPreviewOrder($query, $definition['table_columns']);
            $this->renderPreviewTableData($query);
            return;
        }

        $builder = PageBuilder::mk()
            ->setTitle($definition['title'])
            ->setTable('DynamicBuilderPreviewTable', $this->buildPreviewTableUrl(intval($definition['id'] ?? 0)));

        if (!empty($definition['table_options']) && is_array($definition['table_options'])) {
            $builder->setTableOptions(BuilderService::restoreJsFragments($definition['table_options']));
        }

        foreach ($definition['search_fields'] as $field) {
            if (is_array($field)) {
                $builder->addSearchField($field);
            }
        }
        foreach ($definition['table_columns'] as $column) {
            if (is_array($column)) {
                $builder->addColumn(BuilderService::restoreJsFragments($column));
            }
        }
        $builder->fetch();
    }

    /**
     * 构建表单默认值.
     */
    private function buildFormValue(array $definition): array
    {
        $tableOptions = BuilderService::tableOptions();
        $table = strval($definition['table_name'] ?? '');
        if ($table === '' && count($tableOptions) > 0) {
            $table = strval(array_key_first($tableOptions));
        }

        return [
            'id' => intval($definition['id'] ?? 0),
            'title' => strval($definition['title'] ?? ''),
            'code' => strval($definition['code'] ?? ''),
            'type' => strval($definition['type'] ?? 'form'),
            'status' => intval($definition['status'] ?? 1),
            'table_name' => $table,
            'remark' => strval($definition['remark'] ?? ''),
            'form_field_names' => $definition['form_field_names'] ?? [],
            'search_field_names' => $definition['search_field_names'] ?? [],
            'table_field_names' => $definition['table_field_names'] ?? [],
            'form_fields_json' => BuilderService::formatJson($definition['form_fields'] ?? []),
            'search_fields_json' => BuilderService::formatJson($definition['search_fields'] ?? []),
            'table_columns_json' => BuilderService::formatJson($definition['table_columns'] ?? []),
            'table_options_json' => BuilderService::formatJson($definition['table_options'] ?? []),
        ];
    }

    /**
     * 加载配置定义.
     * @throws Exception
     */
    private function loadDefinition(): array
    {
        $id = intval($this->request->param('builder_id', $this->request->param('id', 0)));
        if ($id > 0) {
            return BuilderService::findById($id);
        }

        $code = BuilderService::normalizeCode(strval($this->request->param('code', '')));
        if ($code === '') {
            throw new Exception('动态配置不存在！');
        }

        $record = SystemBuilder::mk()->where(['name' => BuilderService::dataName($code)])->findOrEmpty();
        if ($record->isEmpty()) {
            throw new Exception('动态配置不存在！');
        }

        return BuilderService::normalizeRecord($record->toArray());
    }

    /**
     * 保存配置定义.
     * @throws Exception
     */
    private function saveDefinition(array $definition, int $id = 0): int
    {
        $name = BuilderService::dataName(strval($definition['code'] ?? ''));
        $repeat = SystemBuilder::mk()->where(['name' => $name])->where('id', '<>', $id)->findOrEmpty();
        if ($repeat->isExists()) {
            throw new Exception('配置编码已经存在！');
        }

        $record = $id > 0 ? SystemBuilder::mk()->where(['id' => $id])->findOrEmpty() : SystemBuilder::mk();
        if ($id > 0 && ($record->isEmpty() || !str_starts_with(strval($record->getAttr('name')), BuilderService::prefix()))) {
            throw new Exception('动态配置不存在！');
        }

        $exists = $record->isExists();
        $saved = $record->save([
            'name' => $name,
            'value' => json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        if ($saved === false) {
            throw new Exception('动态配置保存失败！');
        }

        $exists ? $record->onAdminUpdate(strval($record->getAttr('id'))) : $record->onAdminInsert(strval($record->getAttr('id')));
        return intval($record->getAttr('id'));
    }

    /**
     * 应用动态列表过滤规则.
     */
    private function applyPreviewFilters(Query $query, array $fields): void
    {
        foreach ($fields as $field) {
            if (!is_array($field) || empty($field['name'])) {
                continue;
            }

            $source = strval($field['name']);
            $target = strval($field['field'] ?? $source);
            if (!$this->request->has($source, 'get')) {
                continue;
            }

            $value = $this->request->get($source);
            if ($value === '' || $value === null || $value === []) {
                continue;
            }
            $type = strtolower(strval($field['query'] ?? ''));

            switch ($type) {
                case 'equal':
                    $query->where($target, is_array($value) ? reset($value) : $value);
                    break;
                case 'in':
                    $query->whereIn($target, is_array($value) ? $value : explode(',', strval($value)));
                    break;
                case 'valuebetween':
                    $this->applyBetweenFilter($query, $target, strval($value));
                    break;
                case 'timebetween':
                    $this->applyBetweenFilter($query, $target, strval($value), 'time');
                    break;
                case 'datebetween':
                    $this->applyBetweenFilter($query, $target, strval($value), 'date');
                    break;
                case 'like':
                default:
                    $query->whereLike($target, '%' . strval($value) . '%');
                    break;
            }
        }
    }

    /**
     * 规范预览表单提交数据.
     */
    private function renderPreviewTableData(Query $query): void
    {
        $output = strtolower(strval($this->request->request('output', 'layui.table')));
        $page = max(1, intval($this->request->get('page', 1)));
        $limit = max(1, intval($this->request->get('limit', 20)));
        $data = $query->paginate([
            'list_rows' => $limit,
            'page' => $page,
            'query' => $this->request->get(),
        ])->toArray();

        if ($output === 'json') {
            $this->success('JSON-DATA', [
                'page' => [
                    'current' => intval($data['current_page'] ?? 1),
                    'limit' => intval($data['per_page'] ?? $limit),
                    'pages' => intval($data['last_page'] ?? 1),
                    'total' => intval($data['total'] ?? 0),
                ],
                'list' => array_values($data['data'] ?? []),
            ]);
        }

        throw new HttpResponseException(json([
            'code' => 0,
            'msg' => '',
            'count' => intval($data['total'] ?? 0),
            'data' => array_values($data['data'] ?? []),
        ]));
    }

    private function applyPreviewOrder(Query $query, array $columns): void
    {
        $field = trim(strval($this->request->get('_field_', '')));
        $order = strtolower(strval($this->request->get('_order_', '')));
        if ($field === '' || !in_array($order, ['asc', 'desc'], true)) {
            return;
        }

        $allow = [];
        foreach ($columns as $column) {
            if (is_array($column) && !empty($column['field'])) {
                $allow[] = strval($column['field']);
            }
        }
        if (!in_array($field, $allow, true)) {
            return;
        }

        $query->order($field, $order);
    }

    private function applyBetweenFilter(Query $query, string $field, string $value, string $mode = 'raw'): void
    {
        if (strpos($value, ' - ') === false) {
            return;
        }

        [$begin, $end] = explode(' - ', $value, 2);
        if ($mode === 'date') {
            $begin .= ' 00:00:00';
            $end .= ' 23:59:59';
        } elseif ($mode === 'time') {
            $begin = strval(strtotime($begin));
            $end = strval(strtotime($end));
        }

        $query->whereBetween($field, [$begin, $end]);
    }

    private function normalizePreviewPayload(array $definition, array $data): array
    {
        $primary = $this->resolvePrimaryField($definition['table_name']);
        $fields = array_keys((array)$this->app->db->getFields($definition['table_name']));
        $allowed = array_merge([$primary], array_map(static function (array $field): string {
            return strval($field['name'] ?? '');
        }, $definition['form_fields']));
        $allowed = array_values(array_filter(array_unique($allowed), static function (string $name) use ($fields): bool {
            return $name !== '' && in_array($name, $fields, true);
        }));

        $payload = [];
        foreach ($allowed as $name) {
            $field = $this->findFormField($definition['form_fields'], $name);
            if (($field['type'] ?? '') === 'checkbox' && !$this->request->has($name, 'post')) {
                $payload[$name] = BuilderService::encodeChoiceValue([]);
                continue;
            }

            if (!$this->request->has($name, 'post')) {
                continue;
            }

            $value = $this->request->post($name);
            if (($field['type'] ?? '') === 'checkbox') {
                $value = BuilderService::encodeChoiceValue($value);
            }
            $payload[$name] = $value;
        }

        if ($primary !== 'id' && $this->request->has('id', 'post') && !isset($payload[$primary])) {
            $payload[$primary] = $this->request->post('id');
        }

        return $payload;
    }

    /**
     * 加载预览表单记录.
     */
    private function loadPreviewRow(array $definition): array
    {
        $primary = $this->resolvePrimaryField($definition['table_name']);
        $dataId = intval($this->request->param('data_id', $this->request->param($primary, 0)));
        if ($dataId < 1) {
            return [];
        }

        $record = $this->app->db->table($definition['table_name'])->where([$primary => $dataId])->find();
        if (!is_array($record)) {
            return [];
        }

        foreach ($definition['form_fields'] as $field) {
            if (!is_array($field) || empty($field['name'])) {
                continue;
            }
            $name = strval($field['name']);
            if (!array_key_exists($name, $record)) {
                continue;
            }
            if (($field['type'] ?? '') === 'checkbox') {
                $record[$name] = BuilderService::decodeChoiceValue($record[$name], $field);
            }
        }

        if ($primary !== 'id' && isset($record[$primary])) {
            $record['id'] = $record[$primary];
        }

        return $record;
    }

    /**
     * 查找表单字段配置.
     */
    private function findFormField(array $fields, string $name): array
    {
        foreach ($fields as $field) {
            if (is_array($field) && strval($field['name'] ?? '') === $name) {
                return $field;
            }
        }

        return [];
    }

    /**
     * 解析数据表主键.
     */
    private function resolvePrimaryField(string $table): string
    {
        return strval(BuilderService::tableSchema($table)['primary'] ?? 'id');
    }

    /**
     * 构建预览列表数据地址.
     */
    private function buildPreviewTableUrl(int $id): string
    {
        return url('preview', ['builder_id' => $id, 'output' => 'layui.table'])->build();
    }
}
