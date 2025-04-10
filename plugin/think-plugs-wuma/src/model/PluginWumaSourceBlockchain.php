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

use think\model\relation\HasOne;

/**
 * 区块链溯源模型
 *
 * @property int $deleted 删除状态(0未删1已删)
 * @property int $id
 * @property int $scid 确权证书
 * @property int $sort 排序权重
 * @property int $status 记录状态(0无效1有效)
 * @property string $code 流程编号
 * @property string $create_time 创建时间
 * @property string $data 流程环节
 * @property string $hash 流程HASH
 * @property string $hash_time 上链时间
 * @property string $name 流程名称
 * @property string $remark 流程备注
 * @property string $update_time 更新时间
 * @property-read \plugin\wuma\model\PluginWumaSourceCertificate $cert
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