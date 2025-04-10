<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\model;

/**
 * 溯源模板模块
 *
 * @property int $deleted 删除状态(0未删,1已删)
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
 * @package plugin\wuma\model
 */
class PluginWumaSourceTemplate extends AbstractPrivate
{
    /**
     * 样式数据格式化
     * @param mixed $value
     * @return mixed
     */
    public function getStylesAttr($value)
    {
        return json_decode($value ?: '{}', true);
    }

    /**
     * 内容数据格式化
     * @param mixed $value
     * @return mixed
     */
    public function getContentAttr($value)
    {
        return json_decode($value ?: '[]', true);
    }

    /**
     * 查询指定规则的数据列表
     * @param mixed $map
     * @return array
     */
    public static function lists($map = []): array
    {
        $query = static::mk()->where(['deleted' => 0])->where($map);
        return $query->order('sort desc,id desc')->column('*', 'code');
    }
}