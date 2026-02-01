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

namespace plugin\account\controller\api;

use app\wechat\service\WechatService;
use plugin\account\service\Account;
use think\admin\Controller;
use think\admin\Exception;
use think\Response;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 微信服务号入口.
 * @class Wechat
 * @example 域名请修改为自己的地址，放到网页代码合适位置
 *
 * <meta name="referrer" content="always">
 * <script referrerpolicy="unsafe-url" src="https://your.domain.com/plugin-account/api.wechat/oauth?mode=1"></script>
 *
 * 授权模式支持两种模块，参数 mode=0 时为静默授权，mode=1 时为完整授权
 * 注意：回跳地址默认从 Header 中的 http_referer 获取，也可以传 source 参数
 */
class Wechat extends Controller
{
    /**
     * 通道认证类型.
     * @var string
     */
    private const type = Account::WECHAT;

    /**
     * 接口原地址
     * @var string
     */
    private $source;

    /**
     * 微信调度器.
     * @var WechatService
     */
    private $wechat;

    /**
     * 生成微信网页签名.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public function jssdk()
    {
        $this->success('获取网页签名！', $this->wechat->getWebJssdkSign($this->source));
    }

    /**
     * 微信网页授权脚本.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     * @remark 基于 sessionStorage 标识的登录机制
     */
    public function oauth(): Response
    {
        $script = [];
        $result = $this->wechat->getWebOauthInfo($this->source, intval(input('mode', 0)), false);
        if (empty($result['openid'])) {
            $script[] = 'alert("WeChat Oauth failed.")';
        } else {
            $fansinfo = $result['fansinfo'] ?? [];
            if (empty($fansinfo['is_snapshotuser'])) {
                // 筛选保存数据
                $data = ['appid' => WechatService::getAppid(), 'openid' => $result['openid'], 'extra' => $fansinfo];
                if (isset($fansinfo['unionid'])) {
                    $data['unionid'] = $fansinfo['unionid'];
                }
                if (isset($fansinfo['nickname'])) {
                    $data['nickname'] = $fansinfo['nickname'];
                }
                if (isset($fansinfo['headimgurl'])) {
                    $data['headimg'] = $fansinfo['headimgurl'];
                }
                $result['userinfo'] = Account::mk(static::type)->set($data, true);
                // 返回数据给前端
                $script[] = "window.WeChatOpenid='{$result['openid']}'";
                $script[] = 'window.WeChatFansInfo=' . json_encode($result['fansinfo'], 64 | 128 | 256);
                $script[] = 'window.WeChatUserInfo=' . json_encode($result['userinfo'], 64 | 128 | 256);
                $script[] = "sessionStorage.setItem('wechat.token','{$result['userinfo']['token']}')";
            } else {
                $script[] = 'alert("不支持虚拟用户登录！\n请 10 秒后刷新页面选择授权！")';
                $script[] = 'location.reload()';
            }
        }
        $script[] = '';
        return Response::create(join(";\n", $script))->contentType('application/javascript');
    }

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        if (Account::field(static::type)) {
            $this->wechat = WechatService::instance();
            $this->source = input('source') ?: $this->request->server('http_referer', $this->request->url(true));
        } else {
            $this->error('接口未开通');
        }
    }
}
