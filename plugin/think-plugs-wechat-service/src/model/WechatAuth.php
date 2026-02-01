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

namespace plugin\wechat\service\model;

use think\admin\Model;

/**
 * Class WechatAuth.
 *
 * @property int $auth_time 授权时间
 * @property int $deleted 删除状态(0未删除,1已删除)
 * @property int $expires_in Token时限
 * @property int $id
 * @property int $status 授权状态(0已取消,1已授权)
 * @property int $total 统计调用次数
 * @property string $appkey 应用接口KEY
 * @property string $appuri 应用接口URI
 * @property string $authorizer_access_token 授权Token
 * @property string $authorizer_appid 微信APPID
 * @property string $authorizer_refresh_token 刷新Token
 * @property string $businessinfo 业务序列内容
 * @property string $create_at 创建时间
 * @property string $func_info 公众号集权
 * @property string $miniprograminfo 小程序序列内容
 * @property string $qrcode_url 公众号二维码
 * @property string $service_type 公众号类型
 * @property string $service_verify 公众号认证
 * @property string $user_alias 公众号别名
 * @property string $user_company 公众号公司
 * @property string $user_headimg 公众号头像
 * @property string $user_name 众众号原账号
 * @property string $user_nickname 公众号昵称
 * @property string $user_signature 公众号描述
 */
class WechatAuth extends Model {}
