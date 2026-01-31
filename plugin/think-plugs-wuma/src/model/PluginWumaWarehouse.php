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

namespace plugin\wuma\model;

use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 总部仓库模型管理.
 *
 * @property int $deleted 删除状态(0未删1已删)
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
