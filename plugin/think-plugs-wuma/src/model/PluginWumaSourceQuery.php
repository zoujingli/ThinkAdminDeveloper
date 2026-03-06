<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
 * +----------------------------------------------------------------------
 * | 鐗堟潈鎵€鏈?2014~2026 ThinkAdmin [ thinkadmin.top ]
 * +----------------------------------------------------------------------
 * | 瀹樻柟缃戠珯: https://thinkadmin.top
 * +----------------------------------------------------------------------
 * | 寮€婧愬崗璁?( https://mit-license.org )
 * | 鍏嶈矗澹版槑 ( https://thinkadmin.top/disclaimer )
 * | 浼氬憳鐗规潈 ( https://thinkadmin.top/vip-introduce )
 * +----------------------------------------------------------------------
 * | gitee 浠ｇ爜浠撳簱锛歨ttps://gitee.com/zoujingli/ThinkAdmin
 * | github 浠ｇ爜浠撳簱锛歨ttps://github.com/zoujingli/ThinkAdmin
 * +----------------------------------------------------------------------
 */

namespace plugin\wuma\model;

/**
 * 闃蹭吉鏌ヨ璁板綍妯″瀷.
 *
 * @property int $auid 浠ｇ悊鐢ㄦ埛
 * @property int $code 灏忕爜鏁扮爜
 * @property int $id
 * @property int $notify 绐滆揣鐘舵€? * @property int $times 鏌ヨ娆℃暟
 * @property string $addr 璇︾粏鍦板潃
 * @property string $area 鎵€鍦ㄥ尯鍩? * @property string $city 鎵€鍦ㄥ煄甯? * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $encode 鐗╃爜缂栧彿
 * @property string $geoip 璁块棶IP
 * @property string $ghash 鍟嗗搧鍝堝笇
 * @property string $gtype 瀹氫綅绫诲瀷
 * @property string $latlng 缁忕含搴? * @property string $prov 鎵€鍦ㄧ渷浠? * @property string $update_time 鏇存柊鏃堕棿
 * @class PluginWumaSourceQuery
 */
class PluginWumaSourceQuery extends AbstractPrivate
{
    protected $deleteTime = false;

}
