<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 开源协议 ( https://mit-license.org )
 * | 免责声明 ( https://thinkadmin.top/disclaimer )
 * | 会员特权 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 代码仓库：https://gitee.com/zoujingli/ThinkAdmin
 * | github 代码仓库：https://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\payment\controller;

use plugin\account\model\PluginAccountUser;
use plugin\payment\model\PluginPaymentIntegral;
use plugin\payment\service\Integral as IntegralService;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpResponseException;

/**
 * 积分明细管理.
 * @class Integral
 */
class Integral extends Controller
{
    /**
     * 积分明细管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginPaymentIntegral::mQuery()->layTable(function () {
            $this->title = '积分明细管理';
            $map = ['cancel' => 0, 'deleted' => 0];
            $this->integralTotal = PluginPaymentIntegral::mk()->where($map)->whereRaw('amount>0')->sum('amount');
            $this->integralCount = PluginPaymentIntegral::mk()->where($map)->whereRaw('amount<0')->sum('amount');
        }, function (QueryHelper $query) {
            $db = PluginAccountUser::mQuery()->like('email|nickname|username|phone#user')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
            $query->with(['user'])->like('code,remark')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'cancel' => intval($this->type !== 'index')]);
        });
    }

    /**
     * 交易锁定处理.
     * @auth true
     */
    public function unlock()
    {
        try {
            $data = $this->_vali([
                'code.require' => '单号不能为空！',
                'unlock.require' => '状态不能为空！',
            ]);
            IntegralService::unlock($data['code'], intval($data['unlock']));
            $this->success('交易操作成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 交易状态处理.
     * @auth true
     */
    public function cancel()
    {
        try {
            $data = $this->_vali([
                'code.require' => '单号不能为空！',
                'cancel.require' => '状态不能为空！',
            ]);
            IntegralService::cancel($data['code'], intval($data['cancel']));
            $this->success('交易操作成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 删除余额记录.
     * @auth true
     */
    public function remove()
    {
        try {
            $data = $this->_vali([
                'code.require' => '单号不能为空！',
            ]);
            IntegralService::remove($data['code']);
            $this->success('交易操作成功！');
        } catch (HttpResponseException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }
}
