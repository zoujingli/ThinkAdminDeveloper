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

namespace think\admin\model;

use think\admin\extend\model\VirtualStreamModel;
use think\Model;

/**
 * 模型对象标准化工厂。
 * @class ModelFactory
 */
class ModelFactory
{
    /**
     * 统一构造模型对象，优先实例化真实模型类。
     * @param string $name 模型类名或表名
     * @param array $data 初始数据
     * @param string $conn 指定连接
     */
    public static function build(string $name, array $data = [], string $conn = ''): Model
    {
        if (strpos($name, '\\') !== false) {
            if (class_exists($name)) {
                $model = new $name($data);
                if ($model instanceof Model) {
                    return $model;
                }
            }
            $name = basename(str_replace('\\', '/', $name));
        }
        return VirtualStreamModel::mk($name, $data, $conn);
    }
}
