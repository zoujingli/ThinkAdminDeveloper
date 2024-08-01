<?php

// +----------------------------------------------------------------------
// | Account Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2022~2024 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-account
// | github 代码仓库：https://github.com/zoujingli/think-plugs-account
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\account\controller;

use plugin\account\model\PluginAccountMsms;
use plugin\account\service\Message as MessageService;
use plugin\account\service\message\Alisms;
use think\admin\Controller;
use think\admin\helper\QueryHelper;

/**
 * 手机短信管理
 * @class Message
 * @package plugin\account\controller
 */
class Message extends Controller
{

    /**
     * 缓存配置名称
     * @var string
     */
    protected $smskey;

    /**
     * 初始化控制器
     * @return void
     */
    protected function initialize()
    {
        parent::initialize();
        $this->smskey = 'plugin.account.smscfg';
    }

    /**
     * 手机短信管理
     * @auth true
     * @menu true
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        PluginAccountMsms::mQuery()->layTable(function () {
            $this->title = '手机短信管理';
            $this->scenes = MessageService::$scenes;
        }, static function (QueryHelper $query) {
            $query->equal('status')->like('smsid,scene,phone')->dateBetween('create_time');
        });
    }

    /**
     * 修改短信配置
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     */
    public function config()
    {
        if ($this->request->isGet()) {
            $this->vo = sysdata($this->smskey);
            $this->scenes = MessageService::$scenes;
            $this->regions = Alisms::regions();
            $this->fetch();
        } else {
            sysdata($this->smskey, $this->request->post());
            $this->success('修改配置成功！');
        }
    }
}