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
 * Class plugin\wemall\model\PluginWemallOrderCart.
 *
 * @property int $id
 * @property int $number 鍟嗗搧鏁伴噺
 * @property int $ssid 鎵€灞炲晢瀹? * @property int $unid 鐢ㄦ埛缂栧彿
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $gcode 鍟嗗搧缂栧彿
 * @property string $ghash 瑙勬牸鍝堝笇
 * @property string $gspec 鍟嗗搧瑙勬牸
 * @property string $update_time 鏇存柊鏃堕棿
 * @property PluginWemallGoods $goods
 * @property PluginWemallGoodsItem $specs
 */
class PluginWemallOrderCart extends AbsUser
{
    protected $deleteTime = false;

    /**
     * 鍏宠仈浜у搧鏁版嵁.
     */
    public function goods(): HasOne
    {
        return $this->hasOne(PluginWemallGoods::class, 'code', 'gcode');
    }

    /**
     * 鍏宠仈瑙勬牸鏁版嵁.
     */
    public function specs(): HasOne
    {
        return $this->hasOne(PluginWemallGoodsItem::class, 'ghash', 'ghash');
    }
}
