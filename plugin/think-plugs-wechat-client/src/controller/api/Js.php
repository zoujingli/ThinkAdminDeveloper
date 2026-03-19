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

namespace plugin\wechat\client\controller\api;

use plugin\wechat\client\service\WechatService;
use think\admin\Controller;
use think\admin\Exception;
use think\Response;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 前端JS获取控制器.
 * @class Js
 */
class Js extends Controller
{
    /** @var string */
    protected $params;

    /** @var string */
    protected $openid;

    /** @var string */
    protected $fansinfo;

    /**
     * 生成网页授权的JS内容.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public function index(): Response
    {
        $mode = $this->request->get('mode', 1);
        $source = $this->request->server('http_referer') ?: $this->request->url(true);
        $userinfo = WechatService::getWebOauthInfo($source, $mode, false);
        if (empty($userinfo['openid'])) {
            $content = 'alert("Wechat webOauth failed.")';
        } else {
            $this->openid = $userinfo['openid'];
            $this->params = json_encode(WechatService::getWebJssdkSign($source));
            $this->fansinfo = json_encode($userinfo['fansinfo'] ?? [], JSON_UNESCAPED_UNICODE);
            // 生成数据授权令牌
            $this->token = uniqid('oauth') . rand(10000, 99999);
            $this->app->cache->set($this->openid, $this->token, 3600);
            // 生成前端JS变量代码
            $content = $this->_buildContent();
        }
        return Response::create($content)->contentType('application/x-javascript');
    }

    /**
     * 给指定地址创建签名参数.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public function sdk()
    {
        $data = $this->_vali(['url.require' => '签名地址不能为空！']);
        $this->success('获取签名参数', WechatService::getWebJssdkSign($data['url']));
    }

    /**
     * 生成授权内容.
     */
    private function _buildContent(): string
    {
        return <<<EOF
if(typeof wx === 'object'){
    wx.token="{$this->token}";
    wx.openid="{$this->openid}";
    wx.fansinfo={$this->fansinfo};
    wx.config({$this->params});
    wx.ready(function(){
        wx.hideOptionMenu();
        wx.hideAllNonBaseMenuItem();
    });
}
EOF;
    }
}
