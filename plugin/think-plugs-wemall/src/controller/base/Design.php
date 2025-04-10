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

namespace plugin\wemall\controller\base;

use plugin\wemall\model\PluginWemallGoodsMark;
use think\admin\Controller;

/**
 * 页面设计器
 * @class Design
 * @package plugin\wemall\controller\base
 */
class Design extends Controller
{
    /**
     * 前端页面设计
     * @auth true
     * @menu true
     * @return void
     * @throws \think\admin\Exception
     */
    public function index()
    {
        $this->title = '店铺页面装修 ( 注意：后端页面显示与前端展示可能有些误差，请以前端实际显示为准！ )';
        $this->data = sysdata('plugin.wemall.design');
        $this->marks = PluginWemallGoodsMark::items();
        $this->fetch();
    }

    /**
     * 保存页面布局
     * @auth true
     * @return void
     * @throws \think\admin\Exception
     */
    public function save()
    {
        $input = $this->_vali([
            'pages.require'  => '页面配置不能为空！',
            'navbar.require' => '菜单导航配置不能为空！'
        ]);
        sysdata('plugin.wemall.design', [
            'pages'  => json_decode($input['pages'], true),
            'navbar' => json_decode($input['navbar'], true)
        ]);
        $this->success('保存成功！');
    }

    /**
     * 连接选择器
     * @login true
     * @return void
     */
    public function link()
    {
        $this->types = [
            ['name' => '商品分类', 'link' => sysuri('plugin-wemall/shop.goods.cate/select')],
            ['name' => '商品标签', 'link' => sysuri('plugin-wemall/shop.goods.mark/select')],
            ['name' => '商品详情', 'link' => sysuri('plugin-wemall/shop.goods/select')],
            ['name' => '其他链接', 'link' => sysuri('plugin-wemall/base.design/other')],
        ];
        $this->fetch();
    }

    /**
     * 显示其他连接
     * @login true
     * @return void
     */
    public function other()
    {
        $this->fetch('link_other', [
            'list' => [
                ['name' => '商城首页', 'type' => 'tabs', 'link' => '/pages/home/index'],
                ['name' => '商品中心', 'type' => 'tabs', 'link' => '/pages/goods/index'],
                ['name' => '会员中心', 'type' => 'tabs', 'link' => '/pages/center/index'],
                ['name' => '领取优惠券', 'type' => 'page', 'link' => '/pages/goods/coupon'],
            ]
        ]);
    }
}