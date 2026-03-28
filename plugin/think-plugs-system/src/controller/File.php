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

use plugin\system\builder\FileBuilder;
use plugin\system\service\FileService;
use plugin\system\model\SystemFile;
use think\admin\Controller;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;

/**
 * 系统文件管理.
 */
class File extends Controller
{
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
        try {
            FileService::authorizeView();
            $context = FileService::buildIndexContext();
            SystemFile::mQuery()->layTable(function () use ($context) {
                $this->respondWithPageBuilder(FileBuilder::buildIndexPage($context), $context);
            }, static function (QueryHelper $query) use ($context) {
                FileService::applyIndexQuery($query, $context);
            });
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 编辑文件信息.
     * @auth true
     */
    public function edit()
    {
        try {
            FileService::authorizeManage();
            $context = FileService::buildEditContext();
            $builder = FileBuilder::buildEditForm($context);
            if ($this->request->isGet()) {
                $this->respondWithFormBuilder($builder, $context, FileService::loadEditFormData($context));
            }

            FileService::saveEditFormData($builder->validate(), $context);
            $this->success(lang('数据保存成功！'));
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 移除文件记录.
     * @auth true
     */
    public function remove()
    {
        try {
            FileService::authorizeManage();
            SystemFile::mDelete('', FileService::buildManageWhere());
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 文件记录去重.
     * @auth true
     * @throws DbException
     */
    public function distinct()
    {
        try {
            FileService::authorizeManage();
            FileService::clearDistinctFiles();
            $this->success(lang('文件去重清理成功！'));
        } catch (Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

    /**
     * 数据列表处理.
     */
    protected function _page_filter(array &$data)
    {
        $types = FileService::getTypes();
        foreach ($data as &$vo) {
            $vo = SystemFile::normalizeRow($vo);
            $vo['ctype'] = $types[$vo['type']] ?? $vo['type'];
        }
    }
}
