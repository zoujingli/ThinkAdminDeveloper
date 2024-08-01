<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
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
 * 区块链确权证书模型
 * @class PluginWumaSourceCertificate
 * @package plugin\wuma\model
 */
class PluginWumaSourceCertificate extends AbstractPrivate
{

    /**
     * 获取所有证书列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function lists(): array
    {
        $map = ['status' => 1, 'deleted' => 0];
        return static::mk()->where($map)->order('sort desc,id desc')->select()->toArray();
    }

    /**
     * 格式化定位数据
     * @param mixed $value
     * @return mixed
     */
    public function getContentAttr($value)
    {
        return json_decode($value ?: '[]', true);
    }
}