<?php

// +----------------------------------------------------------------------
// | Payment Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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
use plugin\payment\model\PluginPaymentIntegral;
use think\admin\helper\QueryHelper;

/**
 * 积分数据接口
 * @class Integral
 * @package plugin\payment\controller\api\auth
 */
class Integral extends Auth
{
    /**
     * 获取余额记录
     * @return void
     */
    public function get()
    {
        PluginPaymentIntegral::mQuery(null, function (QueryHelper $query) {
            $query->where(['unid' => $this->unid, 'deleted' => 0, 'cancel' => 0])->order('id desc');
            $this->success('获取积分记录！', $query->page(intval(input('page', 1)), false, false, 20));
        });
    }
}