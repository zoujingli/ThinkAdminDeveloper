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

namespace plugin\wemall\model;

use think\model\relation\HasOne;

/**
 * 鐢ㄦ埛璁块棶琛屼负鏁版嵁.
 *
 * @property int $id
 * @property int $sort 鎺掑簭鏉冮噸
 * @property int $ssid 鎵€灞炲晢瀹? * @property int $times 璁板綍娆℃暟
 * @property int $unid 鐢ㄦ埛缂栧彿
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $gcode 鍟嗗搧缂栧彿
 * @property string $update_time 鏇存柊鏃堕棿
 * @property PluginWemallGoods $goods
 * @class PluginWemallUserActionHistory
 */
class PluginWemallUserActionHistory extends AbsUser
{
    protected $deleteTime = false;

    /**
     * 鍏宠仈鍟嗗搧淇℃伅.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }
}
