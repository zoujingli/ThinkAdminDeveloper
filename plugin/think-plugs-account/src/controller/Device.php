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

namespace plugin\account\controller;

use plugin\account\model\PluginAccountBind;
use plugin\account\service\Account;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 终端账号管理.
 * @class Device
 */
class Device extends Controller
{
    /**
     * 终端账号管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        $this->type = $this->get['type'] ?? 'index';
        PluginAccountBind::mQuery()->layTable(function () {
            $this->title = '终端账号管理';
            $this->types = Account::types(1);
        }, function (QueryHelper $query) {
            $query->where(['deleted' => 0, 'status' => intval($this->type === 'index')]);
            $query->with('user')->equal('type#utype')->like('phone,nickname,username,create_time');
        });
    }

    /**
     * 账号接口配置.
     * @auth true
     * @throws Exception
     */
    public function config()
    {
        $this->types = Account::types();
        if ($this->request->isGet()) {
            $this->data = Account::config();
            $this->data['headimg'] = Account::headimg();
            $this->fetch();
        } else {
            // 保存当前参数
            Account::config($this->request->post());
            // 设置接口有效时间及默认头像
            $expire = $this->request->post('expire');
            $headimg = $this->request->post('headimg');
            Account::expire($expire ?: 0, $headimg ?: null);
            // 设置开放接口通道状态
            $types = $this->request->post('types', []);
            foreach ($this->types as $k => $v) {
                Account::set($k, intval(in_array($k, $types)));
            }
            if (Account::save()) {
                $this->success('配置保存成功！');
            } else {
                $this->error('配置保存失败！');
            }
        }
    }

    /**
     * 修改用户状态
     * @auth true
     */
    public function state()
    {
        PluginAccountBind::mSave($this->_vali([
            'status.in:0,1' => '状态值范围异常！',
            'status.require' => '状态值不能为空！',
        ]));
    }

    /**
     * 删除终端账号.
     * @auth true
     */
    public function remove()
    {
        PluginAccountBind::mDelete();
    }
}
