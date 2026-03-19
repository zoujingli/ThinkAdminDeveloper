<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdminDeveloper
 * +----------------------------------------------------------------------
 * | Copyright (c) 2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | Official Website: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | Licensed: https://mit-license.org
 * | Disclaimer: https://thinkadmin.top/disclaimer
 * | Vip Rights: https://thinkadmin.top/vip-introduce
 * +----------------------------------------------------------------------
 * | Gitee Repository: https://gitee.com/zoujingli/ThinkAdmin
 * | Github Repository: https://github.com/zoujingli/ThinkAdmin
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
    /**
     * 鍏宠仈鍟嗗搧淇℃伅.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }
}
