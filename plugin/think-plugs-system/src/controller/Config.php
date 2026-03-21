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

namespace plugin\system\controller;

use plugin\storage\service\StorageConfig;
use plugin\system\service\SystemAuthService;
use plugin\system\service\SystemService;
use plugin\system\service\UserService;
use plugin\worker\service\ProcessService;
use think\admin\Controller;
use think\admin\service\AppService;
use think\admin\Storage;

/**
 * 系统参数配置.
 * @class Config
 */
class Config extends Controller
{
    public const themes = [
        'default' => '默认青 0',
        'white' => '极简白 0',
        'red-1' => '绛霞红 1',
        'blue-1' => '深空蓝 1',
        'green-1' => '翡翠绿 1',
        'black-1' => '石墨黑 1',
        'navy-1' => '夜幕蓝 1',
        'amber-1' => '琥珀金 1',
        'violet-1' => '流光紫 1',
        'rose-1' => '玫影粉 1',
        'lime-1' => '青柠绿 1',
        'indigo-1' => '靛夜蓝 1',
        'glacier-1' => '冰川蓝 1',
        'red-2' => '绛霞红 2',
        'blue-2' => '深空蓝 2',
        'green-2' => '翡翠绿 2',
        'black-2' => '石墨黑 2',
        'slate-2' => '雾岩灰 2',
        'ocean-2' => '深海青 2',
        'sunset-2' => '晚霞橙 2',
        'rose-2' => '玫影粉 2',
        'lime-2' => '青柠绿 2',
        'indigo-2' => '靛夜蓝 2',
        'glacier-2' => '冰川蓝 2',
    ];

    public const themeCatalog = [
        'default' => ['label' => '默认青 0', 'layout' => 'classic', 'layout_label' => '标准', 'primary' => '#009688', 'header' => '#FFFFFF', 'side' => '#20222A', 'surface' => '#FFFFFF', 'body' => '#F4F7FB'],
        'white' => ['label' => '极简白 0', 'layout' => 'classic', 'layout_label' => '标准', 'primary' => '#16A34A', 'header' => '#FFFFFF', 'side' => '#FFFFFF', 'surface' => '#FFFFFF', 'body' => '#F8FAFC'],
        'red-1' => ['label' => '绛霞红 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#AA3130', 'header' => '#AA3130', 'side' => '#AA3130', 'surface' => '#FFFDFD', 'body' => '#FFF5F5'],
        'blue-1' => ['label' => '深空蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#3963BC', 'header' => '#3963BC', 'side' => '#3963BC', 'surface' => '#FFFFFF', 'body' => '#F4F7FF'],
        'green-1' => ['label' => '翡翠绿 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#009688', 'header' => '#009688', 'side' => '#009688', 'surface' => '#FFFFFF', 'body' => '#F1FBFA'],
        'black-1' => ['label' => '石墨黑 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#393D49', 'header' => '#393D49', 'side' => '#393D49', 'surface' => '#FFFFFF', 'body' => '#F5F7FA'],
        'navy-1' => ['label' => '夜幕蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#1D4ED8', 'header' => '#1D4ED8', 'side' => '#1D4ED8', 'surface' => '#FFFFFF', 'body' => '#F3F6FF'],
        'amber-1' => ['label' => '琥珀金 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#B45309', 'header' => '#B45309', 'side' => '#B45309', 'surface' => '#FFFFFF', 'body' => '#FFF8F1'],
        'violet-1' => ['label' => '流光紫 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#7C3AED', 'header' => '#7C3AED', 'side' => '#7C3AED', 'surface' => '#FFFFFF', 'body' => '#F7F3FF'],
        'rose-1' => ['label' => '玫影粉 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#DB2777', 'header' => '#DB2777', 'side' => '#DB2777', 'surface' => '#FFFDFE', 'body' => '#FFF2F8'],
        'lime-1' => ['label' => '青柠绿 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#65A30D', 'header' => '#65A30D', 'side' => '#65A30D', 'surface' => '#FFFFFE', 'body' => '#F7FCEB'],
        'indigo-1' => ['label' => '靛夜蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#4338CA', 'header' => '#4338CA', 'side' => '#4338CA', 'surface' => '#FFFFFF', 'body' => '#F4F4FF'],
        'glacier-1' => ['label' => '冰川蓝 1', 'layout' => 'brand', 'layout_label' => '品牌侧栏', 'primary' => '#0284C7', 'header' => '#0284C7', 'side' => '#0284C7', 'surface' => '#FFFFFF', 'body' => '#F0F9FF'],
        'red-2' => ['label' => '绛霞红 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#AA3130', 'header' => '#AA3130', 'side' => '#AA3130', 'surface' => '#FFFFFF', 'body' => '#FFF5F5'],
        'blue-2' => ['label' => '深空蓝 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#3963BC', 'header' => '#3963BC', 'side' => '#3963BC', 'surface' => '#FFFFFF', 'body' => '#F4F7FF'],
        'green-2' => ['label' => '翡翠绿 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#009688', 'header' => '#009688', 'side' => '#009688', 'surface' => '#FFFFFF', 'body' => '#F1FBFA'],
        'black-2' => ['label' => '石墨黑 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#393D49', 'header' => '#393D49', 'side' => '#393D49', 'surface' => '#FFFFFF', 'body' => '#F5F7FA'],
        'slate-2' => ['label' => '雾岩灰 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#475569', 'header' => '#475569', 'side' => '#475569', 'surface' => '#FFFFFF', 'body' => '#F7F8FB'],
        'ocean-2' => ['label' => '深海青 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#0F766E', 'header' => '#0F766E', 'side' => '#0F766E', 'surface' => '#FFFFFF', 'body' => '#F0FBF9'],
        'sunset-2' => ['label' => '晚霞橙 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#C2410C', 'header' => '#C2410C', 'side' => '#C2410C', 'surface' => '#FFFFFF', 'body' => '#FFF7ED'],
        'rose-2' => ['label' => '玫影粉 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#DB2777', 'header' => '#DB2777', 'side' => '#DB2777', 'surface' => '#FFFDFE', 'body' => '#FFF2F8'],
        'lime-2' => ['label' => '青柠绿 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#65A30D', 'header' => '#65A30D', 'side' => '#65A30D', 'surface' => '#FFFFFE', 'body' => '#F7FCEB'],
        'indigo-2' => ['label' => '靛夜蓝 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#4338CA', 'header' => '#4338CA', 'side' => '#4338CA', 'surface' => '#FFFFFF', 'body' => '#F4F4FF'],
        'glacier-2' => ['label' => '冰川蓝 2', 'layout' => 'split', 'layout_label' => '双栏导航', 'primary' => '#0284C7', 'header' => '#0284C7', 'side' => '#0284C7', 'surface' => '#FFFFFF', 'body' => '#F0F9FF'],
    ];

    /**
     * 系统参数配置.
     * @auth true
     * @menu true
     */
    public function index()
    {
        StorageConfig::initialize();
        $this->title = '系统参数配置';
        $this->files = Storage::types();
        $this->storageDriver = strtolower((string)StorageConfig::global('driver', 'local'));
        $this->storageName = $this->files[$this->storageDriver] ?? $this->storageDriver;
        $this->storageEditable = SystemAuthService::isSuper()
            || SystemAuthService::check('storage/config/index')
            || SystemAuthService::check('storage/config/storage');
        $this->plugins = AppService::all(true);
        $this->issuper = SystemAuthService::isSuper();
        $this->systemid = ProcessService::getRunVar('uni');
        $this->framework = AppService::getPluginLibrarys('topthink/framework');
        $this->thinkadmin = AppService::getPluginLibrarys('zoujingli/think-library');
        if (SystemAuthService::isSuper() && UserService::verifyPassword('admin', strval(SystemAuthService::getUser('password', '')))) {
            $url = url('system/index/pass', ['id' => SystemAuthService::getUserId()]);
            $this->showErrorMessage = lang("超级管理员账号的密码未修改，建议立即<a data-modal='%s'>修改密码</a>！", [$url]);
        }
        uasort($this->plugins, static function ($a, $b) {
            if ($a['space'] === $b['space']) {
                return 0;
            }
            return $a['space'] > $b['space'] ? 1 : -1;
        });
        $this->fetch();
    }

    /**
     * 修改系统参数.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function system()
    {
        if ($this->request->isGet()) {
            $this->title = '修改系统参数';
            $this->themes = static::themeCatalog;
            $this->fetch();
        } else {
            $post = $this->request->post();
            unset($post['xpath']);
            // 修改网站 ICON 图标，替换 public/favicon.ico
            if (preg_match('#^https?://#', $post['site_icon'] ?? '')) {
                try {
                    SystemService::setFavicon($post['site_icon'] ?? '');
                } catch (\Exception $exception) {
                    trace_file($exception);
                }
            }
            // 数据数据到系统配置表
            foreach ($post as $k => $v) {
                sysconf($k, $v);
            }
            sysoplog('系统配置管理', '修改系统参数成功');
            $this->success('数据保存成功！', system_uri('system/config/index'));
        }
    }

    /**
     * 修改文件存储.
     * @auth true
     * @throws \think\admin\Exception
     */
    public function storage()
    {
        app(\plugin\storage\controller\Config::class)->storage();
    }
}
