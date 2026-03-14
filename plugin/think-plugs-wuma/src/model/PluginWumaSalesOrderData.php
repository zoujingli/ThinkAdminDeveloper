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

namespace plugin\wuma\model;

/**
 * Class plugin\wuma\model\PluginWumaSalesOrderData.
 *
 * @property int $auid 缁忛攢鍟嗙紪鍙? * @property int $id
 * @property int $mode 鎿嶄綔鏂瑰紡(1鎵爜,2铏氭嫙)
 * @property int $number 鐗╃爜鎬绘暟
 * @property int $status 璁板綍鐘舵€?0鏃犳晥,1鏈夋晥)
 * @property int $xuid 鏉ユ簮缁忛攢鍟? * @property string $code 鎿嶄綔鍗曞彿
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $update_time 鏇存柊鏃堕棿
 */
class PluginWumaSalesOrderData extends AbstractPrivate
{
    protected $deleteTime = false;
}
