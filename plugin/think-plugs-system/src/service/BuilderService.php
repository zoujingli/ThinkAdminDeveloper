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

use plugin\system\model\SystemBuilder;
use think\admin\Exception;
use think\admin\helper\PageBuilder;
use think\admin\Library;

class BuilderService
{
    public const PREFIX = 'SystemBuilder:';

    public static function prefix(): string
    {
        return self::PREFIX;
    }

    public static function typeOptions(): array
    {
        return [
            'form' => '动态表单',
            'page' => '动态列表',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            1 => '启用',
            0 => '禁用',
        ];
    }

    public static function dataName(string $code): string
    {
        return self::prefix() . self::normalizeCode($code);
    }

    public static function normalizeCode(string $code): string
    {
        return strtolower(trim($code));
    }

    public static function normalizeRecord(array $data): array
    {
        $config = [];
        if (!empty($data['value']) && is_string($data['value'])) {
            $config = json_decode($data['value'], true) ?: [];
        }

        $name = strval($data['name'] ?? '');
        $code = str_starts_with($name, self::prefix()) ? substr($name, strlen(self::prefix())) : strval($config['code'] ?? '');
        $config['id'] = intval($data['id'] ?? 0);
        $config['code'] = strval($config['code'] ?? $code);
        $config['title'] = strval($config['title'] ?? $config['code']);
        $config['type'] = in_array($config['type'] ?? '', ['form', 'page'], true) ? strval($config['type']) : 'form';
        $config['table_name'] = strval($config['table_name'] ?? '');
        $config['status'] = intval($config['status'] ?? 1) > 0 ? 1 : 0;
        $config['remark'] = strval($config['remark'] ?? '');
        $config['form_field_names'] = self::normalizeNames($config['form_field_names'] ?? [], []);
        $config['search_field_names'] = self::normalizeNames($config['search_field_names'] ?? [], []);
        $config['table_field_names'] = self::normalizeNames($config['table_field_names'] ?? [], []);
        $config['form_fields'] = is_array($config['form_fields'] ?? null) ? array_values($config['form_fields']) : [];
        $config['search_fields'] = is_array($config['search_fields'] ?? null) ? array_values($config['search_fields']) : [];
        $config['table_columns'] = is_array($config['table_columns'] ?? null) ? array_values($config['table_columns']) : [];
        $config['table_options'] = is_array($config['table_options'] ?? null) ? $config['table_options'] : [];
        $config['update_time'] = strval($data['update_time'] ?? '');
        $config['create_time'] = strval($data['create_time'] ?? '');
        return $config;
    }

    public static function findById(int $id): array
    {
        $record = SystemBuilder::mk()->where(['id' => $id])->findOrEmpty();
        if ($record->isEmpty()) {
            throw new Exception('动态配置不存在！');
        }

        $data = $record->toArray();
        if (!str_starts_with(strval($data['name'] ?? ''), self::prefix())) {
            throw new Exception('动态配置不存在！');
        }

        return self::normalizeRecord($data);
    }

    public static function tableOptions(): array
    {
        [$tables] = SystemService::getTables();
        $options = [];
        foreach ($tables as $table) {
            $table = strval($table);
            if (!self::isAllowedTable($table)) {
                continue;
            }
            $options[$table] = $table;
        }
        ksort($options);
        return $options;
    }

    public static function assertAllowedTable(string $table): void
    {
        if (!isset(self::tableOptions()[$table])) {
            throw new Exception('所选数据表不可用！');
        }
    }

    public static function tableSchema(string $table): array
    {
        self::assertAllowedTable($table);
        $metas = (array)Library::$sapp->db->getFields($table);
        $options = [];
        $fields = [];
        foreach ($metas as $name => $meta) {
            $meta = is_array($meta) ? $meta : ['type' => strval($meta)];
            $type = strtolower(strval($meta['type'] ?? 'string'));
            $label = trim(strval($meta['comment'] ?? '')) ?: strval($name);
            $fields[$name] = [
                'name' => strval($name),
                'label' => $label,
                'type' => $type,
                'primary' => self::isPrimaryField($name, $meta),
                'nullable' => self::isNullable($meta),
                'form' => self::defaultFormField(strval($name), $label, $meta),
                'search' => self::defaultSearchField(strval($name), $label, $meta),
                'column' => self::defaultTableColumn(strval($name), $label, $meta),
            ];
            $options[$name] = sprintf('%s (%s)', $label, $type ?: 'string');
        }

        return [
            'table' => $table,
            'primary' => self::primaryField($metas),
            'options' => $options,
            'fields' => $fields,
        ];
    }

    public static function buildDefinitionPayload(array $data): array
    {
        $title = trim(strval($data['title'] ?? ''));
        $code = self::normalizeCode(strval($data['code'] ?? ''));
        $type = in_array($data['type'] ?? '', ['form', 'page'], true) ? strval($data['type']) : 'form';
        $table = trim(strval($data['table_name'] ?? ''));
        if ($title === '') {
            throw new Exception('配置名称不能为空！');
        }
        if ($code === '' || !preg_match('/^[a-z][a-z0-9_]{2,30}$/', $code)) {
            throw new Exception('配置编码格式错误！');
        }

        self::assertAllowedTable($table);
        $schema = self::tableSchema($table);
        $allowed = array_keys($schema['fields']);
        $formNames = self::normalizeNames($data['form_field_names'] ?? [], $allowed);
        $searchNames = self::normalizeNames($data['search_field_names'] ?? [], $allowed);
        $tableNames = self::normalizeNames($data['table_field_names'] ?? [], $allowed);

        return [
            'title' => $title,
            'code' => $code,
            'type' => $type,
            'table_name' => $table,
            'status' => intval($data['status'] ?? 1) > 0 ? 1 : 0,
            'remark' => trim(strval($data['remark'] ?? '')),
            'form_field_names' => $formNames,
            'search_field_names' => $searchNames,
            'table_field_names' => $tableNames,
            'form_fields' => self::resolveConfigList(strval($data['form_fields_json'] ?? ''), $formNames, $schema, 'form'),
            'search_fields' => self::resolveConfigList(strval($data['search_fields_json'] ?? ''), $searchNames, $schema, 'search'),
            'table_columns' => self::resolveConfigList(strval($data['table_columns_json'] ?? ''), $tableNames, $schema, 'column'),
            'table_options' => self::decodeJsonAssoc(strval($data['table_options_json'] ?? '')),
        ];
    }

    public static function formatJson($data): string
    {
        if (empty($data)) {
            return '';
        }

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        return is_string($encoded) ? $encoded : '';
    }

    public static function decodeChoiceValue($value, array $field): mixed
    {
        if (($field['type'] ?? '') !== 'checkbox') {
            return $value;
        }

        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $json = json_decode($value, true);
        if (is_array($json)) {
            return array_values($json);
        }

        return array_values(array_filter(array_map('trim', explode(',', $value)), static function ($item): bool {
            return $item !== '';
        }));
    }

    public static function encodeChoiceValue($value): array|string
    {
        if (!is_array($value)) {
            return $value;
        }

        return json_encode(array_values($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '[]';
    }

    public static function restoreJsFragments($value)
    {
        if (is_array($value) && isset($value['type'], $value['code']) && $value['type'] === 'js' && is_string($value['code'])) {
            return PageBuilder::raw($value['code']);
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = self::restoreJsFragments($item);
            }
        }

        return $value;
    }

    private static function resolveConfigList(string $json, array $selected, array $schema, string $scene): array
    {
        if (trim($json) !== '') {
            return self::decodeJsonList($json);
        }

        $items = [];
        foreach ($selected as $name) {
            if (!isset($schema['fields'][$name][$scene])) {
                continue;
            }
            $items[] = $schema['fields'][$name][$scene];
        }
        return $items;
    }

    private static function decodeJsonList(string $json): array
    {
        $data = json_decode($json, true);
        if (!is_array($data) || !array_is_list($data)) {
            throw new Exception('规则 JSON 必须是数组格式！');
        }

        return array_values(array_filter($data, static function ($item): bool {
            return is_array($item);
        }));
    }

    private static function decodeJsonAssoc(string $json): array
    {
        if (trim($json) === '') {
            return [];
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new Exception('高级配置 JSON 格式错误！');
        }

        return $data;
    }

    private static function normalizeNames($items, array $allowed): array
    {
        $items = is_array($items) ? $items : [];
        $names = [];
        foreach ($items as $item) {
            $item = trim(strval($item));
            if ($item === '') {
                continue;
            }
            if ($allowed && !in_array($item, $allowed, true)) {
                continue;
            }
            if (!in_array($item, $names, true)) {
                $names[] = $item;
            }
        }

        return $names;
    }

    private static function defaultFormField(string $name, string $label, array $meta): array
    {
        $field = [
            'name' => $name,
            'title' => $label,
            'type' => 'text',
        ];

        $type = strtolower(strval($meta['type'] ?? 'string'));
        if (self::isLongTextType($type)) {
            $field['type'] = 'textarea';
            $field['attrs'] = ['rows' => 4];
        } elseif (self::isNumericType($type)) {
            $field['type'] = 'number';
            $field['attrs'] = ['type' => 'number'];
        }

        if (self::isStatusLikeField($name, $type)) {
            $field['type'] = 'radio';
            $field['options'] = self::statusOptions();
        }

        if (self::isImageLikeField($name)) {
            $field['type'] = 'image';
        }

        if (self::isRequiredField($name, $meta)) {
            $field['required'] = true;
        }

        if ($pattern = self::guessPattern($name)) {
            $field['pattern'] = $pattern;
        }

        return $field;
    }

    private static function defaultSearchField(string $name, string $label, array $meta): array
    {
        $type = strtolower(strval($meta['type'] ?? 'string'));
        if (self::isDateLikeField($name, $type)) {
            return [
                'type' => 'input',
                'name' => $name,
                'label' => $label,
                'placeholder' => sprintf('请选择%s', $label),
                'attrs' => ['data-date-range' => null],
                'query' => 'dateBetween',
            ];
        }

        if (self::isStatusLikeField($name, $type)) {
            return [
                'type' => 'select',
                'name' => $name,
                'label' => $label,
                'options' => self::statusOptions(),
                'query' => 'equal',
            ];
        }

        return [
            'type' => 'input',
            'name' => $name,
            'label' => $label,
            'placeholder' => sprintf('请输入%s', $label),
            'query' => self::isNumericType($type) ? 'equal' : 'like',
        ];
    }

    private static function defaultTableColumn(string $name, string $label, array $meta): array
    {
        $type = strtolower(strval($meta['type'] ?? 'string'));
        $column = [
            'field' => $name,
            'title' => $label,
            'minWidth' => self::isLongTextType($type) ? 220 : 140,
        ];

        if (!self::isLongTextType($type)) {
            $column['sort'] = true;
        }

        return $column;
    }

    private static function primaryField(array $fields): string
    {
        foreach ($fields as $name => $meta) {
            if (self::isPrimaryField(strval($name), is_array($meta) ? $meta : [])) {
                return strval($name);
            }
        }

        return array_key_exists('id', $fields) ? 'id' : strval(array_key_first($fields));
    }

    private static function isAllowedTable(string $table): bool
    {
        return $table !== '' && !str_starts_with($table, 'sqlite_');
    }

    private static function isRequiredField(string $name, array $meta): bool
    {
        if (self::isPrimaryField($name, $meta) || self::isDateLikeField($name, strtolower(strval($meta['type'] ?? '')))) {
            return false;
        }

        return !self::isNullable($meta) && !array_key_exists('default', $meta);
    }

    private static function isNullable(array $meta): bool
    {
        if (array_key_exists('null', $meta)) {
            return !empty($meta['null']);
        }
        if (array_key_exists('nullable', $meta)) {
            return !empty($meta['nullable']);
        }
        if (array_key_exists('notnull', $meta)) {
            return empty($meta['notnull']);
        }
        return true;
    }

    private static function isPrimaryField(string $name, array $meta): bool
    {
        return $name === 'id'
            || !empty($meta['primary'])
            || !empty($meta['pk']);
    }

    private static function isNumericType(string $type): bool
    {
        return (bool)preg_match('/int|decimal|float|double|numeric|real/', $type);
    }

    private static function isLongTextType(string $type): bool
    {
        return (bool)preg_match('/text|json|blob/', $type);
    }

    private static function isDateLikeField(string $name, string $type): bool
    {
        return (bool)preg_match('/date|time|timestamp/', $type)
            || (bool)preg_match('/(_time|_at|date)$/', $name);
    }

    private static function isStatusLikeField(string $name, string $type): bool
    {
        return self::isNumericType($type) && (bool)preg_match('/(^|_)(status|state|enabled|disable)$/', $name);
    }

    private static function isImageLikeField(string $name): bool
    {
        return in_array($name, ['headimg', 'avatar', 'image', 'images', 'cover'], true)
            || (bool)preg_match('/(image|avatar|cover|headimg)$/', $name);
    }

    private static function guessPattern(string $name): ?string
    {
        return match (true) {
            (bool)preg_match('/email/', $name) => 'email',
            (bool)preg_match('/(phone|mobile)/', $name) => 'mobile',
            (bool)preg_match('/url$/', $name) => 'url',
            default => null,
        };
    }
}
