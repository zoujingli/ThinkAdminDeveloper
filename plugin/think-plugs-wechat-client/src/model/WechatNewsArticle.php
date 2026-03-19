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
 * 微信图文详细模型.
 *
 * @property int $id
 * @property int $read_num 阅读数量
 * @property int $show_cover_pic 显示封面(0不显示,1显示)
 * @property string $author 文章作者
 * @property string $content 图文内容
 * @property string $content_source_url 原文地址
 * @property string $create_time 创建时间
 * @property string $digest 摘要内容
 * @property string $local_url 永久素材URL
 * @property string $title 素材标题
 * @class WechatNewsArticle
 */
class WechatNewsArticle extends Model {}
