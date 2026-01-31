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

namespace plugin\wechat\service\controller;

use think\admin\Controller;
use think\admin\Exception;

/**
 * 开放平台参数配置
 * Class Config.
 */
class Config extends Controller
{
    /**
     * 开放平台配置.
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
     * 修改开放平台参数.
     * @auth true
     * @throws Exception
     */
    public function edit()
    {
        $this->_applyFormToken();
        if ($this->request->isGet()) {
            $this->fetch('form');
        } else {
            $post = $this->request->post();
            foreach ($post as $k => $v) {
                sysconf($k, $v);
            }
            $this->success('参数修改成功！');
        }
    }
}
