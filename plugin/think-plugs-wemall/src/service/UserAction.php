<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\service;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\model\PluginWemallOrderCart;
use plugin\wemall\model\PluginWemallOrderItem;
use plugin\wemall\model\PluginWemallUserActionCollect;
use plugin\wemall\model\PluginWemallUserActionComment;
use plugin\wemall\model\PluginWemallUserActionHistory;
use think\admin\Storage;

/**
 * 用户行为数据服务
 * @class UserAction
 * @package plugin\wemall\service
 */
abstract class UserAction
{
    /**
     * 设置行为数据
     * @param integer $unid 用户编号
     * @param string $gcode 商品编号
     * @param string $type 行为类型
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function set(int $unid, string $gcode, string $type): array
    {
        $data = ['unid' => $unid, 'gcode' => $gcode];
        if ($type === 'collect') {
            $model = PluginWemallUserActionCollect::mk()->where($data)->findOrEmpty();
        } else {
            $model = PluginWemallUserActionHistory::mk()->where($data)->findOrEmpty();
        }
        $data['sort'] = time();
        $data['times'] = $model->isExists() ? $model->getAttr('times') + 1 : 1;
        $model->save($data) && UserAction::recount($unid);
        return $model->toArray();
    }

    /**
     * 删除行为数据
     * @param integer $unid 用户编号
     * @param string $gcode 商品编号
     * @param string $type 行为类型
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function del(int $unid, string $gcode, string $type): array
    {
        $data = [['unid', '=', $unid], ['gcode', 'in', str2arr($gcode)]];
        if ($type === 'collect') {
            PluginWemallUserActionCollect::mk()->where($data)->delete();
        } else {
            PluginWemallUserActionHistory::mk()->where($data)->delete();
        }
        self::recount($unid);
        return $data;
    }

    /**
     * 清空行为数据
     * @param integer $unid 用户编号
     * @param string $type 行为类型
     * @return array
     * @throws \think\db\exception\DbException
     */
    public static function clear(int $unid, string $type): array
    {
        $data = [['unid', '=', $unid]];
        if ($type === 'collect') {
            PluginWemallUserActionCollect::mk()->where($data)->delete();
        } else {
            PluginWemallUserActionHistory::mk()->where($data)->delete();
        }
        self::recount($unid);
        return $data;
    }

    /**
     * 刷新用户行为统计
     * @param integer $unid 用户编号
     * @param array|null $data 非数组时更新数据
     * @return array [collect, history, mycarts]
     * @throws \think\db\exception\DbException
     */
    public static function recount(int $unid, ?array &$data = null): array
    {
        $isUpdate = !is_array($data);
        if ($isUpdate) $data = [];
        // 更新收藏及足迹数量和购物车
        $map = ['unid' => $unid];
        $data['mycarts_total'] = PluginWemallOrderCart::mk()->where($map)->sum('number');
        $data['collect_total'] = PluginWemallUserActionCollect::mk()->where($map)->count();
        $data['history_total'] = PluginWemallUserActionHistory::mk()->where($map)->count();
        if ($isUpdate && ($user = PluginAccountUser::mk()->findOrEmpty($unid))->isExists()) {
            $user->save(['extra' => array_merge($user->getAttr('extra'), $data)]);
        }
        return [$data['collect_total'], $data['history_total'], $data['mycarts_total']];
    }

    /**
     * 写入商品评论
     * @param PluginWemallOrderItem $item
     * @param string|float $rate
     * @param string $content
     * @param string $images
     * @return bool
     * @throws \think\admin\Exception
     */
    public static function comment(PluginWemallOrderItem $item, $rate, string $content, string $images): bool
    {
        // 图片上传转存
        if (!empty($images)) {
            $images = explode('|', $images);
            foreach ($images as &$image) {
                $image = Storage::saveImage($image, 'comment')['url'];
            }
            $images = join('|', $images);
        }
        // 根据单号+商品规格查询评论
        $code = md5("{$item->getAttr('order_no')}#{$item->getAttr('ghash')}");
        return PluginWemallUserActionComment::mk()->where(['code' => $code])->findOrEmpty()->save([
            'code'     => $code,
            'unid'     => $item->getAttr('unid'),
            'gcode'    => $item->getAttr('gcode'),
            'ghash'    => $item->getAttr('ghash'),
            'order_no' => $item->getAttr('order_no'),
            'rate'     => $rate,
            'images'   => $images,
            'content'  => $content,
        ]);
    }
}