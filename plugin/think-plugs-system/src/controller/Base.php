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

use plugin\system\model\SystemBase;
use plugin\system\service\BaseBuilder;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 数据字典管理.
 * @class Base
 */
class Base extends Controller
{
    /**
     * 数据字典管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        SystemBase::mQuery()->layTable(function () {
            $this->title = '数据字典管理';
            $this->types = SystemBase::types();
            $this->type = $this->get['type'] ?? ($this->types[0] ?? '-');
            $this->pluginGroups = SystemBase::groups($this->type);
            BaseBuilder::buildIndexPage($this->type, $this->types, $this->pluginGroups, $this->request->url())->fetch([
                'types' => $this->types,
                'type' => $this->type,
            ]);
        }, static function (QueryHelper $query) {
            $query->equal('type');
            $query->like('code,name,status')->dateBetween('create_time');
            if ($group = trim(strval(input('get.plugin_group', '')))) {
                $ids = SystemBase::idsByPluginGroup($group, strval(input('get.type', '')));
                empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
            }
        });
    }

    /**
     * 添加数据字典.
     * @auth true
     */
    public function add()
    {
        $builder = $this->buildFormBuilder();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormData()]);
        }

        $data = $this->prepareSaveData($builder->validate());
        $data['id'] = intval($this->request->param('id', 0));
        $this->assertUniqueCode($data);
        $this->saveFormData($data);
    }

    /**
     * 编辑数据字典.
     * @auth true
     */
    public function edit()
    {
        $builder = $this->buildFormBuilder();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormData(intval($this->request->param('id', 0)))]);
        }

        $data = $this->prepareSaveData($builder->validate());
        $data['id'] = intval($this->request->param('id', 0));
        $this->assertUniqueCode($data);
        $this->saveFormData($data);
    }

    /**
     * 修改数据状态.
     * @auth true
     */
    public function state()
    {
        SystemBase::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除数据记录.
     * @auth true
     */
    public function remove()
    {
        SystemBase::mDelete();
    }

    /**
     * 表单数据处理.
     * @throws DbException
     */
    protected function _form_filter(array &$data) {}

    /**
     * 列表数据处理.
     */
    protected function _page_filter(array &$data)
    {
        $data = SystemBase::appendPlugins($data);
    }

    private function prepareSaveData(array $data): array
    {
        $data['content'] = SystemBase::packContent(
            strval($data['content_text'] ?? $this->request->post('content_text', '')),
            $data['plugin_code'] ?? $this->request->post('plugin_code', '')
        );
        unset($data['content_text'], $data['plugin_code'], $data['type_select']);
        return $data;
    }

    private function assertUniqueCode(array $data): void
    {
        $exists = SystemBase::mk()
            ->where([
                'code' => strval($data['code'] ?? ''),
                'type' => strval($data['type'] ?? ''),
            ])
            ->where('id', '<>', intval($data['id'] ?? 0))
            ->count();
        if ($exists > 0) {
            $this->error('数据编码已经存在！');
        }
    }

    private function saveFormData(array $data): void
    {
        $id = intval($data['id'] ?? 0);
        $item = $id > 0 ? SystemBase::mk()->findOrEmpty($id) : SystemBase::mk();
        if ($id > 0 && $item->isEmpty()) {
            $this->error('数据记录不存在！');
        }

        $item->save([
            'type' => strval($data['type'] ?? ''),
            'code' => strval($data['code'] ?? ''),
            'name' => strval($data['name'] ?? ''),
            'content' => strval($data['content'] ?? ''),
            'sort' => intval($this->request->post('sort', $item->getAttr('sort') ?? 0)),
            'status' => intval($this->request->post('status', $item->getAttr('status') ?? 1)),
        ]);
        $this->success('数据保存成功！');
    }

    private function buildIndexPage(): PageBuilder
    {
        return PageBuilder::mk()
            ->setTitle('数据字典管理')
            ->setContentClass('')
            ->withSearchLegend(false)
            ->setTable('BaseTable', $this->request->url())
            ->setSearchAttrs(['action' => $this->request->url()])
            ->setTableOptions([
                'even' => true,
                'height' => 'full',
                'sort' => ['field' => 'sort desc,id', 'type' => 'asc'],
            ])
            ->addModalButton('添加数据', url('add', ['type' => $this->type])->build(), '', ['data-table-id' => 'BaseTable'], 'add')
            ->addBatchActionButton('批量删除', url('remove')->build(), 'id#{id}', '确定要批量删除数据吗？', [], 'remove')
            ->addBeforeTableHtml($this->renderTypeTabs())
            ->addAfterTableHtml('</div></div>')
            ->addSearchHidden('type', strval($this->type))
            ->addSearchInput('code', '数据编码', '请输入数据编码')
            ->addSearchInput('name', '数据名称', '请输入数据名称')
            ->addSearchSelect('status', '使用状态', [0 => '已禁用记录', 1 => '已激活记录'])
            ->addSearchSelect('plugin_group', '所属插件', $this->buildPluginGroupOptions($this->pluginGroups))
            ->addSearchDateRange('create_time', '创建时间', '请选择创建时间')
            ->addCheckboxColumn()
            ->addColumn(['field' => 'sort', 'title' => '排序权重', 'width' => 100, 'align' => 'center', 'sort' => true, 'templet' => '#SortInputTpl'])
            ->addColumn(['field' => 'code', 'title' => '数据编码', 'width' => '20%', 'align' => 'left'])
            ->addColumn(['field' => 'name', 'title' => '数据名称', 'width' => '30%', 'align' => 'left'])
            ->addColumn(['field' => 'plugin_title', 'title' => '所属插件', 'minWidth' => 130, 'align' => 'center', 'templet' => '#PluginBaseTableTpl'])
            ->addColumn(['field' => 'status', 'title' => '数据状态', 'minWidth' => 110, 'align' => 'center', 'templet' => '#StatusSwitchTpl'])
            ->addColumn(['field' => 'create_time', 'title' => '创建时间', 'minWidth' => 170, 'align' => 'center', 'sort' => true])
            ->addRowModalAction('编辑', url('edit')->build() . '?id={{d.id}}', '编辑数据', [], 'edit')
            ->addRowActionButton('删除', url('remove')->build(), 'id#{{d.id}}', '确定要删除数据吗？', [], 'remove')
            ->addToolbarColumn('数据操作', ['minWidth' => 150])
            ->addTemplate('SortInputTpl', '<input type="number" min="0" data-blur-number="0" data-action-blur="{:sysuri()}" data-value="id#{{d.id}};action#sort;sort#{value}" data-loading="false" value="{{d.sort}}" class="layui-input text-center">')
            ->addTemplate('PluginBaseTableTpl', $this->renderPluginTemplate())
            ->addTemplate('StatusSwitchTpl', '<!--{if auth("state")}--><input type="checkbox" value="{{d.id}}" lay-skin="switch" lay-text="已激活|已禁用" lay-filter="StatusSwitch" {{-d.status>0?\'checked\':\'\'}}><!--{else}-->{{-d.status ? \'<b class="color-green">已启用</b>\' : \'<b class="color-red">已禁用</b>\'}}<!--{/if}-->')
            ->addScript(<<<'SCRIPT'
layui.form.on('switch(StatusSwitch)', function (obj) {
    var data = {id: obj.value, status: obj.elem.checked > 0 ? 1 : 0};
    $.form.load("state", data, "post", function (ret) {
        if (ret.code < 1) $.msg.error(ret.info, 3, function () {
            $("#BaseTable").trigger("reload");
        });
        return false;
    }, false);
});
SCRIPT);
    }

    private function buildFormBuilder(): FormBuilder
    {
        $id = intval($this->request->param('id', 0));
        $isEdit = $id > 0;
        $types = SystemBase::types();
        $typeOptions = BaseBuilder::buildTypeOptions($types);

        $builder = FormBuilder::mk()->setAction(url($this->request->action(), array_filter([
            'id' => $id ?: null,
            'type' => strval($this->request->param('type', '')),
        ]))->build());

        if ($isEdit) {
            $builder->addTextInput('type', '数据类型', 'Data Type', true, '请选择数据类型，数据创建后不能再次修改哦~', null, [
                'readonly' => null,
                'class' => 'think-bg-gray',
            ]);
        } else {
            $builder->addSelectInput('type_select', '数据类型', 'Data Type', false, '请选择数据类型，数据创建后不能再次修改哦~', $typeOptions, '', [
                'lay-filter' => 'BaseTypeSelect',
            ])->addTextInput('type', '数据类型', 'Data Type', true, '请输入新的数据类型，数据创建后不能再次修改哦 ~', null, [
                'maxlength' => 20,
                'placeholder' => '请输入数据类型',
            ])->addScript(<<<'SCRIPT'
var $typeSelect = $('[name="type_select"]');
var $typeField = $('[data-field-name="type"]');
function syncBaseTypeField(value) {
    if (value === '--- 新增类型 ---') {
        $typeField.removeClass('layui-hide').find('input').val('').focus();
    } else {
        $typeField.addClass('layui-hide').find('input').val(value || '');
    }
}
syncBaseTypeField($typeSelect.val());
layui.form.on('select(BaseTypeSelect)', function (data) {
    syncBaseTypeField(data.value);
});
SCRIPT);
        }

        $codeAttrs = ['maxlength' => 100];
        if ($isEdit) {
            $codeAttrs['readonly'] = null;
            $codeAttrs['class'] = 'think-bg-gray';
        }

        return $builder
            ->addTextInput('code', '数据编码', 'Data Code', true, '请输入新的数据编码，数据创建后不能再次修改，同种数据类型的数据编码不能出现重复 ~', null, $codeAttrs)
            ->addTextInput('name', '数据名称', 'Data Name', true, '请输入当前数据名称，请尽量保持名称的唯一性，数据名称尽量不要出现重复 ~', null, ['maxlength' => 500])
            ->addSelectInput('plugin_code', '所属插件', 'Plugin Scope', false, '可选。选择后会写入插件归属元数据，适合身份权限或插件专用字典项。', BaseBuilder::buildPluginOptions())
            ->addTextArea('content_text', '数据内容', 'Data Content', false, '', ['placeholder' => '请输入数据内容'])
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function loadFormData(int $id = 0): array
    {
        $data = [];
        if ($id > 0) {
            $item = SystemBase::mk()->findOrEmpty($id);
            if ($item->isEmpty()) {
                $this->error('数据记录不存在！');
            }
            $data = $item->toArray();
        }
        $meta = SystemBase::parseContent(strval($data['content'] ?? ''));
        $codes = (array)($meta['plugin'] ?: $meta['plugins']);
        $data['plugin_code'] = count($codes) === 1 ? strval(current($codes)) : '';
        $data['content_text'] = strval($meta['text'] ?? ($data['content'] ?? ''));
        $data['type_select'] = strval($data['type'] ?? $this->request->param('type', SystemBase::types()[0] ?? ''));
        $data['type'] = strval($data['type'] ?? $this->request->param('type', ''));
        return $data;
    }
}
