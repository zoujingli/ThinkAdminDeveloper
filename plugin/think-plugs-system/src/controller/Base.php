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

use plugin\system\builder\BaseBuilder;
use plugin\system\model\SystemBase;
use plugin\system\service\BaseService;
use think\admin\Controller;
use think\admin\Exception;
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
        $context = BaseService::buildIndexContext();
        SystemBase::mQuery()->layTable(function () use ($context) {
            $this->respondWithPageBuilder(BaseBuilder::buildIndexPage($context), $context);
        }, static function (QueryHelper $query) use ($context) {
            BaseService::applyIndexQuery($query, $context);
        });
    }

    /**
     * 添加数据字典.
     * @auth true
     */
    public function add()
    {
        $this->handleForm('add');
    }

    /**
     * 编辑数据字典.
     * @auth true
     */
    public function edit()
    {
        $this->handleForm('edit');
    }

    /**
     * 修改数据状态.
     * @auth true
     */
    public function state()
    {
        SystemBase::mSave($this->_vali([
            'status.in:0,1' => lang('状态值范围异常！'),
            'status.require' => lang('状态值不能为空！'),
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

    private function handleForm(string $action): void
    {
        $context = BaseService::buildFormContext($action);
        $builder = BaseBuilder::buildForm($context);
        try {
            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, BaseService::loadFormData($context));
            }
            $data = BaseService::prepareFormData($builder->validate(), $context);
            BaseService::saveFormData($data);
            $this->success(lang('数据保存成功！'));
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
