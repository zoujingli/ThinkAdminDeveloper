<?php

declare(strict_types=1);

namespace plugin\system\service;

use plugin\system\model\SystemFile;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\admin\runtime\SystemContext;
use think\admin\Service;
use think\admin\Storage;

/**
 * 系统文件服务。
 * @class FileService
 */
class FileService extends Service
{
    /**
     * 获取存储驱动类型映射。
     * @return array<string, string>
     */
    public static function getTypes(): array
    {
        return Storage::types();
    }

    /**
     * 构建文件列表上下文。
     * @return array<string, mixed>
     */
    public static function buildIndexContext(): array
    {
        return [
            'title' => strval(lang('系统文件管理')),
            'requestBaseUrl' => request()->baseUrl(),
            'extensions' => SystemFile::distinctExtensions(),
            'types' => self::getTypes(),
        ];
    }

    /**
     * 应用文件列表查询。
     * @param array<string, mixed> $context
     */
    public static function applyIndexQuery(QueryHelper $query, array $context = []): void
    {
        $query->like('name,hash')->equal('type')->dateBetween('create_time');
        $query->where([
            'is_safe' => 0,
            'status' => 2,
        ]);
        $extension = strtolower(trim(strval(request()->get('extension', request()->get('xext', '')))));
        if ($extension !== '') {
            $query->where(['extension' => $extension]);
        }

        $where = self::buildManageWhere();
        if ($where !== []) {
            $query->where($where);
        }
    }

    /**
     * 构建文件编辑上下文。
     * @return array<string, mixed>
     */
    public static function buildEditContext(): array
    {
        $id = intval(request()->param('id', 0));

        return [
            'id' => $id,
            'title' => strval(lang('编辑文件信息')),
            'actionUrl' => url('edit', array_filter(['id' => $id ?: null]))->build(),
            'types' => Storage::types(),
        ];
    }

    /**
     * 加载文件编辑表单数据。
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function loadEditFormData(array $context): array
    {
        $id = intval($context['id'] ?? 0);
        $where = self::buildManageWhere();
        if ($id < 1) {
            throw new Exception(lang('文件记录不存在！'));
        }

        $file = SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty();
        if ($file->isEmpty()) {
            throw new Exception(lang('文件记录不存在！'));
        }

        $types = is_array($context['types'] ?? null) ? $context['types'] : self::getTypes();
        $data = SystemFile::normalizeRow($file->toArray());
        $data['size_display'] = format_bytes(intval($data['size'] ?? 0));
        $data['type_display'] = $types[$data['type'] ?? ''] ?? strval($data['type'] ?? '');

        return $data;
    }

    /**
     * 保存文件编辑表单数据。
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @throws Exception
     */
    public static function saveEditFormData(array $data, array $context): void
    {
        $id = intval(request()->post('id', $context['id'] ?? 0));
        $where = self::buildManageWhere();
        if ($id < 1) {
            throw new Exception(lang('文件记录不存在！'));
        }

        $file = SystemFile::mk()->where(['id' => $id])->where($where)->findOrEmpty();
        if ($file->isEmpty()) {
            throw new Exception(lang('文件记录不存在！'));
        }

        $name = trim(strval($data['name'] ?? ''));
        if ($name === '') {
            throw new Exception(lang('文件名称不能为空！'));
        }

        if ($file->save(['name' => $name]) === false) {
            throw new Exception(lang('文件记录更新失败！'));
        }
    }

    /**
     * 清理当前管理员的重复文件。
     */
    public static function clearDistinctFiles(): void
    {
        $map = ['is_safe' => 0];
        $where = self::buildManageWhere();
        if ($where !== []) {
            $map = array_merge($map, $where);
        }

        $keepSubQuery = SystemFile::mk()
            ->fieldRaw('MAX(id) AS id')
            ->where($map)
            ->group('type, storage_key')
            ->buildSql();

        SystemFile::mk()
            ->where($map)
            ->whereNotExists(function ($query) use ($keepSubQuery) {
                $query->table("({$keepSubQuery})")->alias('f2')->whereRaw('f2.id = system_file.id');
            })
            ->delete();
    }

    /**
     * 授权文件列表查看。
     * @throws Exception
     */
    public static function authorizeView(): void
    {
        if (!self::canView()) {
            throw new Exception(lang('没有权限访问文件管理！'));
        }
    }

    /**
     * 授权文件管理。
     * @throws Exception
     */
    public static function authorizeManage(): void
    {
        if (!self::canManage()) {
            throw new Exception(lang('没有权限访问文件管理！'));
        }
    }

    /**
     * 是否允许查看系统文件。
     */
    public static function canView(): bool
    {
        $context = SystemContext::instance();
        return $context->isSuper() || $context->check('system/file/index') || self::canManage();
    }

    /**
     * 是否允许管理系统文件。
     */
    public static function canManage(): bool
    {
        $context = SystemContext::instance();
        return $context->isSuper()
            || $context->check('system/file/edit')
            || $context->check('system/file/remove')
            || $context->check('system/file/distinct');
    }

    /**
     * 生成当前管理员可操作的数据范围。
     * @return array<string, int>
     */
    public static function buildManageWhere(): array
    {
        return SystemContext::instance()->isSuper() ? [] : ['system_user_id' => SystemContext::instance()->getUserId()];
    }
}
