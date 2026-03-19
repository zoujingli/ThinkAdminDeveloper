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

use plugin\account\model\Abs;

/**
 * 鍟嗗煄鍟嗗搧鏍囩鏁版嵁.
 *
 * @property int $id
 * @property int $sort 鎺掑簭鏉冮噸
 * @property int $status 鏍囩鐘舵€?1浣跨敤,0绂佺敤)
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $name 鏍囩鍚嶇О
 * @property string $remark 鏍囩鎻忚堪
 * @property string $update_time 鏇存柊鏃堕棿
 * @class PluginWemallGoodsMark
 */
class PluginWemallGoodsMark extends Abs
{
    /**
     * 鑾峰彇鎵€鏈夋爣绛?
     */
    public static function items(): array
    {
        return static::mk()->where(['status' => 1])->order('sort desc,id desc')->column('name');
    }
}
