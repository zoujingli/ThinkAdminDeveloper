<?php

declare(strict_types=1);

namespace plugin\system\model;

use think\admin\Model;

class SystemNode extends Model
{
    protected $updateTime = false;

    protected $createTime = false;

    /**
     * 绑定模型名称.
     * @var string
     */
    protected $name = 'SystemAuthNode';
}
