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

namespace plugin\system\model;

use think\admin\Model;
use think\model\concern\SoftDelete;
use think\model\relation\HasOne;

/**
 * 系统管理员账号模型（含软删除）。
 * @class SystemUser
 */
class SystemUser extends Model
{
    use SoftDelete;

    protected $updateTime = false;

    protected $oplogName = '系统用户';

    protected $oplogType = '系统用户管理';

    /**
     * 获取用户信息.
     */
    public static function items(mixed $map, array &$data = [], string $field = 'uuid', string $target = 'user_info', string $fields = 'username,nickname,headimg,status,delete_time'): array
    {
        $query = static::mk()->where($map)->order('sort desc,id desc');
        if (count($data) > 0) {
            $users = $query->whereIn('id', array_unique(array_column($data, $field)))->column($fields, 'id');
            foreach ($data as &$vo) {
                $vo[$target] = $users[$vo[$field]] ?? [];
            }
            return $users;
        }
        return $query->column($fields, 'id');
    }

    /**
     * 获取用户信息.
     */
    public function userinfo(): HasOne
    {
        $relation = $this->hasOne(SystemBase::class, 'code', 'usertype');
        $relation->where([
            'type' => '身份权限', 'status' => 1,
        ]);
        return $relation;
    }

    /**
     * 获取用户头像.
     */
    public function getHeadimgAttr(mixed $value): string
    {
        if (empty($value)) {
            try {
                $host = strval(sysdata('system.site.host') ?: 'https://v6.thinkadmin.top');
                return "{$host}/static/theme/img/headimg.png";
            } catch (\Exception $exception) {
                return 'https://v6.thinkadmin.top/static/theme/img/headimg.png';
            }
        }

        return $value;
    }
}
