<?php

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

namespace plugin\wemall\controller\user;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallUserTransfer;
use plugin\wemall\service\UserTransfer;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\admin\service\AdminService;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 代理提现管理.
 * @class Transfer
 */
class Transfer extends Controller
{
    /**
     * 提现转账方案.
     * @var array
     */
    protected $types = [];

    /**
     * 代理提现配置.
     * @throws Exception
     */
    public function config()
    {
        $this->skey = 'plugin.wemall.transfer.config';
        $this->title = '代理提现配置';
        $this->_sysdata();
    }

    /**
     * 微信转账配置.
     * @throws Exception
     */
    public function payment()
    {
        $this->skey = 'plugin.wemall.transfer.wxpay';
        $this->title = '微信提现配置';
        $this->_sysdata();
    }

    /**
     * 代理提现管理.
     * @menu true
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserTransfer::mQuery()->layTable(function () {
            $this->title = '代理提现管理';
            $this->transfer = UserTransfer::amount(0);
        }, static function (QueryHelper $query) {
            // 数据列表处理
            $query->with(['user'])->equal('type,status')->dateBetween('create_time');
            // 用户条件搜索
            $db = PluginAccountUser::mQuery()->like('phone|username|nickname#user')->db();
            if ($db->getOptions('where')) {
                $query->whereRaw("unid in {$db->field('id')->buildSql()}");
            }
        });
    }

    /**
     * 提现审核操作.
     * @auth true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function audit()
    {
        if ($this->request->isGet()) {
            PluginWemallUserTransfer::mForm('audit', 'code');
        } else {
            $data = $this->_vali([
                'code.require' => '打款单号不能为空！',
                'status.require' => '交易审核操作类型！',
                'status.in:0,1,2,3,4' => '交易审核操作类型！',
                'remark.default' => '',
            ]);
            $map = ['code' => $data['code']];
            $find = PluginWemallUserTransfer::mk()->where($map)->find();
            if (empty($find)) {
                $this->error('不允许操作审核！');
            }
            // 提现状态(0已拒绝, 1待审核, 2已审核, 3打款中, 4已打款, 5已收款)
            if (in_array($data['status'], [0, 1, 2, 3])) {
                $data['last_at'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] == 4) {
                if (empty($find['trade_no'])) {
                    $data['trade_no'] = CodeExtend::uniqidDate(20);
                }
                $data['trade_time'] = date('Y-m-d H:i:s');
                $data['change_time'] = date('Y-m-d H:i:s');
                $data['change_desc'] = ($data['remark'] ?: '线下打款成功') . ' By ' . AdminService::getUserName();
            }
            if (PluginWemallUserTransfer::mk()->strict(false)->where($map)->update($data) !== false) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败！');
            }
        }
    }

    /**
     * 后台打款服务
     * @auth true
     */
    public function sync()
    {
        $this->_queue('提现到微信余额定时处理', 'xdata:mall:trans', 0, [], 0, 50);
    }

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        $this->types = UserTransfer::types;
    }

    /**
     * 配置数据处理.
     * @throws Exception
     */
    private function _sysdata()
    {
        if ($this->request->isGet()) {
            $this->data = sysdata($this->skey);
            $this->fetch('');
        } else {
            sysdata($this->skey, $this->request->post());
            $this->success('配置修改成功');
        }
    }
}
