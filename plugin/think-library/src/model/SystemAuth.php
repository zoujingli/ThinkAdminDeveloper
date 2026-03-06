<?php

declare(strict_types=1);

namespace think\admin\model;

use think\admin\Model;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

class SystemAuth extends Model
{
    protected $updateTime = false;

    protected $oplogName = '系统权限';

    protected $oplogType = '系统权限管理';

    /**
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function items(): array
    {
        return static::mk()->where(['status' => 1])->order('sort desc,id desc')->select()->toArray();
    }

    public function onAdminDelete(string $ids)
    {
        if (count($aids = str2arr($ids)) > 0) {
            SystemNode::mk()->whereIn('auth', $aids)->delete();
        }

        sysoplog($this->oplogType, lang('删除%s[%s]及授权配置', [lang($this->oplogName), $ids]));
    }

    public function getCreateTimeAttr($value): string
    {
        return format_datetime($value);
    }
}
