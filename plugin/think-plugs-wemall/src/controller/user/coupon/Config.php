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

namespace plugin\wemall\controller\user\coupon;

use plugin\wemall\model\PluginWemallConfigCoupon;
use plugin\wemall\model\PluginWemallConfigLevel;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 抵扣卡券管理
 * @class Config
 * @package plugin\wemall\controller\user\coupon
 */
class Config extends Controller
{
    /**
     * 抵扣卡券管理
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
        PluginWemallConfigCoupon::mQuery()->layTable(function () {
            $this->title = '抵扣卡券管理';
            $this->types = PluginWemallConfigCoupon::types;
        }, function (QueryHelper $query) {
            $query->like('name')->equal('status,type#mtype')->dateBetween('create_time');
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
        });
    }

    /**
     * 添加抵扣卡券
     * @auth true
     */
    public function add()
    {
        $this->title = '添加抵扣卡券';
        PluginWemallConfigCoupon::mForm('form');
    }

    /**
     * 编辑抵扣卡券
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑抵扣卡券';
        PluginWemallConfigCoupon::mForm('form');
    }

    /**
     * 表单数据处理
     * @param array $data
     * @return void
     */
    protected function _form_filter(array &$data)
    {
        if ($this->request->isGet()) {
            $this->types = PluginWemallConfigCoupon::types;
            $this->levels = PluginWemallConfigLevel::items();
            array_unshift($this->levels, ['name' => '全部', 'number' => '-']);
        } else {
            $data['levels'] = arr2str($data['levels'] ?? []);
        }
    }

    /**
     * 表单结果处理
     * @param boolean $result
     * @return void
     */
    protected function _form_result(bool $result)
    {
        if ($result) {
            $this->success('卡券保存成功！', 'javascript:history.back()');
        } else {
            $this->error('卡券保存失败！');
        }
    }

    /**
     * 修改抵扣卡券
     * @auth true
     */
    public function state()
    {
        PluginWemallConfigCoupon::mSave($this->_vali([
            'status.in:0,1'  => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除抵扣卡券
     * @auth true
     */
    public function remove()
    {
        PluginWemallConfigCoupon::mDelete();
    }
}