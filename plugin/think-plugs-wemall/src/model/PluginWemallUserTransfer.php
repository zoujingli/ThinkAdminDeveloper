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

namespace plugin\wemall\model;

use plugin\wemall\service\UserTransfer;

/**
 * 浠ｇ悊鎻愮幇鏁版嵁.
 *
 * @property float $amount 鎻愮幇杞处閲戦
 * @property float $charge_amount 鎻愮幇鎵嬬画璐归噾棰? * @property float $charge_rate 鎻愮幇鎵嬬画璐规瘮渚? * @property int $audit_status 瀹℃牳鐘舵€? * @property int $id
 * @property int $status 鎻愮幇鐘舵€?0澶辫触,1寰呭鏍?2宸插鏍?3鎵撴涓?4宸叉墦娆?5宸叉敹娆?
 * @property int $unid 鐢ㄦ埛UNID
 * @property string $alipay_code 鏀粯瀹濊处鍙? * @property string $alipay_user 鏀粯瀹濆鍚? * @property string $appid 鍏紬鍙稟PPID
 * @property string $audit_remark 瀹℃牳鎻忚堪
 * @property string $audit_time 瀹℃牳鏃堕棿
 * @property string $bank_bran 寮€鎴峰垎琛屽悕绉? * @property string $bank_code 寮€鎴烽摱琛屽崱鍙? * @property string $bank_name 寮€鎴烽摱琛屽悕绉? * @property string $bank_user 寮€鎴疯处鍙峰鍚? * @property string $bank_wseq 寰俊閾惰缂栧彿
 * @property string $change_desc 澶勭悊鎻忚堪
 * @property string $change_time 澶勭悊鏃堕棿
 * @property string $code 鎻愮幇鍗曞彿
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $date 鎻愮幇鏃ユ湡
 * @property string $openid 鍏紬鍙稯PENID
 * @property string $qrcode 鏀舵鐮佸浘鐗囧湴鍧€
 * @property string $remark 鎻愮幇鎻忚堪
 * @property string $trade_no 浜ゆ槗鍗曞彿
 * @property string $trade_time 鎵撴鏃堕棿
 * @property string $type 鎻愮幇鏂瑰紡
 * @property string $update_time 鏇存柊鏃堕棿
 * @property string $username 鍏紬鍙风湡瀹炲鍚? * @class PluginWemallUserTransfer
 */
class PluginWemallUserTransfer extends AbsUser
{
    protected $deleteTime = false;

    /**
     * 鏍煎紡鍖栬緭鍑烘椂闂?
     * @param mixed $value
     */
    public function getChangeTimeAttr($value): string
    {
        return format_datetime($value);
    }

    /**
     * 鑷姩鏄剧ず绫诲瀷鍚嶇О.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $map = ['platform' => '骞冲彴鍙戞斁'];
            $data['type_name'] = $map[$data['type']] ?? (UserTransfer::types[$data['type']] ?? $data['type']);
        }
        return $data;
    }
}
