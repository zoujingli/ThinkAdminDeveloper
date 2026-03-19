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

use plugin\storage\model\SystemFile;
use plugin\system\service\SystemAuthService;
use think\admin\Controller;
use think\admin\helper\FormBuilder;
use think\admin\helper\QueryHelper;
use think\admin\Storage;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统文件管理.
 * @class File
 */
class File extends Controller
{
    /**
     * 存储类型.
     * @var array
     */
    protected $types;

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
        SystemFile::mQuery()->layTable(function () {
            $this->title = '系统文件管理';
            $this->xexts = SystemFile::mk()->distinct()->column('xext');
        }, static function (QueryHelper $query) {
            $query->like('name,hash,xext')->equal('type')->dateBetween('create_time');
            $query->where(['issafe' => 0, 'status' => 2, 'uuid' => SystemAuthService::getUserId()]);
        });
    }

    /**
     * 编辑系统文件.
     * @auth true
     */
    public function edit()
    {
        $where = [];
        if (!SystemAuthService::isSuper()) {
            $where = ['uuid' => SystemAuthService::getUserId()];
        }
        $id = intval($this->request->param('id', 0));
        if ($id < 1 || SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty()->isEmpty()) {
            $this->error('文件记录不存在！');
        }
        $builder = $this->buildEditForm();
        $file = $this->loadEditableFile($id, $where);
        if ($this->request->isGet()) {
            $builder->fetch(['vo' => $file]);
        }

        $data = $builder->validate();
        $data = [
            'id' => intval($this->request->post('id', 0)),
            'name' => strval($data['name'] ?? ''),
        ];
        SystemFile::mSave($data, '', $where);
    }

    /**
     * 删除系统文件.
     * @auth true
     */
    public function remove()
    {
        if (!SystemAuthService::isSuper()) {
            $where = ['uuid' => SystemAuthService::getUserId()];
        }
        SystemFile::mDelete('', $where ?? []);
    }

    /**
     * 清理重复文件.
     * @auth true
     * @throws DbException
     */
    public function distinct()
    {
        $map = ['issafe' => 0, 'uuid' => SystemAuthService::getUserId()];
        // 使用派生表包装子查询，避免直接引用同一表
        $keepSubQuery = SystemFile::mk()->fieldRaw('MAX(id) AS id')->where($map)->group('type, xkey')->buildSql();
        // 使用 whereNotExists 配合派生表子查询删除，避免 1093 错误和 whereIn
        SystemFile::mk()->where($map)->whereNotExists(function ($query) use ($keepSubQuery) {
            $query->table("({$keepSubQuery})")->alias('f2')->whereRaw('f2.id = system_file.id');
        })->delete();
        $this->success('清理重复文件成功！');
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

    private function buildEditForm(): FormBuilder
    {
        return FormBuilder::mk()
            ->addTextInput('name', '文件名称', 'Name', true, '', null, [
                'maxlength' => 100,
                'required-error' => '文件名称不能为空！',
            ])
            ->addTextInput('size_display', '文件大小', 'Size', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('type_display', '存储方式', 'Type', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('hash', '文件哈希', 'Hash', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addTextInput('xurl', '文件链接', 'Link', false, '', null, [
                'readonly' => null,
                'class' => 'layui-bg-gray',
            ])
            ->addSubmitButton()
            ->addCancelButton();
    }

    private function loadEditableFile(int $id, array $where): array
    {
        $file = SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty();
        $data = $file->toArray();
        $data['size_display'] = format_bytes(intval($data['size'] ?? 0));
        $data['type_display'] = $this->types[$data['type'] ?? ''] ?? strval($data['type'] ?? '');
        return $data;
    }
}
