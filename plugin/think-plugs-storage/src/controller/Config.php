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

namespace plugin\storage\controller;

use plugin\storage\service\StorageConfig;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\runtime\SystemContext;
use think\admin\service\Storage;

/**
 * 存储参数配置.
 * @class Config
 */
class Config extends Controller
{
    /**
     * 存储中心首页.
     * @auth true
     * @menu true
     * @login true
     */
    public function index()
    {
        $this->authorizeView();
        StorageConfig::initialize();
        $this->title = '存储配置中心';
        $this->files = Storage::types();
        $this->driver = strtolower((string)StorageConfig::global('driver', 'local'));
        $this->driverName = $this->files[$this->driver] ?? $this->driver;
        $this->canEdit = $this->canManage();
        return $this->fetch('config/index');
    }

    /**
     * 修改文件存储.
     * @auth true
     * @login true
     * @throws Exception
     */
    public function storage()
    {
        $this->authorizeManage();
        $this->_applyFormToken();
        if ($this->request->isGet()) {
            StorageConfig::initialize();
            $this->type = input('type', array_key_first(Storage::types()) ?: 'local');
            $this->points = Storage::regions($this->type);
            return $this->fetch(Storage::template($this->type));
        }
        $post = $this->request->post();
        if (!empty($post['storage']['allowed_exts'])) {
            $deny = ['sh', 'asp', 'bat', 'cmd', 'exe', 'php'];
            $exts = array_unique(str2arr(strtolower($post['storage']['allowed_exts'])));
            if (count(array_intersect($deny, $exts)) > 0) {
                $this->error('禁止上传可执行的文件！');
            }
            $post['storage']['allowed_exts'] = join(',', $exts);
        }
        foreach ($post as $name => $value) {
            sysconf($name, $value);
        }
        sysoplog('系统配置管理', '修改系统存储参数');
        $this->success('修改文件存储成功！');
    }

    private function authorizeView(): void
    {
        if ($this->canView()) {
            return;
        }
        $this->error('抱歉，没有访问该操作的权限！');
    }

    private function authorizeManage(): void
    {
        if ($this->canManage()) {
            return;
        }
        $this->error('抱歉，没有访问该操作的权限！');
    }

    private function canView(): bool
    {
        return SystemContext::isSuper()
            || SystemContext::check('storage/config/index')
            || SystemContext::check('storage/config/storage');
    }

    private function canManage(): bool
    {
        return SystemContext::isSuper()
            || SystemContext::check('storage/config/storage')
            || SystemContext::check('storage/config/index');
    }
}
