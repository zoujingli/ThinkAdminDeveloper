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

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

/**
 * Class plugin\wuma\model\PluginWumaSalesUser.
 *
 * @property int $auid 上级代理
 * @property int $deleted 删除状态(0未删,1已删)
 * @property int $id
 * @property int $level 代理等级
 * @property int $master 总部账号
 * @property int $status 用户状态(1正常,0已黑)
 * @property int $super_auid 邀请上级用户
 * @property string $business 营业执照
 * @property string $code 授权编号
 * @property string $create_time 注册时间
 * @property string $date_after 结束时间
 * @property string $date_start 开始时间
 * @property string $headimg 用户头像
 * @property string $mobile 联系电话
 * @property string $password 登录密码
 * @property string $phone 用户手机
 * @property string $region_address 详细地址
 * @property string $region_area 所属区域
 * @property string $region_city 所属城市
 * @property string $region_prov 所属省份
 * @property string $remark 用户备注描述
 * @property string $super_phone 邀请上级手机
 * @property string $update_time 更新时间
 * @property string $userid 身份证号
 * @property string $username 用户姓名
 * @property PluginWumaSalesUser $sup_agent
 * @property PluginWumaSalesUserLevel $levelinfo
 * @property PluginWumaSalesUser[] $sub_agent
 */
class PluginWumaSalesUser extends AbstractPrivate
{
    /**
     * 查询指定规则的数据列表.
     * @param mixed $map
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists($map = [], string $fields = '*'): array
    {
        $query = static::mq()->field($fields)->where($map);
        return $query->order('id desc')->select()->toArray();
    }

    /**
     * 关联上级代理.
     */
    public function supAgent(): HasOne
    {
        return $this->hasOne(self::class, 'id', 'auid');
    }

    /**
     * 获取下级代理.
     */
    public function subAgent(): HasMany
    {
        return $this->hasMany(self::class, 'auid', 'id');
    }

    /**
     * 关联等级数据.
     */
    public function levelinfo(): HasOne
    {
        return $this->hasOne(PluginWumaSalesUserLevel::class, 'number', 'level');
    }
}
