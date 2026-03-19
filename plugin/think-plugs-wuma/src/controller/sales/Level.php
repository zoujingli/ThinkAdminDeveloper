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

namespace plugin\wuma\controller\sales;

use plugin\wuma\model\PluginWumaSalesUserLevel as UserLevel;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\admin\helper\PageBuilder;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 代理等级管理.
 * @class Level
 */
class Level extends Controller
{
    /**
     * 代理等级管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        UserLevel::mQuery()->layTable(function () {
            $this->buildIndexPage()->fetch();
        });
    }

    /**
     * 添加代理等级.
     * @auth true
     * @throws DbException
     */
    public function add()
    {
        $builder = $this->buildFormBuilder();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormData()]);
        }

        $data = $builder->validate();
        $data['id'] = intval($this->request->param('id', 0));
        $this->saveLevelData($data);
    }

    /**
     * 编辑代理等级.
     * @auth true
     * @throws DbException
     */
    public function edit()
    {
        $builder = $this->buildFormBuilder();
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $this->loadFormData(intval($this->request->param('id', 0)))]);
        }

        $data = $builder->validate();
        $data['id'] = intval($this->request->param('id', 0));
        $this->saveLevelData($data);
    }

    /**
     * 表单结果处理.
     * @throws DbException
     */
    public function _form_result(bool $state)
    {
        $state && UserLevel::stepSync();
    }

    /**
     * 修改等级状态.
     * @auth true
     */
    public function state()
    {
        UserLevel::mSave($this->_vali([
            'status.require' => '修改状态不能为空！',
            'status.in:0,1' => '修改状态不在范围内！',
        ]));
    }

    /**
     * 删除代理等级.
     * @auth true
     */
    public function remove()
    {
        UserLevel::mDelete();
    }

    /**
     * 表单数据处理.
     * @throws DbException
     */
    protected function _form_filter(array &$vo)
    {
        $vo['utime'] = time();
    }

    /**
     * 状态变更处理.
     * @throws DbException
     */
    protected function _save_result()
    {
        $this->_form_result(true);
    }

    /**
     * 删除结果处理.
     * @throws DbException
     */
    protected function _delete_result()
    {
        $this->_form_result(true);
    }

    private function buildIndexPage(): PageBuilder
    {
        return PageBuilder::mk()
            ->setTitle('代理等级管理')
            ->setTable('UpgradeTable', $this->request->url())
            ->setSearchLegend('条件搜索')
            ->setTableOptions([
                'even' => true,
                'height' => 'full',
                'sort' => ['field' => 'number', 'type' => 'asc'],
            ])
            ->addModalButton('添加等级', url('add')->build(), '添加等级', ['data-table-id' => 'UpgradeTable'], 'add')
            ->addBeforeTableHtml('<div class="think-box-notify"><span>代理等级添加后，尽量不要对等级进行删除操作，否则会影响代理等级显示！</span></div>')
            ->addColumn(['field' => 'number', 'title' => '序号', 'align' => 'center', 'width' => 80, 'sort' => true])
            ->addColumn(['field' => 'name', 'title' => '等级名称', 'align' => 'center', 'minWidth' => 100])
            ->addColumn(['field' => 'remark', 'title' => '等级描述', 'align' => 'center', 'minWidth' => 100, 'templet' => '<div class="color-desc">{{d.remark||"-"}}</div>'])
            ->addColumn(['field' => 'status', 'title' => '等级状态', 'align' => 'center', 'minWidth' => 110, 'templet' => '#StatusSwitchTpl'])
            ->addColumn(['field' => 'create_time', 'title' => '创建时间', 'align' => 'center', 'minWidth' => 170, 'sort' => true])
            ->addRowModalAction('编辑', url('edit')->build() . '?id={{d.id}}', '编辑等级', [], 'edit')
            ->addRowActionButton('删除', url('remove')->build(), 'id#{{d.id}}', '确定要删除问题吗?', [], 'remove')
            ->addToolbarColumn('操作面板', ['minWidth' => 160])
            ->addTemplate('StatusSwitchTpl', '<!--{if auth("state")}--><input type="checkbox" value="{{d.id}}" lay-skin="switch" lay-text="已激活|已禁用" lay-filter="StatusSwitch" {{d.status>0?\'checked\':\'\'}}><!--{else}-->{{d.status ? \'<b class="color-green">已启用</b>\' : \'<b class="color-red">已禁用</b>\'}}<!--{/if}-->')
            ->addScript(<<<'SCRIPT'
layui.form.on('switch(StatusSwitch)', function (obj) {
    var data = {id: obj.value, status: obj.elem.checked > 0 ? 1 : 0};
    $.form.load("state", data, "post", function (ret) {
        if (ret.code < 1) $.msg.error(ret.info, 3, function () {
            $("#UpgradeTable").trigger("reload");
        });
        return false;
    }, false);
});
SCRIPT);
    }

    private function buildFormBuilder(): FormBuilder
    {
        $id = intval($this->request->param('id', 0));
        $vo = $id > 0 ? UserLevel::mk()->findOrEmpty($id) : UserLevel::mk();
        $max = $id > 0 ? UserLevel::stepMax() : UserLevel::stepMax() + 1;
        $query = ['id' => $id ?: null];
        if (!$vo->isEmpty()) {
            $query['old_number'] = intval($vo->getAttr('number'));
        }

        $options = [];
        for ($i = 0; $i <= $max; ++$i) {
            $options[(string)$i] = $id > 0 && intval($vo->getAttr('number')) === $i
                ? "当前为第 {$i} 级权限"
                : "设置为第 {$i} 级权限";
        }

        return FormBuilder::mk()
            ->setAction(url($this->request->action(), array_filter($query, static fn ($value) => $value !== null))->build())
            ->addSelectInput('number', '等级序号', 'Level Seq.', true, '序号的数字越小表示等级越低，数字越大表示等级越高。', $options)
            ->addTextInput('name', '等级名称', 'Level Name', true, '请保持等级名称不重复，代理用户将使用此名称显示区分等级。')
            ->addTextArea('remark', '等级描述', 'Level Remark', false, '等级描述仅用于后台标注该等级的用途或其他描述，其它位置不显示。')
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function loadFormData(int $id = 0): array
    {
        if ($id < 1) {
            return ['number' => UserLevel::stepMax()];
        }
        $item = UserLevel::mk()->findOrEmpty($id);
        if ($item->isEmpty()) {
            $this->error('等级记录不存在！');
        }
        return $item->toArray();
    }

    private function saveLevelData(array $data): void
    {
        $id = intval($data['id'] ?? 0);
        $item = $id > 0 ? UserLevel::mk()->findOrEmpty($id) : UserLevel::mk();
        if ($id > 0 && $item->isEmpty()) {
            $this->error('等级记录不存在！');
        }
        $item->save([
            'number' => intval($data['number'] ?? 0),
            'name' => strval($data['name'] ?? ''),
            'remark' => strval($data['remark'] ?? ''),
            'status' => intval($this->request->post('status', $item->getAttr('status') ?? 1)),
            'utime' => time(),
        ]);
        UserLevel::stepSync();
        $this->success('数据保存成功！');
    }
}
