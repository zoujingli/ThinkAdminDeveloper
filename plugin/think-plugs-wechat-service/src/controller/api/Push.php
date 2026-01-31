<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | Payment Plugin for ThinkAdmin
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

namespace plugin\wechat\service\controller\api;

use plugin\wechat\service\AuthService;
use plugin\wechat\service\handle\PublishHandle;
use plugin\wechat\service\handle\ReceiveHandle;
use plugin\wechat\service\model\WechatAuth;
use think\admin\Controller;
use think\admin\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\Response;
use WeChat\Exceptions\InvalidDecryptException;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;
use WeOpen\Service;

/**
 * 服务平台推送服务
 * Class Push.
 */
class Push extends Controller
{
    /**
     * 微信API推送事件处理.
     * @throws InvalidDecryptException
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function notify(string $appid = ''): string
    {
        $tests = [
            'wx570bc396a51b8ff8', 'wx9252c5e0bb1836fc', 'wx8e1097c5bc82cde9', 'wx14550af28c71a144', 'wxa35b9c23cfe664eb', // 公众号测试专用号
            'wxd101a85aa106f53e', 'wxc39235c15087f6f3', 'wx7720d01d4b2a4500', 'wx05d483572dcd5d8b', 'wx5910277cae6fd970', // 小程序测试专用号
        ];
        $appid = empty($appid) ? input('appid') : $appid;
        if (in_array($appid, $tests)) {
            # 全网发布接口测试
            return PublishHandle::instance()->handler($appid);
        }
        # 常归接口正常服务
        return ReceiveHandle::instance()->handler($appid);
    }

    /**
     * 一、处理服务推送Ticket
     * 二、处理取消公众号授权.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function ticket(): string
    {
        try {
            $server = AuthService::WeOpenService();
            if (!($data = $server->getComonentTicket())) {
                return 'Ticket event handling failed.';
            }
            if (!empty($data['ComponentVerifyTicket'])) {
                sysconf('service.ticket_push_date', date('Y-m-d H:i:s'));
            }
        } catch (\Exception $exception) {
            $message = "Ticket event handling failed, {$exception->getMessage()}";
            $this->app->log->notice($message);
            return $message;
        }
        if (!empty($data['AuthorizerAppid']) && isset($data['InfoType'])) {
            # 授权成功通知
            if ($data['InfoType'] === 'authorized') {
                $map = ['authorizer_appid' => $data['AuthorizerAppid']];
                WechatAuth::mk()->where($map)->update(['deleted' => 0]);
            }
            # 取消授权通知
            if ($data['InfoType'] === 'unauthorized') {
                $map = ['authorizer_appid' => $data['AuthorizerAppid']];
                WechatAuth::mk()->where($map)->update(['deleted' => 1]);
            }
            # 授权更新通知
            if ($data['InfoType'] === 'updateauthorized') {
                $_GET['auth_code'] = $data['PreAuthCode'];
                $this->applyAuth($server);
            }
        }
        return 'success';
    }

    /**
     * 微信代网页授权.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public function oauth()
    {
        [$mode, $appid, $enurl, $sessid] = [
            $this->request->get('mode'), $this->request->get('state'),
            $this->request->get('enurl'), $this->request->get('sessid'),
        ];
        $result = AuthService::WeOpenService()->getOauthAccessToken($appid);
        if (empty($result['openid'])) {
            throw new Exception('网页授权失败, 无法进一步操作！');
        }
        $expire = empty($result['is_snapshotuser']) ? 3600 : 10;
        $this->app->cache->set("{$appid}_{$sessid}_token", $result, $expire);
        $this->app->cache->set("{$appid}_{$sessid}_openid", $result['openid'], $expire);
        if (!empty($mode)) {
            $fans = AuthService::WeChatOauth($appid)->getUserInfo($result['access_token'], $result['openid']);
            if (empty($fans)) {
                throw new Exception('网页授权信息获取失败, 无法进一步操作！');
            }
            $fans['is_snapshotuser'] = empty($result['is_snapshotuser']) ? 0 : 1;
            $this->app->cache->set("{$appid}_{$sessid}_fans", $fans, $expire);
        }
        $this->redirect(debase64url($enurl));
    }

    /**
     * 跳转到微信服务授权页面.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function auth(): Response
    {
        if (empty($source = input('source'))) {
            return response('请传入回跳 source 参数 ( 请使用 enbase64url 加密 )');
        }
        if (empty($resource = debase64url($source))) {
            return response('请传入回跳 source 参数 ( 请使用 enbase64url 加密 )');
        }
        # 预授权码不为空，则表示可以进行授权处理
        $service = AuthService::WeOpenService();
        if ($authcode = $this->request->get('auth_code')) {
            return $this->applyAuth($service, $resource, $authcode);
        }
        # 生成微信授权链接，使用刷新跳转到授权网页
        $redirect = sysuri('api.push/auth', [], false, true) . "?source={$source}";
        if ($redirect = $service->getAuthRedirect($redirect)) {
            # 生成微信授权链接成功
            return response("<script>location.href='{$redirect}'</script>", 200, ["Refresh:0;url={$redirect}"]);
        }
        # 生成微信授权链接失败
        return response('<h2>Failed to create authorization. Please return to try again.</h2>');
    }

    /**
     * 公众号授权绑定数据处理.
     * @param Service $service 服务对象
     * @param null|string $redirect 回跳地址
     * @param null|string $authcode 授权编号
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function applyAuth(Service $service, ?string $redirect = null, ?string $authcode = null): Response
    {
        // 授权code换取公众号信息
        $result = $service->getQueryAuthorizerInfo($authcode);
        if (empty($result['authorizer_appid'])) {
            return response('接收微信第三方平台授权失败! ');
        }

        // 通过接口查询公众号参数
        if (!($data = array_merge($result, $service->getAuthorizerInfo($result['authorizer_appid'])))) {
            return response('获取授权数据失败, 请稍候再试! ');
        }

        // 生成公众号授权参数
        $data = array_merge(AuthService::buildAuthData($data), [
            'deleted' => 0, 'expires_in' => time() + 7000, 'create_at' => date('y-m-d H:i:s'),
        ]);

        // 公众号授权数据更新
        $defa = WechatAuth::mk()->where(['authorizer_appid' => $result['authorizer_appid']])->find();
        $data['appkey'] = empty($defa['appkey']) ? md5(uniqid() . rand(1000, 9999)) : $defa['appkey'];
        $data['auth_time'] = empty($defa['auth_time']) ? time() : $defa['auth_time'];
        WechatAuth::mUpdate($data, 'authorizer_appid');

        // 授权成功后跳转地址处理
        if (empty($redirect)) {
            return response('未配置授权成功后的回跳地址！');
        }
        $split = is_numeric(stripos($redirect, '?')) ? '&' : '?';
        $realurl = preg_replace(['/appid=\w+/i', '/appkey=\w+/i', '/(\?&)$/i'], ['', '', ''], $redirect);
        return redirect("{$realurl}{$split}appid={$data['authorizer_appid']}&appkey={$data['appkey']}");
    }
}
