<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\PluginAccountUser;
use plugin\account\service\Account;
use plugin\wemall\model\PluginWemallUserCreate;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\service\UserCreate;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\model\SystemUser;

/**
 * 创建会员用户
 * @class Create
 * @package plugin\wemall\controller\user
 */
class Create extends Controller
{
    /**
     * 会员用户管理
     * @return void
     * @auth true
     * @menu true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWemallUserCreate::mQuery()->layTable(function () {
            $this->title = '会员用户管理';
        }, function (QueryHelper $query) {

            $query->equal('agent_entry')->dateBetween('create_time');
            $query->with(['agent', 'user'])->like('name|phone#user')->dateBetween('create_time');
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
        });
    }

    /**
     * 创建会员用户
     * @auth true
     * @return void
     */
    public function add()
    {
        PluginWemallUserCreate::mForm('form');
    }

    /**
     * 编辑会员用户
     * @auth true
     * @return void
     */
    public function edit()
    {
        PluginWemallUserCreate::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @return void
     * @throws \think\admin\Exception
     */
    protected function _form_filter(array &$data)
    {
        // 默认头像处理
        if (empty($data['headimg'])) {
            $data['headimg'] = Account::headimg();
        }
        if ($this->request->isPost()) {
            $map = [['phone', '=', $data['phone']], ['id', '<>', $data['id'] ?? 0]];
            if (PluginWemallUserCreate::mk()->where($map)->findOrEmpty()->isExists()) {
                $this->error('该手机账号已经存在！');
            }
            if ($data['rebate_usable'] > $data['rebate_total']) {
                $this->error('剩余返佣不能大于累计返佣！');
            }
            // 代理用户检查
            if (!empty($data['agent_phone'])) {
                $map = ['phone' => $data['agent_phone'], 'deleted' => 0];
                $user = PluginAccountUser::mk()->where($map)->findOrEmpty();
                if ($user->isEmpty()) $this->error('无效推荐人');
                $relation = PluginWemallUserRelation::mk()->where(['unid' => $user->getAttr('id')])->findOrEmpty();
                if ($relation->isEmpty()) $this->error('无效推荐人');
                if (empty($relation->getAttr('entry_agent'))) $this->error('上级无代理权限！');
            }
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     * @param array $data
     * @return void
     */
    protected function _form_result(bool $result, array $data)
    {
        if ($result) try {
            UserCreate::create(intval($data['id']));
        } catch (\Exception $exception) {
            if ($exception->getCode()) {
                $this->success($exception->getMessage());
            } else {
                $this->error($exception->getMessage());
            }
        }
    }

    /**
     * 修改用户状态
     * @auth true
     * @return void
     */
    public function state()
    {
        PluginWemallUserCreate::mSave();
    }

    /**
     * 数据保存处理
     * @param boolean $result
     * @return void
     * @throws \think\admin\Exception
     */
    public function _save_result(bool $result)
    {
        if ($result) {
            $cuid = intval(input('id'));
            empty(input('status')) ? UserCreate::cancel($cuid) : UserCreate::create($cuid);
        }
    }

    /**
     * 移除会员用户
     * @auth true
     * @return void
     */
    public function remove()
    {
        PluginWemallUserCreate::mDelete();
    }
}