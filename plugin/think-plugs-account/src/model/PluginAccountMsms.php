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

namespace plugin\account\model;

use plugin\account\service\Message;

/**
 * 账号短信验证模型.
 *
 * @property int $id
 * @property int $status 短信状态(0失败,1成功)
 * @property int $unid 账号编号
 * @property int $usid 终端编号
 * @property string $create_time 创建时间
 * @property string $params 短信内容
 * @property string $phone 目标手机
 * @property string $result 返回结果
 * @property string $scene 业务场景
 * @property string $smsid 消息编号
 * @property string $type 短信类型
 * @property string $update_time 更新时间
 * @class PluginAccountMsms
 */
class PluginAccountMsms extends Abs
{
    /**
     * 格式化数据.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['scene'])) {
            $data['scene_name'] = Message::$scenes[$data['scene']] ?? $data['scene'];
        }
        return $data;
    }
}
