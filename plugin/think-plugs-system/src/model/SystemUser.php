<?php

declare(strict_types=1);

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

    protected $oplogName = '系统用户';

    protected $oplogType = '系统用户管理';

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

    public function userinfo(): HasOne
    {
        return $this->hasOne(SystemBase::class, 'code', 'usertype')->where([
            'type' => '身份权限', 'status' => 1,
        ]);
    }

    public function getHeadimgAttr($value): string
    {
        if (empty($value)) {
            try {
                $host = sysconf('base.site_host|raw') ?: 'https://v6.thinkadmin.top';
                return "{$host}/static/theme/img/headimg.png";
            } catch (\Exception $exception) {
                return 'https://v6.thinkadmin.top/static/theme/img/headimg.png';
            }
        }

        return $value;
    }
}
