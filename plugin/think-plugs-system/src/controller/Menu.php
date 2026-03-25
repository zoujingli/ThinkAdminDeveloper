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

use plugin\system\builder\MenuBuilder;
use plugin\system\model\SystemMenu;
use plugin\system\service\MenuService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 系统菜单管理.
 * @class Menu
 */
class Menu extends Controller
{
    /**
     * 系统菜单管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $context = MenuService::buildIndexContext();
        SystemMenu::mQuery()->layTable(function () use ($context) {
            $this->respondWithPageBuilder(MenuBuilder::buildIndexPage($context), $context);
        }, static function (QueryHelper $query) use ($context) {
            MenuService::applyIndexQuery($query, $context);
        });
    }

    /**
     * 添加系统菜单.
     * @auth true
     */
    public function add()
    {
        $this->handleForm('add');
    }

    /**
     * 编辑系统菜单.
     * @auth true
     */
    public function edit()
    {
        $this->handleForm('edit');
    }

    /**
     * 修改菜单状态.
     * @auth true
     */
    public function state()
    {
        SystemMenu::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统菜单.
     * @auth true
     */
    public function remove()
    {
        SystemMenu::mDelete();
    }

    /**
     * 列表数据处理.
     */
    protected function _index_page_filter(array &$data)
    {
        MenuService::filterIndexData($data);
    }

    private function handleForm(string $action): void
    {
        try {
            $context = MenuService::buildFormContext($action);
            $builder = MenuBuilder::buildForm($context);

            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, MenuService::loadFormData($context));
            }

            $data = MenuService::prepareFormData($builder->validate(), $context);
            MenuService::saveFormData($data);
            $this->success('数据保存成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (Exception|\Throwable $exception) {
            $this->error($exception->getMessage());
        }
    }
}
