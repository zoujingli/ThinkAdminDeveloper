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

namespace plugin\system\model;

use think\admin\Model;
use think\model\concern\SoftDelete;
use think\model\relation\HasOne;

class SystemUser extends Model
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $defaultSoftDelete;

    protected $updateTime = false;

    /**
     * 日志名称.
     * @var string
     */
    protected $oplogName = '系统用户';

    /**
     * 日志类型.
     * @var string
     */
    protected $oplogType = '系统用户管理';

    /**
     * 获取用户数据.
     * @param mixed $map 数据查询规则
     * @param array $data 用户数据集合
     * @param string $field 外链字段
     * @param string $target 关联目标字段
     * @param string $fields 关联数据字段
     */
    public static function items($map, array &$data = [], string $field = 'uuid', string $target = 'user_info', string $fields = 'username,nickname,headimg,status,delete_time'): array
    {
        $query = static::mk()->where($map)->order('sort desc,id desc');
        if (count($data) > 0) {
            $users = $query->whereIn('id', array_unique(array_column($data, $field)))->column($fields, 'id');
            foreach ($users as &$user) {
                $user['deleted'] = empty($user['delete_time']) ? 0 : 1;
            }
            foreach ($data as &$vo) {
                $vo[$target] = $users[$vo[$field]] ?? [];
            }
            return $users;
        }
        $users = $query->column($fields, 'id');
        foreach ($users as &$user) {
            $user['deleted'] = empty($user['delete_time']) ? 0 : 1;
        }
        return $users;
    }

    /**
     * 关联身份权限.
     */
    public function userinfo(): HasOne
    {
        return $this->hasOne(SystemBase::class, 'code', 'usertype')->where([
            'type' => '身份权限', 'status' => 1,
        ]);
    }

    /**
     * 默认头像处理.
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
     * 格式化登录时间.
     */
    public function getLoginAtAttr(string $value): string
    {
        return format_datetime($value);
    }

    /**
     * 格式化创建时间.
     * @param mixed $value
     */
    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
