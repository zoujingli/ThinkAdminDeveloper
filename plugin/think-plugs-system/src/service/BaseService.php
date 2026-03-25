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

namespace plugin\system\service;

use plugin\system\model\SystemBase;
use think\admin\Exception;
use think\admin\helper\QueryHelper;
use think\admin\Service;

/**
 * 数据字典业务服务.
 * @class BaseService
 */
class BaseService extends Service
{
    /**
     * 构建数据字典列表上下文.
     * @return array<string, mixed>
     */
    public static function buildIndexContext(): array
    {
        $types = SystemBase::types();
        $mode = self::normalizeMode(strval(request()->get('type', 'index')));
        $baseType = self::resolveIndexType($types, strval(request()->get('base_type', '')));
        $pluginGroups = SystemBase::groups($baseType);
        return [
            'title' => '数据字典管理',
            'types' => $types,
            'type' => $mode,
            'baseType' => $baseType,
            'pluginGroups' => $pluginGroups,
            'pluginGroup' => trim(strval(request()->get('plugin_group', ''))),
            'pluginGroupOptions' => self::buildPluginGroupOptions($pluginGroups),
            'requestBaseUrl' => request()->baseUrl(),
        ];
    }

    /**
     * 构建数据字典表单上下文.
     * @return array<string, mixed>
     */
    public static function buildFormContext(string $action): array
    {
        $types = SystemBase::types();
        $id = intval(request()->param('id', 0));
        $type = strval(request()->param('type', ''));
        return [
            'action' => $action,
            'id' => $id,
            'isEdit' => $action === 'edit' || $id > 0,
            'types' => $types,
            'type' => $type,
            'actionUrl' => url($action, array_filter([
                'id' => $id ?: null,
                'type' => $type ?: null,
            ]))->build(),
            'pluginOptions' => self::buildPluginOptions(),
        ];
    }

    /**
     * 应用数据字典列表查询.
     * @param array<string, mixed> $context
     */
    public static function applyIndexQuery(QueryHelper $query, array $context = []): void
    {
        $baseType = strval($context['baseType'] ?? request()->get('base_type', ''));
        $mode = self::normalizeMode(strval($context['type'] ?? request()->get('type', 'index')));
        if ($baseType !== '') {
            $query->where(['type' => $baseType]);
        }
        $query->like('code,name')->dateBetween('create_time');
        $query->where(['status' => $mode === 'recycle' ? 0 : 1]);
        $group = trim(strval($context['pluginGroup'] ?? request()->get('plugin_group', '')));
        if ($group !== '') {
            $ids = SystemBase::idsByPluginGroup($group, $baseType);
            empty($ids) ? $query->whereRaw('1 = 0') : $query->whereIn('id', $ids);
        }
    }

    /**
     * 加载表单数据.
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     * @throws Exception
     */
    public static function loadFormData(array $context): array
    {
        $data = [];
        $id = intval($context['id'] ?? 0);
        $types = is_array($context['types'] ?? null) ? $context['types'] : [];
        $type = strval($context['type'] ?? '');
        if ($id > 0) {
            $item = SystemBase::mk()->findOrEmpty($id);
            if ($item->isEmpty()) {
                throw new Exception('数据记录不存在！');
            }
            $data = $item->toArray();
        }
        $meta = SystemBase::parseContent(strval($data['content'] ?? ''));
        $codes = (array)($meta['plugin'] ?: $meta['plugins']);
        $data['plugin_code'] = count($codes) === 1 ? strval(current($codes)) : '';
        $data['content_text'] = strval($meta['text'] ?? ($data['content'] ?? ''));
        $data['type_select'] = strval($data['type'] ?? ($type !== '' ? $type : ($types[0] ?? '')));
        $data['type'] = strval($data['type'] ?? $type);
        return $data;
    }

    /**
     * 整理表单保存数据.
     * @param array<string, mixed> $data
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function prepareFormData(array $data, array $context): array
    {
        $data['id'] = intval($context['id'] ?? 0);
        $data['content'] = SystemBase::packContent(
            strval($data['content_text'] ?? request()->post('content_text', '')),
            $data['plugin_code'] ?? request()->post('plugin_code', '')
        );
        unset($data['content_text'], $data['plugin_code'], $data['type_select']);
        return $data;
    }

    /**
     * 保存表单数据.
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public static function saveFormData(array $data): void
    {
        self::assertUniqueCode($data);
        $id = intval($data['id'] ?? 0);
        $item = $id > 0 ? SystemBase::mk()->findOrEmpty($id) : SystemBase::mk();
        if ($id > 0 && $item->isEmpty()) {
            throw new Exception('数据记录不存在！');
        }
        $item->save([
            'type' => strval($data['type'] ?? ''),
            'code' => strval($data['code'] ?? ''),
            'name' => strval($data['name'] ?? ''),
            'content' => strval($data['content'] ?? ''),
            'sort' => intval(request()->post('sort', $item->getAttr('sort') ?? 0)),
            'status' => intval(request()->post('status', $item->getAttr('status') ?? 1)),
        ]);
    }

    /**
     * 构建插件选项.
     * @return array<string, string>
     */
    public static function buildPluginOptions(): array
    {
        $options = [];
        foreach (SystemBase::pluginOptions() as $plugin) {
            $code = strval($plugin['code'] ?? '');
            $name = strval($plugin['name'] ?? $code);
            if ($code !== '') {
                $options[$code] = "{$name} [ {$code} ]";
            }
        }
        return $options;
    }

    /**
     * 校验数据编码唯一性.
     * @param array<string, mixed> $data
     * @throws Exception
     */
    private static function assertUniqueCode(array $data): void
    {
        $exists = SystemBase::mk()
            ->where([
                'code' => strval($data['code'] ?? ''),
                'type' => strval($data['type'] ?? ''),
            ])
            ->where('id', '<>', intval($data['id'] ?? 0))
            ->count();
        if ($exists > 0) {
            throw new Exception('数据编码已经存在！');
        }
    }

    /**
     * 解析列表类型.
     * @param array<int, string> $types
     */
    private static function resolveIndexType(array $types, string $type): string
    {
        $type = trim($type);
        return $type !== '' ? $type : strval($types[0] ?? '-');
    }

    private static function normalizeMode(string $type): string
    {
        return $type === 'recycle' ? 'recycle' : 'index';
    }

    /**
     * 构建插件分组选项.
     * @param array<int, array<string, mixed>> $groups
     * @return array<string, string>
     */
    private static function buildPluginGroupOptions(array $groups): array
    {
        $options = [];
        foreach ($groups as $group) {
            $code = strval($group['code'] ?? '');
            if ($code !== '') {
                $options[$code] = strval($group['name'] ?? $code);
            }
        }
        return $options;
    }
}
