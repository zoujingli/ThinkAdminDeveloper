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

declare (strict_types=1);

namespace plugin\payment\controller\api\auth;

use plugin\account\controller\api\Auth;
use plugin\payment\model\PluginPaymentAddress;

/**
 * 用户收货地址管理
 * @class Address
 * @package plugin\payment\controller\api\auth
 */
class Address extends Auth
{
    /**
     * 初始化检查
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->checkUserStatus();
    }

    /**
     * 修改地址
     * @return void
     * @throws \think\db\exception\DbException
     */
    public function set()
    {
        $data = $this->_vali([
            'id.default'          => 0,
            'unid.value'          => $this->unid,
            'type.default'        => 0,
            'idcode.default'      => '', // 身份证号码
            'idimg1.default'      => '', // 身份证正面
            'idimg2.default'      => '', // 身份证反面
            'type.in:0,1'         => '状态不在范围！',
            'user_name.require'   => '姓名不能为空！',
            'user_phone.mobile'   => '手机格式错误！',
            'user_phone.require'  => '手机不能为空！',
            'region_prov.require' => '省份不能为空！',
            'region_city.require' => '城市不能为空！',
            'region_area.require' => '区域不能为空！',
            'region_addr.require' => '地址不能为空！',
        ]);

        if (empty($data['id'])) {
            unset($data['id']);
            $map = ['unid' => $this->unid, 'deleted' => 0];
            if (PluginPaymentAddress::mk()->where($map)->count() >= 10) {
                $this->error('最多10个地址！');
            }
        }

        // 设置默认值
        $model = $this->withDefault(intval($data['id'] ?? 0), intval($data['type']), true);

        // 保存收货地址
        if ($model->save($data) && $model->isExists()) {
            $this->success('保存成功！', $model->refresh()->hidden(['deleted'])->toArray());
        } else {
            $this->error('保存失败！');
        }
    }

    /**
     * 获取地址
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get()
    {
        $query = $this->_query($this->withModel());
        $query->equal('id')->order('type desc,id desc');
        $this->success('获取地址数据！', $query->page(false, false));
    }

    /**
     * 修改地址状态
     * @return void
     */
    public function state()
    {
        $data = $this->_vali([
            'id.require'   => '编号不能为空！',
            'type.in:0,1'  => '状态不在范围！',
            'type.require' => '状态不能为空！',
        ]);

        // 检查是否存在
        $model = $this->withDefault(intval($data['id']), intval($data['type']));
        $model->isEmpty() && $this->error('地址不存在！');

        // 返回成功消息
        $this->success('设置默认成功！', $model->refresh()->toArray());
    }

    /**
     * 删除收货地址
     */
    public function remove()
    {
        $map = $this->_vali(['id.require' => '地址ID为空！']);
        $model = $this->withModel($map)->findOrEmpty();
        if ($model->isEmpty()) {
            $this->error('地址不存在！');
        } elseif ($model->save(['deleted' => 1]) !== false) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }

    /**
     * 创建数据模型
     * @param mixed $map 地址查询条件
     * @return mixed
     */
    private function withModel($map = [])
    {
        $model = PluginPaymentAddress::mk()->withoutField('deleted');
        return $model->where($map)->where(['unid' => $this->unid, 'deleted' => 0]);
    }

    /**
     * 取消默认选项
     * @param integer $id 地址编号
     * @param integer $type 是否默认
     * @param boolean $force 强制更新
     * @return PluginPaymentAddress
     */
    private function withDefault(int $id = 0, int $type = 1, bool $force = false): PluginPaymentAddress
    {
        $model = $this->withModel(['id' => $id])->findOrEmpty();
        if ($model->isExists() && intval($model->getAttr('type')) !== $type) {
            $model->save(['type' => $type]);
        }
        if (($force || $model->isExists()) && $type > 0) {
            $map = [['id', '<>', $id], ['unid', '=', $this->unid]];
            $model->newQuery()->where($map)->update(['type' => 0]);
        }
        return $model;
    }
}