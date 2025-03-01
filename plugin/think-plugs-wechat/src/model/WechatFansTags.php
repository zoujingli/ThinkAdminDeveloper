<?php

// +----------------------------------------------------------------------
// | Wechat Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace app\wechat\model;

use think\admin\Model;

/**
 * 微信粉丝标签模型
 *
 * @property int $count 粉丝总数
 * @property int $id
 * @property string $appid 公众号APPID
 * @property string $create_at 创建日期
 * @property string $name 标签名称
 * @class WechatFansTags
 * @package app\wechat\model
 */
class WechatFansTags extends Model
{
}