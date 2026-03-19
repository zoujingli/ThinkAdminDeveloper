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
 * 微信媒体文件模型.
 *
 * @property int $id
 * @property string $appid 公众号ID
 * @property string $create_time 创建时间
 * @property string $local_url 本地文件链接
 * @property string $md5 文件哈希
 * @property string $media_id 永久素材MediaID
 * @property string $media_url 远程图片链接
 * @property string $type 媒体类型
 * @class WechatMedia
 */
class WechatMedia extends Model {}
