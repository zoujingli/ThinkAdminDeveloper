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

use plugin\system\builder\ConfigBuilder;
use plugin\system\service\ConfigService;
use plugin\system\storage\StorageConfig;
use think\admin\Controller;
use think\admin\Exception;

/**
 * 系统参数配置.
 * @class Config
 */
class Config extends Controller
{
    /**
     * 系统参数配置.
     */
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
        $context = ConfigService::buildIndexContext(static::themeCatalog);
        $this->respondWithPageBuilder(ConfigBuilder::buildIndexPage($context), $context);
    }

    /**
     * 修改系统参数.
     * @auth true
     * @menu true
     */
    public function system()
    {
        if ($this->request->isGet()) {
            $context = ConfigService::buildSystemContext(static::themeCatalog);
            $this->respondWithFormBuilder(
                ConfigBuilder::buildSystemForm($context),
                $context,
                ConfigService::buildSystemFormData(static::themeCatalog)
            );
        } else {
            try {
                ConfigService::saveSystemConfig($this->request->post(), static::themeCatalog);
                $this->success(lang('系统参数保存成功。'), system_uri('system/config/index'));
            } catch (Exception $exception) {
                $this->error($exception->getMessage());
            }
        }
    }

    /**
     * 存储中心配置.
     * @auth true
     * @menu true
     */
    public function storage()
    {
        StorageConfig::initialize();

        if ($this->request->isGet()) {
            $type = strtolower(trim(strval($this->request->get('type', ''))));
            if ($type == '') {
                $this->authorizeStorageView();
                $context = ConfigService::buildStorageIndexContext();
                $this->respondWithPageBuilder(ConfigBuilder::buildStorageIndexPage($context), $context);
            } else {
                $this->authorizeStorageManage();
                $context = ConfigService::buildStorageFormContext($type);
                $this->respondWithFormBuilder(
                    ConfigBuilder::buildStorageForm($context),
                    $context,
                    ConfigService::buildStorageFormData()
                );
            }
        } else {
            $this->authorizeStorageManage();
            $storage = ConfigService::normalizeStorageConfig($this->request->post());
            if (!empty($storage['allowed_extensions'])) {
                $deny = ['sh', 'asp', 'bat', 'cmd', 'exe', 'php'];
                if (count(array_intersect($deny, str2arr($storage['allowed_extensions']))) > 0) {
                    $this->error(lang('禁止上传可执行文件类型。'));
                }
            }
            StorageConfig::save($storage);
            sysoplog('存储参数配置', '更新存储配置');
            $this->success(lang('存储配置保存成功。'), system_uri('system/config/storage'));
        }
    }

    /**
     * 授权存储配置查看.
     */
    private function authorizeStorageView(): void
    {
        if (ConfigService::canViewStorage()) {
            return;
        }
        $this->error(lang('抱歉，没有访问该操作的权限！'));
    }

    /**
     * 授权存储配置管理.
     */
    private function authorizeStorageManage(): void
    {
        if (ConfigService::canManageStorage()) {
            return;
        }
        $this->error(lang('抱歉，没有访问该操作的权限！'));
    }
}
