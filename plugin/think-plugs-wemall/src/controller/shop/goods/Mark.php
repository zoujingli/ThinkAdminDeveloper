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
use think\admin\builder\form\FormBuilder;
use think\admin\builder\page\PageBuilder;
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
        return PageBuilder::make()
            ->define(function ($page) {
                $page->title('商品标签管理')
                    ->searchAttrs(['action' => $this->request->url()])
                    ->buttons(function ($buttons) {
                        $buttons->modal('添加', url('add')->build(), '添加素材标签', ['data-table-id' => 'TagsData'], 'add');
                    })
                    ->search(function ($search) {
                        $search->input('name', '标签名称', '请输入标签名称')
                            ->select('status', '使用状态', [0 => '已禁用的记录', 1 => '已激活的记录'])
                            ->dateRange('create_time', '创建时间', '请选择创建时间');
                    })
                    ->table('TagsData', $this->request->url(), function ($table) {
                        $table->options([
                            'even' => true,
                            'height' => 'full',
                            'sort' => ['field' => 'sort desc,id', 'type' => 'desc'],
                        ])->column(['field' => 'id', 'title' => 'ID', 'width' => 80, 'align' => 'center', 'sort' => true])
                            ->sortInput('{:sysuri()}')
                            ->column(['field' => 'name', 'title' => '标签名称', 'minWidth' => 100])
                            ->statusSwitch(url('state')->build(), ['title' => '状态', 'width' => 110])
                            ->column(['field' => 'create_time', 'title' => '创建时间', 'minWidth' => 170, 'align' => 'center'])
                            ->rows(function ($rows) {
                                $rows->modal('编辑', url('edit')->build() . '?id={{d.id}}', '编辑标签数据', [], 'edit')
                                    ->action('删除', url('remove')->build(), 'id#{{d.id}}', '确定要删除此标签吗？', [], 'remove');
                            })
                            ->toolbar('操作面板', ['minWidth' => 100]);
                    });
            })
            ->build();
    }

    private function buildFormBuilder(): FormBuilder
    {
        $id = intval($this->request->param('id', 0));
        return FormBuilder::make()
            ->define(function ($form) use ($id) {
                $form->action(url($this->request->action(), array_filter(['id' => $id ?: null]))->build())
                    ->fields(function ($fields) {
                        $fields->text('name', '标签名称', 'Mark Name', true, '<b>必填：</b>请填写标签名称，建议字符不要太长')
                            ->textarea('desc', '标签描述', 'Mark Remark');
                    })->actions(function ($actions) {
                        $actions->submit()->cancel('取消编辑', '确定要取消修改吗？');
                    });
            })
            ->build();
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
