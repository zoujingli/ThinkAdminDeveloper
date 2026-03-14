<?php

declare(strict_types=1);

namespace plugin\system\model;

use think\admin\Model;

class SystemMenu extends Model
{
    protected $updateTime = false;

    protected $oplogName = '系统菜单';

    protected $oplogType = '系统菜单管理';
}
