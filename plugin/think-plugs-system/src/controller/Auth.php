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

use plugin\system\builder\AuthBuilder;
use plugin\system\model\SystemAuth;
use plugin\system\service\AuthService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统权限管理.
 * @class Auth
 */
class Auth extends Controller
{
    /**
     * 系统权限管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $context = AuthService::buildIndexContext();
        SystemAuth::mQuery()->layTable(function () use ($context) {
            $this->respondWithPageBuilder(AuthBuilder::buildIndexPage($context), $context);
        }, static function (QueryHelper $query) use ($context) {
            AuthService::applyIndexQuery($query, $context);
        });
    }

    /**
     * 修改权限状态
     * @auth true
     */
    public function state()
    {
        SystemAuth::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除系统权限.
     * @auth true
     */
    public function remove()
    {
        SystemAuth::mDelete();
    }

    /**
     * 添加系统权限.
     * @auth true
     */
    public function add()
    {
        $this->handleForm('add');
    }

    /**
     * 编辑系统权限.
     * @auth true
     */
    public function edit()
    {
        $this->handleForm('edit');
    }

    /**
     * 列表数据处理.
     */
    protected function _page_filter(array &$data)
    {
        $data = SystemAuth::appendPlugins($data);
    }

    private function handleForm(string $action): void
    {
        $context = AuthService::buildFormContext($action);
        $builder = AuthBuilder::buildForm($context);

        try {
            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, AuthService::loadFormData($context));
            }
            if ($this->request->post('action') === 'json') {
                $this->success('获取权限节点成功！', AuthService::loadFormTree($context));
            }
            $data = AuthService::prepareFormData($builder->validate(), $context);
            AuthService::saveFormData($data);
            $this->success('权限修改成功！', 'javascript:history.back()');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
