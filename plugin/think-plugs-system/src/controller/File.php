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

use plugin\system\model\SystemFile;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\admin\helper\QueryHelper;
use think\admin\runtime\SystemContext;
use think\admin\Storage;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统文件管理.
 */
class File extends Controller
{
    protected array $types = [];

    /**
     * 系统文件管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->authorizeView();
        SystemFile::mQuery()->layTable(function () {
            $this->title = '系统文件管理';
            $this->xexts = SystemFile::mk()->distinct()->column('xext');
        }, function (QueryHelper $query) {
            $query->like('name,hash,xext')->equal('type')->dateBetween('create_time');
            $query->where([
                'issafe' => 0,
                'status' => 2,
                'uuid' => SystemContext::instance()->getUserId(),
            ]);
        });
    }

    /**
     * 编辑文件信息.
     * @auth true
     */
    public function edit()
    {
        $this->authorizeManage();
        $where = $this->buildManageWhere();
        $id = intval($this->request->param('id', 0));
        if ($id < 1 || SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty()->isEmpty()) {
            $this->error('File record does not exist.');
        }

        $builder = $this->buildEditForm();
        $file = $this->loadEditableFile($id, $where);
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $file]);
            return;
        }

        $data = $builder->validate();
        SystemFile::mSave([
            'id' => intval($this->request->post('id', 0)),
            'name' => strval($data['name'] ?? ''),
        ], '', $where);
    }

    /**
     * 移除文件记录.
     * @auth true
     */
    public function remove()
    {
        $this->authorizeManage();
        SystemFile::mDelete('', $this->buildManageWhere());
    }

    /**
     * 文件记录去重.
     * @auth true
     * @throws DbException
     */
    public function distinct()
    {
        $this->authorizeManage();
        $map = ['issafe' => 0, 'uuid' => SystemContext::instance()->getUserId()];
        $keepSubQuery = SystemFile::mk()->fieldRaw('MAX(id) AS id')->where($map)->group('type, xkey')->buildSql();
        SystemFile::mk()->where($map)->whereNotExists(function ($query) use ($keepSubQuery) {
            $query->table("({$keepSubQuery})")->alias('f2')->whereRaw('f2.id = system_file.id');
        })->delete();
        $this->success('Duplicate files cleared.');
    }

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        $this->types = Storage::types();
    }

    /**
     * 数据列表处理.
     */
    protected function _page_filter(array &$data)
    {
        foreach ($data as &$vo) {
            $vo['ctype'] = $this->types[$vo['type']] ?? $vo['type'];
        }
    }

    /**
     * 权限异常处理.
     */
    private function authorizeView(): void
    {
        if ($this->canView()) {
            return;
        }
        $this->error('Permission denied.');
    }

    private function authorizeManage(): void
    {
        if ($this->canManage()) {
            return;
        }
        $this->error('Permission denied.');
    }

    private function canView(): bool
    {
        $context = SystemContext::instance();
        return $context->isSuper() || $context->check('system/file/index') || $this->canManage();
    }

    /**
     * 是否拥有权限.
     */
    private function canManage(): bool
    {
        $context = SystemContext::instance();
        return $context->isSuper()
            || $context->check('system/file/edit')
            || $context->check('system/file/remove')
            || $context->check('system/file/distinct');
    }

    /**
     * 生成管理员条件.
     */
    private function buildManageWhere(): array
    {
        return SystemContext::instance()->isSuper() ? [] : ['uuid' => SystemContext::instance()->getUserId()];
    }

    /**
     * 生成编辑表单.
     */
    private function buildEditForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('name', 'File Name', 'Name', true, '', null, [
                'maxlength' => 100,
                'required-error' => 'File name is required.',
            ])
            ->addTextInput('size_display', 'File Size', 'Size', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('type_display', 'Storage Driver', 'Type', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('hash', 'File Hash', 'Hash', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('xurl', 'File URL', 'Link', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addSubmitButton()
            ->addCancelButton();
    }

    /**
     * 加载可编辑文件.
     */
    private function loadEditableFile(int $id, array $where): array
    {
        $file = SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty();
        $data = $file->toArray();
        $data['size_display'] = format_bytes(intval($data['size'] ?? 0));
        $data['type_display'] = $this->types[$data['type'] ?? ''] ?? strval($data['type'] ?? '');
        return $data;
    }
}
