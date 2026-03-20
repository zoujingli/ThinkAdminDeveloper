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

use think\model\relation\HasOne;

/**
 * 区块链溯源模型.
 *
 * @property string $delete_time 删除时间
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
 * @property PluginWumaSourceCertificate $cert
 * @class PluginWumaSourceBlockchain
 */
class PluginWumaSourceBlockchain extends AbstractPrivate
{
    /**
     * 关联证书数据.
     */
    public function cert(): HasOne
    {
        $relation = $this->hasOne(PluginWumaSourceCertificate::class, 'id', 'scid');
        $relation->where(['status' => 1]);
        return $relation;
    }
}
