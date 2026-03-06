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
 * 鐗╃爜鏌ヨ楠岃瘉璁板綍.
 *
 * @property int $auid 浠ｇ悊鐢ㄦ埛
 * @property int $code 灏忕爜鏁扮爜
 * @property int $id
 * @property int $times 鏌ヨ娆℃暟
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $encode 鐗╃爜缂栧彿
 * @property string $ghash 鍟嗗搧缂栧彿
 * @property string $update_time 鏇存柊鏃堕棿
 * @class PluginWumaSourceQueryVerify
 */
class PluginWumaSourceQueryVerify extends AbstractPrivate
{
    protected $deleteTime = false;

}
