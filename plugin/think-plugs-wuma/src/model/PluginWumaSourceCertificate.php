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
 * 区块链确权证书模型.
 *
 * @property int $deleted 删除状态(0未删1已删)
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 记录状态(0无效1有效)
 * @property int $times 访问次数
 * @property mixed $content 定制规则
 * @property string $code 模板编号
 * @property string $create_time 创建时间
 * @property string $image 证书底图
 * @property string $name 模板名称
 * @property string $update_time 更新时间
 * @class PluginWumaSourceCertificate
 */
class PluginWumaSourceCertificate extends AbstractPrivate
{
    /**
     * 获取所有证书列表.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function lists(): array
    {
        $map = ['status' => 1, 'deleted' => 0];
        return static::mk()->where($map)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 格式化定位数据.
     * @param mixed $value
     * @return mixed
     */
    public function getContentAttr($value)
    {
        return json_decode($value ?: '[]', true);
    }
}
