<?php

// +----------------------------------------------------------------------
// | Wechat Service Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 Anyon <zoujingli@qq.com>
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wechat-service
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wechat-service
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wechat\service\controller;

use think\admin\Controller;

/**
 * 开放平台参数配置
 * Class Config
 * @package plugin\wechat\service\controller
 */
class Config extends Controller
{
    /**
     * 开放平台配置
     * @auth true
     * @menu true
     */
    public function index()
    {
        $this->title = '开放平台配置';
        $this->geoip = $this->app->cache->get('mygeoip', '');
        if (empty($this->geoip)) {
            $this->geoip = gethostbyname($this->request->host());
            $this->app->cache->set('mygeoip', $this->geoip, 360);
        }
        $this->fetch();
    }

    /**
     * 修改开放平台参数
     * @auth true
     * @throws \think\admin\Exception
     */
    public function edit()
    {
        $this->_applyFormToken();
        if ($this->request->isGet()) {
            $this->fetch('form');
        } else {
            $post = $this->request->post();
            foreach ($post as $k => $v) sysconf($k, $v);
            $this->success('参数修改成功！');
        }
    }
}