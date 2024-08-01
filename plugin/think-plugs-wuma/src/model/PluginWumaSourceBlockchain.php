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

use think\model\relation\HasOne;

/**
 * 区块链溯源模型
 * @class PluginWumaSourceBlockchain
 * @package plugin\wuma\model
 */
class PluginWumaSourceBlockchain extends AbstractPrivate
{
    /**
     * 关联证书数据
     * @return \think\model\relation\HasOne
     */
    public function cert(): HasOne
    {
        $one = $this->hasOne(PluginWumaSourceCertificate::class, 'id', 'scid');
        return $one->where(['status' => 1, 'deleted' => 0]);
    }
}