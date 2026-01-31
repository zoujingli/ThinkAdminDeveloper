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

namespace plugin\wuma\controller\sales;

use think\admin\Controller;
use think\admin\Exception;

/**
 * 平台参数配置.
 * @class Config
 */
class Config extends Controller
{
    /**
     * 存储名称.
     * @var string
     */
    protected $agxkey = 'plugin.wuma.agent';

    /**
     * 平台参数配置.
     * @menu true
     * @auth true
     * @throws Exception
     */
    public function index()
    {
        $this->title = '平台参数配置';
        $this->agxdata = sysdata($this->agxkey);
        $this->fetch();
    }

    /**
     * 代理参数配置.
     * @auth true
     * @throws Exception
     */
    public function agxcfg()
    {
        if ($this->request->isGet()) {
            $this->vo = sysdata($this->agxkey);
            $this->fetch('agent');
        } else {
            sysdata($this->agxkey, $this->request->post());
            $this->success('修改配置成功！');
        }
    }

    /**
     * 初始化控制器.
     * @throws Exception
     */
    protected function initialize()
    {
        parent::initialize();
        $this->kfuser = sysdata('kfuser');
    }
}
