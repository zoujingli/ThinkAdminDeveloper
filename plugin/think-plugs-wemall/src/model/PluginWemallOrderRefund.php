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

use plugin\wemall\service\UserRefund;
use think\model\relation\HasOne;

/**
 * 鍟嗗搧璁㈠崟鍞悗妯″瀷.
 *
 * @property float $amount 鐢宠閲戦
 * @property float $balance_amount 閫€娆句綑棰? * @property float $integral_amount 閫€娆剧Н鍒? * @property float $payment_amount 閫€娆炬敮浠? * @property int $admin_by 鍚庡彴鐢ㄦ埛
 * @property int $id
 * @property int $number 閫€璐ф暟閲? * @property int $ssid 鎵€灞炲晢瀹? * @property int $status 娴佺▼鐘舵€?0宸插彇娑?1棰勮鍗?2寰呭鏍?3寰呴€€璐?4宸查€€璐?5寰呴€€娆?6宸查€€娆?7宸插畬鎴?
 * @property int $type 鐢宠绫诲瀷(1閫€璐ч€€娆?2浠呴€€娆?
 * @property int $unid 鐢ㄦ埛缂栧彿
 * @property string $balance_code 閫€鍥炲崟鍙? * @property string $code 鍞悗鍗曞彿
 * @property string $content 鐢宠璇存槑
 * @property string $create_time 鍒涘缓鏃堕棿
 * @property string $express_code 蹇€掑叕鍙? * @property string $express_name 蹇€掑悕绉? * @property string $express_no 蹇€掑崟鍙? * @property string $images 鐢宠鍥剧墖
 * @property string $integral_code 閫€鍥炲崟鍙? * @property string $order_no 璁㈠崟鍗曞彿
 * @property string $payment_code 閫€娆惧崟鍙? * @property string $phone 鑱旂郴鐢佃瘽
 * @property string $reason 閫€娆惧師鍥? * @property string $remark 鎿嶄綔鎻忚堪
 * @property string $status_at 鐘舵€佸彉鏇存椂闂? * @property string $status_ds 鐘舵€佸彉鏇存弿杩? * @property string $update_time 鏇存柊鏃堕棿
 * @property PluginWemallOrder $orderinfo
 * @class PluginWemallOrderRefund
 */
class PluginWemallOrderRefund extends AbsUser
{
    /**
     * 鑾峰彇璁㈠崟淇℃伅.
     */
    public function orderinfo(): HasOne
    {
        return $this->hasOne(PluginWemallOrder::class, 'order_no', 'order_no');
    }

    /**
     * 鏍煎紡鍖栧敭鍚庡浘鐗?
     * @param mixed $value
     */
    public function getImagesAttr($value): array
    {
        return is_string($value) ? str2arr($value, '|') : [];
    }

    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['type'])) {
            $data['typename'] = UserRefund::types[$data['type']] ?? $data['type'];
        }
        if (isset($data['reason'])) {
            $data['reasonname'] = UserRefund::reasons[$data['reason']] ?? $data['reason'];
        }
        return $data;
    }
}
