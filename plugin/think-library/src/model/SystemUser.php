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

namespace think\admin\model;

use think\admin\Model;
use think\model\concern\SoftDelete;
use think\model\relation\HasOne;

/**
 * 绯荤粺鐢ㄦ埛妯″瀷.
 *
 * @property int $id
 * @property int $is_deleted 鍒犻櫎(1鍒犻櫎,0鏈垹)
 * @property int $login_num 鐧诲綍娆℃暟
 * @property int $sort 鎺掑簭鏉冮噸
 * @property int $status 鐘舵€?0绂佺敤,1鍚敤)
 * @property string $authorize 鏉冮檺鎺堟潈
 * @property string $contact_mail 鑱旂郴閭
 * @property string $contact_phone 鑱旂郴鎵嬫満
 * @property string $contact_qq 鑱旂郴QQ
 * @property string $create_at 鍒涘缓鏃堕棿
 * @property string $describe 澶囨敞璇存槑
 * @property string $headimg 澶村儚鍦板潃
 * @property string $login_at 鐧诲綍鏃堕棿
 * @property string $login_ip 鐧诲綍鍦板潃
 * @property string $nickname 鐢ㄦ埛鏄电О
 * @property string $password 鐢ㄦ埛瀵嗙爜
 * @property string $username 鐢ㄦ埛璐﹀彿
 * @property string $usertype 鐢ㄦ埛绫诲瀷
 * @property SystemBase $userinfo
 * @class SystemUser
 */
class SystemUser extends Model
{
    use SoftDelete;

    protected $updateTime = false;

    /**
     * 鏃ュ織鍚嶇О.
     * @var string
     */
    protected $oplogName = '绯荤粺鐢ㄦ埛';

    /**
     * 鏃ュ織绫诲瀷.
     * @var string
     */
    protected $oplogType = '绯荤粺鐢ㄦ埛绠＄悊';

    /**
     * 鑾峰彇鐢ㄦ埛鏁版嵁.
     * @param mixed $map 鏁版嵁鏌ヨ瑙勫垯
     * @param array $data 鐢ㄦ埛鏁版嵁闆嗗悎
     * @param string $field 鍘熷杩炲瓧娈?     * @param string $target 鍏宠仈鐩爣瀛楁
     * @param string $fields 鍏宠仈鏁版嵁瀛楁
     */
    public static function items($map, array &$data = [], string $field = 'uuid', string $target = 'user_info', string $fields = 'username,nickname,headimg,status,delete_time'): array
    {
        $query = static::mk()->where($map)->order('sort desc,id desc');
        if (count($data) > 0) {
            $users = $query->whereIn('id', array_unique(array_column($data, $field)))->column($fields, 'id');
            foreach ($users as &$user) {
                $user['deleted'] = empty($user['delete_time']) ? 0 : 1;
                $user['is_deleted'] = $user['deleted'];
            }
            foreach ($data as &$vo) {
                $vo[$target] = $users[$vo[$field]] ?? [];
            }
            return $users;
        }
        $users = $query->column($fields, 'id');
        foreach ($users as &$user) {
            $user['deleted'] = empty($user['delete_time']) ? 0 : 1;
            $user['is_deleted'] = $user['deleted'];
        }
        return $users;
    }

    /**
     * 鍏宠仈韬唤鏉冮檺.
     */
    public function userinfo(): HasOne
    {
        return $this->hasOne(SystemBase::class, 'code', 'usertype')->where([
            'type' => '韬唤鏉冮檺', 'status' => 1,
        ]);
    }

    /**
     * 榛樿澶村儚澶勭悊.
     * @param mixed $value
     */
    public function getHeadimgAttr($value): string
    {
        if (empty($value)) {
            try {
                $host = sysconf('base.site_host|raw') ?: 'https://v6.thinkadmin.top';
                return "{$host}/static/theme/img/headimg.png";
            } catch (\Exception $exception) {
                return 'https://v6.thinkadmin.top/static/theme/img/headimg.png';
            }
        } else {
            return $value;
        }
    }

    /**
     * 鏍煎紡鍖栫櫥褰曟椂闂?
     */
    public function getLoginAtAttr(string $value): string
    {
        return format_datetime($value);
    }

    /**
     * 鏍煎紡鍖栧垱寤烘椂闂?
     * @param mixed $value
     */
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}

