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

namespace plugin\wemall\controller\api\auth;

use plugin\wemall\controller\api\Auth;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserRebate;
use plugin\wemall\service\UserUpgrade;
use think\admin\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 会员中心.
 * @class Center
 */
class Center extends Auth
{
    /**
     * 获取会员资料.
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function get()
    {
        $user = $this->account->user()->toArray();
        if (empty($user['extra']['level_name'])) {
            UserUpgrade::recount($this->unid);
        }
        $this->success('获取资料成功！', [
            'account' => $this->account->get(false, true),
            'relation' => $this->relation->toArray(),
        ]);
    }

    /**
     * 获取会员等级.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function levels()
    {
        $this->success('获取会员等级！', UserRebate::levels());
    }

    /**
     * 获取会员折扣.
     */
    public function discount()
    {
        $data = $this->_vali(['discount.require' => '折扣不能为空！']);
        [, $rate] = UserOrder::discount(intval($data['discount']), $this->levelCode);
        $this->success('获取会员折扣！', ['rate' => strval($rate)]);
    }
}
