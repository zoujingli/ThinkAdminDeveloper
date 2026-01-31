<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

use plugin\account\model\PluginAccountUser;
use plugin\wemall\service\UserAgent;
use plugin\wemall\service\UserOrder;
use plugin\wemall\service\UserUpgrade;
use think\admin\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\model\relation\HasOne;

/**
 * 用户关系数据.
 *
 * @property int $agent_level_code 代理等级
 * @property int $agent_state 绑定状态
 * @property int $agent_uuid 绑定用户
 * @property int $entry_agent 推广权益(0无,1有)
 * @property int $entry_member 入会礼包(0无,1有)
 * @property int $id
 * @property int $layer 所属层级
 * @property int $level_code 会员等级
 * @property int $puid1 上1级代理
 * @property int $puid2 上2级代理
 * @property int $puid3 上3级代理
 * @property int $puids 绑定状态
 * @property int $sort 排序权重
 * @property int $unid 当前用户
 * @property string $agent_level_name 代理名称
 * @property string $create_time 创建时间
 * @property string $extra 扩展数据
 * @property string $level_name 会员名称
 * @property string $path 关系路径
 * @property string $update_time 更新时间
 * @property PluginAccountUser $user1
 * @property PluginAccountUser $user2
 * @property PluginWemallUserRelation $agent1
 * @property PluginWemallUserRelation $agent2
 * @class PluginWemallUserRelation
 */
class PluginWemallUserRelation extends AbsUser
{
    /**
     * 关联上1级用户.
     */
    public function user1(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'puid1');
    }

    /**
     * 关联上2级用户.
     */
    public function user2(): HasOne
    {
        return $this->hasOne(PluginAccountUser::class, 'id', 'puid2');
    }

    /**
     * 关联上1级关系.
     */
    public function agent1(): HasOne
    {
        return $this->hasOne(PluginWemallUserRelation::class, 'unid', 'puid1');
    }

    /**
     * 关联上2级关系.
     */
    public function agent2(): HasOne
    {
        return $this->hasOne(PluginWemallUserRelation::class, 'unid', 'puid2');
    }

    /**
     * 更新用户推荐关系.
     * @param int $unid 用户编号
     * @return $this
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function withInit(int $unid): PluginWemallUserRelation
    {
        $user = PluginAccountUser::mk()->findOrEmpty($unid);
        if ($user->isEmpty()) {
            throw new Exception('无效的用户！');
        }
        if ($user->getAttr('deleted') > 0) {
            throw new Exception('账号已删除！');
        }
        $rela = static::mk()->lock(true)->where(['unid' => $unid])->findOrEmpty();
        if ($rela->isEmpty() || empty($rela->getAttr('path')) || empty($rela->getAttr('level_name'))) {
            $data = ['id' => $unid, 'unid' => $unid, 'path' => ',', 'level_name' => '普通会员', 'agent_level_name' => '普通用户'];
            if (!($rela->isExists() && $rela->save($data))) {
                // ON DUPLICATE KEY UPDATE 实现 MySQL 不重复插入
                $rela->duplicate($data)->insert($data);
                $rela = $rela->where(['unid' => $unid])->findOrEmpty();
            }
            UserOrder::entry(UserUpgrade::upgrade(UserAgent::upgrade($rela)));
        }
        return $rela;
    }

    /**
     * 转换用户关联模型.
     * @param int|PluginWemallUserRelation $unid
     * @return array [Relation, UNID]
     * @throws Exception
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function withRelation($unid): array
    {
        if (is_numeric($unid)) {
            return [self::withInit(intval($unid)), intval($unid)];
        }
        if ($unid instanceof self) {
            return [$unid, intval($unid->getAttr('unid'))];
        }
        throw new Exception('无效的参数数据！');
    }
}
