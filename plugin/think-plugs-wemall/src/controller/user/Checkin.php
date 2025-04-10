<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 用户免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\user;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallUserCheckin;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 用户签到管理
 * @class Checkin
 * @package plugin\wemall\controller\user
 */
class Checkin extends Controller
{
    /**
     * 用户签到管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginWemallUserCheckin::mQuery()->layTable(function () {
            $this->title = '用户签到管理';
        }, function (QueryHelper $query) {
            // 搜索数据表字段搜索
            $query->with('user')->dateBetween('create_time');
            // 按用户资料搜索
            $user = PluginAccountUser::mQuery()->like('nickname|phone#user');
            if ($user->getOptions('where')) $query->whereRaw("unid in {$user->field('id')->buildSql()}");
        });
    }

    /**
     * 签到配置管理
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     */
    public function config()
    {
        if ($this->request->isGet()) {
            $this->title = '签到参数配置';
            $this->data = sysdata(PluginWemallUserCheckin::$ckcfg);
            $this->fetch();
        } elseif ($this->request->isPost()) {
            $this->data = $this->request->post();
            $this->data['items'] = json_decode($this->data['items'] ?? '{}', true);
            sysdata(PluginWemallUserCheckin::$ckcfg, $this->data);
            $this->success('配置修改成功！', 'javascript:history.back()');
        }
    }
}