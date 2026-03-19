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

use plugin\wechat\client\service\LoginService;
use think\admin\Controller;
use think\admin\Exception;
use WeChat\Exceptions\InvalidResponseException;
use WeChat\Exceptions\LocalCacheException;

/**
 * 微信扫码登录.
 * @class Login
 */
class Login extends Controller
{
    /**
     * 显示二维码
     */
    public function qrc()
    {
        $mode = intval(input('mode', '0'));
        $data = LoginService::qrcode(LoginService::gcode(), $mode);
        $this->success('登录二维码', $data);
    }

    /**
     * 微信授权处理.
     * @throws InvalidResponseException
     * @throws LocalCacheException
     * @throws Exception
     */
    public function oauth()
    {
        $data = $this->_vali(['auth.default' => '', 'mode.default' => '0']);
        if (LoginService::oauth($data['auth'], intval($data['mode']))) {
            $this->fetch('success', ['message' => '授权成功']);
        } else {
            $this->fetch('failed', ['message' => '授权失败']);
        }
    }

    /**
     * 获取授权信息
     * 用定时器请求这个接口.
     */
    public function query()
    {
        $data = $this->_vali(['code.require' => '编号不能为空！']);
        if ($fans = LoginService::query($data['code'])) {
            $this->success('获取授权信息', $fans);
        } else {
            $this->error('未获取到授权！');
        }
    }
}
