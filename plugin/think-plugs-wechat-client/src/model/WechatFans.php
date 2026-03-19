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

namespace plugin\wechat\client\model;

use think\admin\Model;

/**
 * 微信粉丝用户模型.
 *
 * @property int $id
 * @property int $is_black 是否为黑名单状态
 * @property int $sex 用户性别(1男性,2女性,0未知)
 * @property int $subscribe 关注状态(0未关注,1已关注)
 * @property int $subscribe_time 关注时间
 * @property string $appid 公众号APPID
 * @property string $city 用户所在城市
 * @property string $country 用户所在国家
 * @property string $create_time 创建时间
 * @property string $headimgurl 用户头像
 * @property string $language 用户的语言(zh_CN)
 * @property string $nickname 用户昵称
 * @property string $openid 粉丝openid
 * @property string $province 用户所在省份
 * @property string $qr_scene 二维码场景值
 * @property string $qr_scene_str 二维码场景内容
 * @property string $remark 备注
 * @property string $subscribe_at 关注时间
 * @property string $subscribe_scene 扫码关注场景
 * @property string $tagid_list 粉丝标签id
 * @property string $unionid 粉丝unionid
 * @class WechatFans
 */
class WechatFans extends Model {}
