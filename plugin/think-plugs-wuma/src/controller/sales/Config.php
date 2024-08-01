<?php

// +----------------------------------------------------------------------
// | Wuma Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 收费插件 ( https://thinkadmin.top/fee-introduce.html )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wuma
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wuma
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wuma\controller\sales;

use think\admin\Controller;

/**
 * 平台参数配置
 * @class Config
 * @package plugin\wuma\controller\sales
 */
class Config extends Controller
{
    /**
     * 存储名称
     * @var string
     */
    protected $agxkey = "plugin.wuma.agent";

    /**
     * 初始化控制器
     * @return void
     * @throws \think\admin\Exception
     */
    protected function initialize()
    {
        parent::initialize();
        $this->kfuser = sysdata('kfuser');
    }

    /**
     * 平台参数配置
     * @menu true
     * @auth true
     * @throws \think\admin\Exception
     */
    public function index()
    {
        $this->title = '平台参数配置';
        $this->agxdata = sysdata($this->agxkey);
        $this->fetch();
    }

    /**
     * 代理参数配置
     * @auth true
     * @throws \think\admin\Exception
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
}