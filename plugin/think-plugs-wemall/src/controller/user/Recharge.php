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
use plugin\payment\service\Balance;
use plugin\wemall\model\PluginWemallUserRecharge;
use think\admin\Controller;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;
use think\exception\HttpResponseException;

/**
 * 会员充值管理
 * @class Recharge
 * @package plugin\normal\controller\funds
 */
class Recharge extends Controller
{
    /**
     * 会员充值管理
     * @menu true
     * @auth true
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserRecharge::mQuery()->layTable(function () {
            $this->title = '会员充值管理';
            $this->total = PluginWemallUserRecharge::mk()->where(['deleted' => 0])->sum('amount');
        }, function (QueryHelper $query) {
            // 按会员资料搜索
            $user = PluginAccountUser::mQuery()->like('nickname|phone#user');
            if ($user->getOptions('where')) $query->whereRaw("unid in {$user->field('id')->buildSql()}");
            // 搜索数据表字段搜索
            $query->where(['deleted' => 0])->with('user');
            $query->like('name|remark#text')->dateBetween('create_time');
        });
    }

    /**
     * 会员充值余额
     * @auth true
     */
    public function add()
    {
        PluginWemallUserRecharge::mForm('form');
    }

    /**
     * 表单回调处理
     * @param array $data
     * @return void
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(16, 'B');
        }
        if ($this->request->isGet()) {
            $data['unid'] = $data['unid'] ?? input('unid', 0);
            $this->user = PluginAccountUser::mk()->findOrEmpty($data['unid']);
            $this->user->isEmpty() && $this->error('无效用户信息！');
        } else try {
            $data = $this->_vali([
                'name.default'   => '后台余额操作',
                'code.require'   => '单号不能为空！',
                'unid.require'   => '用户不能为空！',
                'amount.require' => '金额不能为空！',
                'remark.require' => '描述不能为空！',
            ], $data);
            if (empty(floatval($data['amount']))) {
                $this->error('充值金额不能为零！');
            }
            $this->app->db->transaction(static function () use ($data) {
                $data['create_by'] = AdminService::getUserId();
                // 创建充值记录
                PluginWemallUserRecharge::mk()->where(['code' => $data['code']])->findOrEmpty()->save($data);
                // 创建余额变更
                Balance::create(intval($data['unid']), $data['code'], $data['name'], floatval($data['amount']), $data['remark'], true);
            });
            $this->success('余额充值成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 取消余额充值
     * @auth true
     * @return void
     */
    public function remove()
    {
        try {
            $data = $this->_vali(['id.require' => '数据不为空！']);
            $recharge = PluginWemallUserRecharge::mk()->where($data)->findOrEmpty();
            if ($recharge->isEmpty()) $this->error('待删除记录不存在！');
            $this->app->db->transaction(function () use ($recharge) {
                $recharge->save([
                    'deleted'      => 1,
                    'deleted_by'   => AdminService::getUserId(),
                    'deleted_time' => date("Y-m-d H:i:s")
                ]);
                Balance::cancel($recharge->getAttr('code'));
            });
            $this->success('取消充值成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}