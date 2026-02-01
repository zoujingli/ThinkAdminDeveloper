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

namespace plugin\account\controller;

use plugin\account\model\PluginAccountMsms;
use plugin\account\service\message\Alisms;
use plugin\account\service\Message as MessageService;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 手机短信管理.
 * @class Message
 */
class Message extends Controller
{
    /**
     * 缓存配置名称.
     * @var string
     */
    protected $smskey;

    /**
     * 手机短信管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
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
     * 修改短信配置.
     * @auth true
     * @throws Exception
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

    /**
     * 初始化控制器.
     */
    protected function initialize()
    {
        parent::initialize();
        $this->smskey = 'plugin.account.smscfg';
    }
}
