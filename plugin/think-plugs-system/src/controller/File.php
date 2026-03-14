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

namespace plugin\system\controller;

use plugin\system\service\SystemAuthService;
use plugin\storage\model\SystemFile;
use think\admin\Controller;
use think\admin\helper\QueryHelper;
use think\admin\service\Storage;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统文件管理.
 * @class File
 */
class File extends Controller
{
    /**
     * 存储类型.
     * @var array
     */
    protected $types;

    /**
     * 系统文件管理.
     * @auth true
     * @menu true
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function index()
    {
        SystemFile::mQuery()->layTable(function () {
            $this->title = '系统文件管理';
            $this->xexts = SystemFile::mk()->distinct()->column('xext');
        }, static function (QueryHelper $query) {
            $query->like('name,hash,xext')->equal('type')->dateBetween('create_time');
            $query->where(['issafe' => 0, 'status' => 2, 'uuid' => SystemAuthService::getUserId()]);
        });
    }

    /**
     * 编辑系统文件.
     * @auth true
     */
    public function edit()
    {
        $where = [];
        if (!SystemAuthService::isSuper()) {
            $where = ['uuid' => SystemAuthService::getUserId()];
        }
        $id = intval($this->request->param('id', 0));
        if ($id < 1 || SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty()->isEmpty()) {
            $this->error('文件记录不存在！');
        }
        SystemFile::mForm('form', '', $where);
    }

    /**
     * 删除系统文件.
     * @auth true
     */
    public function remove()
    {
        if (!SystemAuthService::isSuper()) {
            $where = ['uuid' => SystemAuthService::getUserId()];
        }
        SystemFile::mDelete('', $where ?? []);
    }

    /**
     * 清理重复文件.
     * @auth true
     * @throws DbException
     */
    public function distinct()
    {
        $map = ['issafe' => 0, 'uuid' => SystemAuthService::getUserId()];
        // 使用派生表包装子查询，避免直接引用同一表
        $keepSubQuery = SystemFile::mk()->fieldRaw('MAX(id) AS id')->where($map)->group('type, xkey')->buildSql();
        // 使用 whereNotExists 配合派生表子查询删除，避免 1093 错误和 whereIn
        SystemFile::mk()->where($map)->whereNotExists(function ($query) use ($keepSubQuery) {
            $query->table("({$keepSubQuery})")->alias('f2')->whereRaw('f2.id = system_file.id');
        })->delete();
        $this->success('清理重复文件成功！');
    }

    /**
     * 控制器初始化.
     */
    protected function initialize()
    {
        $this->types = Storage::types();
    }

    /**
     * 数据列表处理.
     */
    protected function _page_filter(array &$data)
    {
        foreach ($data as &$vo) {
            $vo['ctype'] = $this->types[$vo['type']] ?? $vo['type'];
        }
    }
}
