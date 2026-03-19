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

namespace think\admin\model;

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
        return new RuntimeModel($name, $data, $conn);
    }
}
