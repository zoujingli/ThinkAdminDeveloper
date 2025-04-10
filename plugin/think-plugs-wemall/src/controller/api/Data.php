<?php

// +----------------------------------------------------------------------
// | WeMall Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// | 会员免费 ( https://thinkadmin.top/vip-introduce )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-wemall
// | github 代码仓库：https://github.com/zoujingli/think-plugs-wemall
// +----------------------------------------------------------------------

declare (strict_types=1);

namespace plugin\wemall\controller\api;

use plugin\account\model\PluginAccountUser;
use plugin\wemall\service\ConfigService;
use think\admin\Controller;
use think\admin\model\SystemBase;

/**
 * 基础数据接口
 * @class Data
 * @package plugin\wemall\controller\api
 */
class Data extends Controller
{
    /**
     * 获取指定数据
     * @throws \think\admin\Exception
     */
    public function get()
    {
        $data = $this->_vali(['name.require' => '数据名称不能为空！']);
        $extra = ['about', 'slider', 'agreement', 'cropper'];
        if (in_array($data['name'], $extra) || isset(SystemBase::items('页面内容')[$data['name']])) {
            $this->success('获取数据对象', sysdata($data['name']));
        } else {
            $this->error('获取数据失败', []);
        }
    }

    /**
     * 识别推荐人信息
     * @return void
     */
    public function spread()
    {
        $data = $this->_vali(['from.require' => '推荐人不能为空！']);
        $where = ['id' => $data['from'], 'deleted' => 0];
        $user = PluginAccountUser::mk()->where($where)->findOrEmpty();
        if ($user->isEmpty()) $this->error('无效推荐人！');
        $this->success('查询成功!', [
            'unid'  => $user->getAttr('id'),
            'name'  => $user->getAttr('nickname'),
            'phone' => preg_replace('/^(\d{3}).*?(\d{4})$/', '$1****$2', $user->getAttr('phone') ?: ''),
        ]);
    }

    /**
     * 获取页面布局
     * @return void
     * @throws \think\admin\Exception
     */
    public function layout()
    {
        $config = ConfigService::get();
        $this->success('获取应用配置', [
            'config' => [
                'baseName'       => $config['base_name'] ?? '',
                'baseIcon'       => $config['base_icon'] ?? '',
                'copyRight'      => $config['base_copy'] ?? '',
                'userBalance'    => $config['enable_balance'] ?? false,
                'userIntergral'  => $config['enable_integral'] ?? false,
                'enableWapsite'  => $config['enable_wapsite'] ?? false,
                'schemeAndroid'  => $config['scheme_android'] ?? '',
                'schemeRedirect' => $config['scheme_redirect'] ?? ''
            ],
            'layout' => (object)sysdata('plugin.wemall.design'),
        ]);
    }

    /**
     * 图片内容数据
     * @throws \think\admin\Exception
     */
    public function slider()
    {
        $this->keys = input('keys', '首页图片');
        if (isset(SystemBase::items('图片内容')[$this->keys])) {
            $this->success('获取图片内容', sysdata($this->keys));
        } else {
            $this->error('获取图片失败', []);
        }
    }

    /**
     * 获取协议内容
     * @return void
     * @throws \think\admin\Exception
     */
    public function agreement()
    {
        $this->success('获取协议成功！', [
            'privacy'   => ConfigService::getPage('user_privacy'),
            'agreement' => ConfigService::getPage('user_agreement'),
        ]);
    }
}