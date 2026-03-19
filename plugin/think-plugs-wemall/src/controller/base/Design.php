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

namespace plugin\wemall\controller\base;

use plugin\wemall\model\PluginWemallGoodsMark;
use think\admin\Controller;
use think\admin\Exception;

/**
 * 页面设计器.
 * @class Design
 */
class Design extends Controller
{
    /**
     * 前端页面设计
     * @auth true
     * @menu true
     * @throws Exception
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
     * @throws Exception
     */
    public function save()
    {
        $input = $this->_vali([
            'pages.require' => '页面配置不能为空！',
            'navbar.require' => '菜单导航配置不能为空！',
        ]);
        sysdata('plugin.wemall.design', [
            'pages' => json_decode($input['pages'], true),
            'navbar' => json_decode($input['navbar'], true),
        ]);
        $this->success('保存成功！');
    }

    /**
     * 连接选择器.
     * @login true
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
     * 显示其他连接.
     * @login true
     */
    public function other()
    {
        $this->fetch('link_other', [
            'list' => [
                ['name' => '商城首页', 'type' => 'tabs', 'link' => '/pages/home/index'],
                ['name' => '商品中心', 'type' => 'tabs', 'link' => '/pages/goods/index'],
                ['name' => '会员中心', 'type' => 'tabs', 'link' => '/pages/center/index'],
                ['name' => '领取优惠券', 'type' => 'page', 'link' => '/pages/goods/coupon'],
            ],
        ]);
    }
}
