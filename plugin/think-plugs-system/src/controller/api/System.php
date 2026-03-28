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

namespace plugin\system\controller\api;

use plugin\system\model\SystemData;
use plugin\system\service\AuthService;
use plugin\system\service\ConfigService;
use think\admin\Controller;
use think\admin\service\RuntimeService;
use think\exception\HttpResponseException;

/**
 * 系统运行管理.
 * @class System
 */
class System extends Controller
{
    /**
     * 网站压缩发布.
     * @login true
     */
    public function push()
    {
        if (AuthService::isSuper()) {
            try {
                RuntimeService::push() && sysoplog('系统运维管理', '刷新发布运行缓存');
                $this->success(lang('网站缓存加速成功！'), 'javascript:location.reload()');
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                trace_file($exception);
                $this->error($exception->getMessage());
            }
        } else {
            $this->error(lang('请使用超管账号操作！'));
        }
    }

    /**
     * 清理运行缓存.
     * @login true
     */
    public function clear()
    {
        if (AuthService::isSuper()) {
            try {
                RuntimeService::clear() && sysoplog('系统运维管理', '清理网站日志缓存');
                $this->success(lang('清空日志缓存成功！'), 'javascript:location.reload()');
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                trace_file($exception);
                $this->error($exception->getMessage());
            }
        } else {
            $this->error(lang('请使用超管账号操作！'));
        }
    }

    /**
     * 当前运行模式.
     * @login true
     */
    public function debug()
    {
        if (AuthService::isSuper()) {
            if (input('state')) {
                RuntimeService::set('product');
                sysoplog('系统运维管理', '开发模式切换为生产模式');
                $this->success(lang('已切换为生产模式！'), 'javascript:location.reload()');
            } else {
                RuntimeService::set('debug');
                sysoplog('系统运维管理', '生产模式切换为开发模式');
                $this->success(lang('已切换为开发模式！'), 'javascript:location.reload()');
            }
        } else {
            $this->error(lang('请使用超管账号操作！'));
        }
    }

    /**
     * 修改富文本编辑器.
     * @throws \think\admin\Exception
     */
    public function editor()
    {
        if (AuthService::isSuper()) {
            $editor = ConfigService::setEditorDriver(strval(input('editor', 'auto')));
            sysoplog('系统运维管理', "切换编辑器为{$editor}");
            $this->success(lang('已切换后台编辑器！'), 'javascript:location.reload()');
        } else {
            $this->error(lang('请使用超管账号操作！'));
        }
    }

    /**
     * 清理系统配置.
     * @login true
     */
    public function config()
    {
        if (AuthService::isSuper()) {
            try {
                $newdata = [];
                foreach (SystemData::mk()->order('id asc')->cursor() as $item) {
                    $name = strval($item['name']);
                    $newdata[$name] = ['name' => $name, 'value' => $item->toString()];
                }
                $this->app->db->transaction(static function () use ($newdata) {
                    SystemData::mQuery()->empty()->insertAll(array_values($newdata));
                });
                $this->app->cache->delete('SystemData');
                sysvar('think.admin.data', []);
                sysoplog('系统运维管理', '清理系统配置参数');
                $this->success(lang('清理系统配置成功！'), 'javascript:location.reload()');
            } catch (HttpResponseException $exception) {
                throw $exception;
            } catch (\Exception $exception) {
                trace_file($exception);
                $this->error($exception->getMessage());
            }
        } else {
            $this->error(lang('请使用超管账号操作！'));
        }
    }
}
