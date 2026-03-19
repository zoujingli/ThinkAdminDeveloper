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
