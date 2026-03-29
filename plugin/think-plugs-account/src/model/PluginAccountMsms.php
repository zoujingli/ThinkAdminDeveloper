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
class PluginAccountMsms extends PlainAbs
{
    /**
     * 格式化数据.
     */
    public function toArray(): array
    {
        $data = parent::toArray();
        if (isset($data['scene'])) {
            $data['scene_name'] = Message::sceneLabel(strval($data['scene']));
        }
        return $data;
    }
}
