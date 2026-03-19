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

/**
 * 溯源模板模块.
 *
 * @property string $delete_time 删除时间
 * @property int $id
 * @property int $sort 排序权重
 * @property int $status 记录状态(0无效,1有效)
 * @property int $times 访问次数
 * @property mixed $content 模板内容
 * @property mixed $styles 主题样式
 * @property string $code 模板编号
 * @property string $create_time 创建时间
 * @property string $name 模板名称
 * @property string $update_time 更新时间
 * @class PluginWumaSourceTemplate
 */
class PluginWumaSourceTemplate extends AbstractPrivate
{
    /**
     * 样式数据格式化.
     * @param mixed $value
     * @return mixed
     */
    public function getStylesAttr($value)
    {
        return json_decode($value ?: '{}', true);
    }

    /**
     * 内容数据格式化.
     * @param mixed $value
     * @return mixed
     */
    public function getContentAttr($value)
    {
        return json_decode($value ?: '[]', true);
    }

    /**
     * 查询指定规则的数据列表.
     * @param mixed $map
     */
    public static function lists($map = []): array
    {
        $query = static::mk()->where($map);
        return $query->order('sort desc,id desc')->column('*', 'code');
    }
}
