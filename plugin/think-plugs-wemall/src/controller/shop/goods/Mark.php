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

namespace plugin\wemall\controller\shop\goods;

use plugin\wemall\model\PluginWemallGoodsMark;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\admin\helper\PageBuilder;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商品标签管理.
 * @class Mark
 */
class Mark extends Controller
{
    /**
     * 商品标签管理.
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallGoodsMark::mQuery($this->get)->layTable(function () {
            if ($this->request->action() === 'index') {
                $this->buildIndexPage()->fetch();
            }
        }, static function (QueryHelper $query) {
            $query->like('name')->equal('status')->dateBetween('create_time');
        });
    }

    /**
     * 添加商品标签.
     * @auth true
     */
    public function add()
    {
        $builder = $this->buildFormBuilder();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormData()]);
        }

        $data = $builder->validate();
        $data['id'] = intval($this->request->param('id', 0));
        $this->saveMarkData($data);
    }

    /**
     * 编辑商品标签.
     * @auth true
     */
    public function edit()
    {
        $builder = $this->buildFormBuilder();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormData(intval($this->request->param('id', 0)))]);
        }

        $data = $builder->validate();
        $data['id'] = intval($this->request->param('id', 0));
        $this->saveMarkData($data);
    }

    /**
     * 修改商品标签状态.
     * @auth true
     */
    public function state()
    {
        PluginWemallGoodsMark::mSave();
    }

    /**
     * 删除商品标签.
     * @auth true
     */
    public function remove()
    {
        PluginWemallGoodsMark::mDelete();
    }

    /**
     * 商品标签选择.
     * @login true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function select()
    {
        $this->get['status'] = 1;
        $this->index();
    }

    private function buildIndexPage(): PageBuilder
    {
        return PageBuilder::mk()
            ->setTitle('商品标签管理')
            ->setTable('TagsData', $this->request->url())
            ->setSearchAttrs(['action' => $this->request->url()])
            ->setTableOptions([
                'even' => true,
                'height' => 'full',
                'sort' => ['field' => 'sort desc,id', 'type' => 'desc'],
            ])
            ->addModalButton('添加', url('add')->build(), '添加素材标签', ['data-table-id' => 'TagsData'], 'add')
            ->addSearchInput('name', '标签名称', '请输入标签名称')
            ->addSearchSelect('status', '使用状态', [0 => '已禁用的记录', 1 => '已激活的记录'])
            ->addSearchDateRange('create_time', '创建时间', '请选择创建时间')
            ->addColumn(['field' => 'id', 'title' => 'ID', 'width' => 80, 'align' => 'center', 'sort' => true])
            ->addColumn(['field' => 'sort', 'title' => '排序权重', 'width' => 100, 'align' => 'center', 'sort' => true, 'templet' => '#SortInputTagsDataTplModal'])
            ->addColumn(['field' => 'name', 'title' => '标签名称', 'minWidth' => 100])
            ->addColumn(['field' => 'status', 'title' => '状态', 'width' => 110, 'align' => 'center', 'templet' => '#StatusSwitchTagsDataTpl'])
            ->addColumn(['field' => 'create_time', 'title' => '创建时间', 'minWidth' => 170, 'align' => 'center'])
            ->addRowModalAction('编辑', url('edit')->build() . '?id={{d.id}}', '编辑标签数据', [], 'edit')
            ->addRowActionButton('删除', url('remove')->build(), 'id#{{d.id}}', '确定要删除此标签吗？', [], 'remove')
            ->addToolbarColumn('操作面板', ['minWidth' => 100])
            ->addTemplate('StatusSwitchTagsDataTpl', '<!--{if auth("state")}--><input type="checkbox" value="{{d.id}}" lay-skin="switch" lay-text="已激活|已禁用" lay-filter="StatusSwitchTagsData" {{-d.status>0?\'checked\':\'\'}}><!--{else}-->{{-d.status ? \'<b class="color-green">已激活</b>\' : \'<b class="color-red">已禁用</b>\'}}<!--{/if}-->')
            ->addTemplate('SortInputTagsDataTplModal', '<input type="number" min="0" data-blur-number="0" data-action-blur="{:sysuri()}" data-value="id#{{d.id}};action#sort;sort#{value}" data-loading="false" value="{{d.sort}}" class="layui-input text-center">')
            ->addScript(<<<'SCRIPT'
layui.form.on('switch(StatusSwitchTagsData)', function (obj) {
    var data = {id: obj.value, status: obj.elem.checked > 0 ? 1 : 0};
    $.form.load("state", data, "post", function (ret) {
        if (ret.code < 1) $.msg.error(ret.info, 3, function () {
            $("#TagsData").trigger("reload");
        });
        return false;
    }, false);
});
SCRIPT);
    }

    private function buildFormBuilder(): FormBuilder
    {
        $id = intval($this->request->param('id', 0));
        return FormBuilder::mk()
            ->setAction(url($this->request->action(), array_filter(['id' => $id ?: null]))->build())
            ->addTextInput('name', '标签名称', 'Mark Name', true, '<b>必填：</b>请填写标签名称，建议字符不要太长')
            ->addTextArea('desc', '标签描述', 'Mark Remark')
            ->addSubmitButton()
            ->addCancelButton('取消编辑', '确定要取消修改吗？');
    }

    private function loadFormData(int $id = 0): array
    {
        if ($id < 1) {
            return [];
        }
        $item = PluginWemallGoodsMark::mk()->findOrEmpty($id);
        if ($item->isEmpty()) {
            $this->error('标签记录不存在！');
        }
        return $item->toArray();
    }

    private function saveMarkData(array $data): void
    {
        $id = intval($data['id'] ?? 0);
        $item = $id > 0 ? PluginWemallGoodsMark::mk()->findOrEmpty($id) : PluginWemallGoodsMark::mk();
        if ($id > 0 && $item->isEmpty()) {
            $this->error('标签记录不存在！');
        }
        $item->save([
            'name' => strval($data['name'] ?? ''),
            'desc' => strval($data['desc'] ?? ''),
            'sort' => intval($this->request->post('sort', $item->getAttr('sort') ?? 0)),
            'status' => intval($this->request->post('status', $item->getAttr('status') ?? 1)),
        ]);
        $this->success('数据保存成功！');
    }
}
