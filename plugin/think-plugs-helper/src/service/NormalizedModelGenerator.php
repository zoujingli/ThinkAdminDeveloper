<?php

declare(strict_types=1);

namespace plugin\helper\service;

use Exception;
use think\db\Query;
use think\ide\ModelGenerator;

class NormalizedModelGenerator extends ModelGenerator
{
    protected function getPropertiesFromTable()
    {
        try {
            $query = $this->model->db();
            if ($query instanceof Query) {
                $fields = $query->getFields();
            }
        } catch (Exception $exception) {
            $this->output->warning($exception->getMessage());
        }

        if (empty($fields)) {
            return;
        }

        $dateFormat = $this->model->getOption('dateFormat');
        $createTime = $this->normalizeFieldName(strval($this->model->getOption('createTime', 'create_time')));
        $updateTime = $this->normalizeFieldName(strval($this->model->getOption('updateTime', 'update_time')));
        $deleteTime = $this->normalizeFieldName(strval($this->model->getOption('deleteTime', 'delete_time')));
        $fieldType = $this->model->getOption('type');

        foreach ($fields as $rawName => $field) {
            $name = $this->normalizeFieldName($rawName);
            if ($name === null) {
                continue;
            }

            if (in_array($name, [$createTime, $updateTime, $deleteTime], true)) {
                $type = str_contains($dateFormat, '\\') ? $dateFormat : 'string';
            } elseif (!empty($fieldType[$rawName])) {
                $type = $fieldType[$rawName];

                if (is_array($type)) {
                    [$type, $param] = $type;
                } elseif (strpos($type, ':')) {
                    [$type, $param] = explode(':', $type, 2);
                }

                switch ($type) {
                    case 'timestamp':
                    case 'datetime':
                        $format = !empty($param) ? $param : $dateFormat;
                        $type = str_contains($format, '\\') ? $format : 'string';
                        break;
                    case 'json':
                        $type = 'array';
                        break;
                    case 'serialize':
                        $type = 'mixed';
                        break;
                }
            } else {
                if (!preg_match('/^([\w]+)(\(([\d]+)*(,([\d]+))*\))*(.+)*$/', $field['type'], $matches)) {
                    continue;
                }

                $limit = null;
                $type = $matches[1];
                if (count($matches) > 2) {
                    $limit = $matches[3] ? (int) $matches[3] : null;
                }

                if ($type === 'tinyint' && $limit === 1) {
                    $type = 'boolean';
                }

                switch ($type) {
                    case 'varchar':
                    case 'char':
                    case 'tinytext':
                    case 'mediumtext':
                    case 'longtext':
                    case 'text':
                    case 'timestamp':
                    case 'date':
                    case 'time':
                    case 'guid':
                    case 'datetimetz':
                    case 'datetime':
                    case 'set':
                    case 'enum':
                        $type = 'string';
                        break;
                    case 'tinyint':
                    case 'smallint':
                    case 'mediumint':
                    case 'int':
                    case 'bigint':
                        $type = 'integer';
                        break;
                    case 'decimal':
                    case 'float':
                        $type = 'float';
                        break;
                    case 'boolean':
                        $type = 'boolean';
                        break;
                    default:
                        $type = 'mixed';
                        break;
                }
            }

            $this->addProperty($name, $type, true, true, $field['comment'] ?? null);
        }
    }

    private function normalizeFieldName(string $name): ?string
    {
        return match ($name) {
            'deleted', 'deleted_at', 'deleted_time' => 'delete_time',
            'deleted_by' => null,
            default => $name,
        };
    }
}
