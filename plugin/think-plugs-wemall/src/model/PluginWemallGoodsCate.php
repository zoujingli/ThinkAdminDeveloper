<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | ThinkAdmin Plugin for ThinkAdmin
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

namespace plugin\wemall\model;

use plugin\account\model\Abs;
use think\admin\extend\DataExtend;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 商城商品分类数据.
 *
 * @property int $deleted 删除状态
 * @property int $id
 * @property int $pid 上级分类
 * @property int $sort 排序权重
 * @property int $status 使用状态
 * @property string $cover 分类图标
 * @property string $create_time 创建时间
 * @property string $name 分类名称
 * @property string $remark 分类描述
 * @property string $update_time 更新时间
 * @class PluginWemallGoodsCate
 */
class PluginWemallGoodsCate extends Abs
{
    /**
     * 获取上级可用选项.
     * @param int $max 最大级别
     * @param array $data 表单数据
     * @param array $parent 上级分类
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function pdata(int $max, array &$data, array $parent = []): array
    {
        $items = static::mk()->where(['deleted' => 0])->order('sort desc,id asc')->select()->toArray();
        $cates = DataExtend::arr2table(empty($parent) ? $items : array_merge([$parent], $items));
        if (isset($data['id'])) {
            foreach ($cates as $cate) {
                if ($cate['id'] === $data['id']) {
                    $data = $cate;
                }
            }
        }
        foreach ($cates as $key => $cate) {
            $isSelf = isset($data['spt'], $data['spc']) && $data['spt'] <= $cate['spt'] && $data['spc'] > 0;
            if ($cate['spt'] >= $max || $isSelf) {
                unset($cates[$key]);
            }
        }
        return $cates;
    }

    /**
     * 获取数据树.
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function dtree(): array
    {
        $query = static::mk()->where(['status' => 1, 'deleted' => 0])->order('sort desc,id desc');
        return DataExtend::arr2tree($query->withoutField('sort,status,deleted,create_time')->select()->toArray());
    }

    /**
     * 获取列表数据.
     * @param bool $simple 仅子级别
     */
    public static function items(bool $simple = false): array
    {
        $query = static::mk()->where(['status' => 1, 'deleted' => 0])->order('sort desc,id asc');
        $cates = array_column(DataExtend::arr2table($query->column('id,pid,name', 'id')), null, 'id');
        foreach ($cates as $cate) {
            isset($cates[$cate['pid']]) && $cates[$cate['id']]['parent'] = &$cates[$cate['pid']];
        }
        foreach ($cates as $key => $cate) {
            $id = $cate['id'];
            $cates[$id]['ids'][] = $cate['id'];
            $cates[$id]['names'][] = $cate['name'];
            while (isset($cate['parent']) && ($cate = $cate['parent'])) {
                $cates[$id]['ids'][] = $cate['id'];
                $cates[$id]['names'][] = $cate['name'];
            }
            $cates[$id]['ids'] = array_reverse($cates[$id]['ids']);
            $cates[$id]['names'] = array_reverse($cates[$id]['names']);
            if (isset($pky) && $simple && in_array($cates[$pky]['name'], $cates[$id]['names'])) {
                unset($cates[$pky]);
            }
            $pky = $key;
        }
        foreach ($cates as &$cate) {
            unset($cate['sps'], $cate['parent']);
        }
        return array_values($cates);
    }
}
