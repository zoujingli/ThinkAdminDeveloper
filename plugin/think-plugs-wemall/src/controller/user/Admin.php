<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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
use plugin\wemall\model\PluginWemallConfigLevel;
use plugin\wemall\model\PluginWemallUserRelation;
use plugin\wemall\service\ConfigService;
use plugin\wemall\service\UserUpgrade;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\extend\JwtExtend;
use think\admin\helper\QueryHelper;
use think\exception\HttpResponseException;

/**
 * 会员用户管理
 * @class Admin
 * @package plugin\wemall\controller\user
 */
class Admin extends Controller
{
    /**
     * 会员用户管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginWemallUserRelation::mQuery()->layTable(function () {
            $this->title = '会员用户管理';
            $this->upgrades = PluginWemallConfigLevel::items();
        }, function (QueryHelper $query) {
            // 如果传了UNID，不展示下级及自己
            if (!empty($this->get['unid'])) {
                $query->whereNotLike("path", "%,{$this->get['unid']},%");
                $query->where(['entry_agent' => 1])->where('unid', '<>', $this->get['unid']);
            }
            $query->with(['user', 'agent1', 'agent2', 'user1', 'user2'])->equal('level_code');
            // 用户内容查询
            $user = PluginAccountUser::mQuery()->dateBetween('create_time');
            $user->equal('entry_agent')->like('code|phone|username|nickname#user');
            $user->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
            $query->whereRaw("unid in {$user->db()->field('id')->buildSql()}");
        });
    }

    /**
     * 刷新会员数据
     * @auth true
     * @return void
     */
    public function sync()
    {
        $this->_queue('刷新会员用户数据', 'xdata:mall:users');
    }

    /**
     * 编辑会员资料
     * @auth true
     * @return void
     */
    public function edit()
    {
        PluginWemallUserRelation::mQuery()->with('user')->mForm('form', 'unid');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @return void
     * @throws \think\admin\Exception
     */
    protected function _edit_form_filter(array $data)
    {
        if ($this->request->isPost()) {
            $account = Account::mk(Account::WEB, ['unid' => $data['unid']]);
            // 更新当前用户代理线，同时更新账号的 user 数据
            $account->bind(['id' => $data['unid']], $data['user'] ?? []);
            // 修改用户登录密码
            if (!empty($data['user']['password'])) {
                $account->pwdModify($data['user']['password']);
                unset($data['user']['password']);
            }
        }
    }

    /**
     * 修改用户状态
     * @auth true
     */
    public function state()
    {
        PluginAccountUser::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除用户账号
     * @auth true
     */
    public function remove()
    {
        PluginAccountUser::mDelete();
    }

    /**
     * 模拟用户登录
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     */
    public function view()
    {
        $data = $this->_vali(['unid.require' => '编号不能为空！']);
        $token = CodeExtend::encrypt($data, JwtExtend::jwtkey());
        $domain = ConfigService::get('base_domain');
        $this->redirect("{$domain}?autologin={$token}");
    }

    /**
     * 修改用户上级
     * @auth true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function parent()
    {
        if ($this->request->isGet()) {
            $this->index();
        } else try {
            $data = $this->_vali(['unid.require' => '用户编号为空！', 'puid.require' => '上级编号为空！']);
            $parent = PluginWemallUserRelation::mQuery()->where(['unid' => $data['puid']])->findOrEmpty();
            if ($parent->isEmpty()) $this->error('上级用户不存在！');
            $relation = PluginWemallUserRelation::withInit(intval($data['unid']));
            if (stripos($parent->getAttr('path'), ",{$data['unid']},") !== false) {
                $this->error('无法设置下级为自己的上级！');
            }
            $this->app->db->transaction(function () use ($relation, $parent) {
                UserUpgrade::forceReplaceParent($relation, $parent);
            });
            $this->success('更新上级成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}