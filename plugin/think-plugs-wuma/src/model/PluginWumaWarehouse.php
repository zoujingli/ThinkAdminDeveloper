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

namespace plugin\wuma\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 总部仓库模型管理.
 *
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 记录状态(0无效1有效)
 * @property string $addr_area 所属区域
 * @property string $addr_city 所属城市
 * @property string $addr_prov 所属省份
 * @property string $addr_text 详细地址
 * @property string $code 仓库编号
 * @property string $create_time 创建时间
 * @property string $name 仓库名称
 * @property string $person 负责人
 * @property string $remark 物码描述
 * @property string $update_time 更新时间
 * @class PluginWumaWarehouse
 */
class PluginWumaWarehouse extends AbstractPrivate
{
    /**
     * 获取所有仓库数据.
     * @param mixed $map
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists($map = []): array
    {
        return static::mk()->where($map)->order('sort desc,id desc')->select()->toArray();
    }
}
