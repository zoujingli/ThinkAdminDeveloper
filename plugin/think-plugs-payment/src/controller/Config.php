<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

use plugin\account\service\Account;
use plugin\payment\model\PluginPaymentConfig;
use plugin\payment\service\Payment;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\extend\CodeExtend;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 支付配置管理.
 * @class Config
 */
class Config extends Controller
{
    /**
     * 支付配置管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginPaymentConfig::mQuery()->layTable(function () {
            $this->title = '支付配置管理';
            $this->types = Payment::types(1);
        }, function (QueryHelper $query) {
            $query->withoutField('content');
            $query->where(['status' => intval($this->type === 'index'), 'deleted' => 0]);
            $query->like('name,code')->equal('status,type#ptype')->dateBetween('create_time');
        });
    }

    /**
     * 添加支付配置.
     * @auth true
     */
    public function add()
    {
        $this->title = '添加支付配置';
        PluginPaymentConfig::mForm('form');
    }

    /**
     * 编辑支付配置.
     * @auth true
     */
    public function edit()
    {
        $this->title = '编辑支付配置';
        PluginPaymentConfig::mForm('form');
    }

    /**
     * 修改通道状态
     * @auth true
     */
    public function state()
    {
        PluginPaymentConfig::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除支付配置.
     * @auth true
     */
    public function remove()
    {
        PluginPaymentConfig::mDelete();
    }

    /**
     * 配置支付方式.
     * @auth true
     * @throws Exception
     */
    public function types()
    {
        $this->types = Payment::types();
        $this->config = sysdata('plugin.payment.config');
        if ($this->request->isGet()) {
            $this->fetch();
        } else {
            $post = $this->request->post(['types', 'integral']);
            if (($post['integral'] ?? 0) < 1) {
                $this->error('兑换积分不能少于1积分！');
            }
            sysdata('plugin.payment.config', $post);
            foreach ($this->types as $k => $v) {
                Payment::set($k, intval(in_array($k, $post['types'])));
            }
            if (Payment::save()) {
                $this->success('配置保存成功！');
            } else {
                $this->error('配置保存失败！');
            }
        }
    }

    /**
     * 获取支付配置.
     */
    protected function _page_filter(array &$data)
    {
        [$ptypes, $atypes] = [Payment::types(), Account::types(1)];
        foreach ($data as &$vo) {
            [$vo['ntype'], $vo['atype']] = [$ptypes[$vo['type']]['name'] ?? $vo['type'], []];
            if (isset($ptypes[$vo['type']])) {
                foreach ($ptypes[$vo['type']]['account'] as $account) {
                    if (isset($atypes[$account])) {
                        $vo['atype'][$account] = $atypes[$account]['name'];
                    }
                }
            }
        }
    }

    /**
     * 数据表单处理.
     */
    protected function _form_filter(array &$data)
    {
        if (empty($data['code'])) {
            $data['code'] = CodeExtend::uniqidNumber(12, 'M');
        }
        if ($this->request->isGet()) {
            $data['content'] = $data['content'] ?? [];
            [$this->payments, $types] = [[], Account::types(1)];
            foreach (Payment::types(1) as $k => $v) {
                // 屏蔽内置支付方式
                if (in_array($k, [Payment::BALANCE, Payment::INTEGRAL, Payment::COUPON])) {
                    continue;
                }
                $allow = [];
                foreach ($v['account'] as $api) {
                    if (isset($types[$api])) {
                        $allow[$api] = $types[$api]['name'];
                    }
                }
                if (empty($allow)) {
                    continue;
                }
                $this->payments[$k] = array_merge($v, ['allow' => join('、', $allow)]);
            }
        } else {
            if (empty($data['type'])) {
                $this->error('请选择支付方式！');
            }
            if (empty($data['cover'])) {
                $this->error('请上传支付图标！');
            }
            // 保存配置参数
            $data['content'] = $this->request->post();
            $fields = PluginPaymentConfig::mk()->getTableFields();
            foreach ($data['content'] as $k => $v) {
                if (in_array($k, $fields) || $v === '') {
                    unset($data['content'][$k]);
                }
            }
        }
    }

    /**
     * 处理结果处理.
     */
    protected function _form_result(bool $state)
    {
        if ($state) {
            $this->success('参数保存成功！', 'javascript:history.back()');
        }
    }
}
