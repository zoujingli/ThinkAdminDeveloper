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

namespace think\admin;

use think\admin\helper\QueryHelper;
use think\admin\model\QueryFactory;
use think\db\BaseQuery;
use think\db\Mongo;
use think\db\Query;
use think\model\concern\SoftDelete;
use Throwable;

/**
 * 基础模型类.
 * @class Model
 * @mixin \think\db\Query
 * @method static bool mSave(array $data = [], string $field = '', mixed $where = []) 快捷更新
 * @method static bool mDelete(string $field = '', mixed $where = []) 快捷删除
 * @method static bool|array mForm(string $template = '', string $field = '', mixed $where = [], array $data = []) 快捷表单
 * @method static bool|integer mUpdate(array $data = [], string $field = '', mixed $where = []) 快捷保存
 * @method static QueryHelper mQuery($input = null, callable $callable = null) 快捷查询
 */
abstract class Model extends \think\Model
{
    /**
     * 日志过滤.
     * @var callable
     */
    public static $oplogCall;

    protected $autoWriteTimestamp = 'datetime';

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    /**
     * 日志类型.
     * @var string
     */
    protected $oplogType;

    /**
     * 日志名称.
     * @var string
     */
    protected $oplogName;

    public function __construct(array|object $data = [])
    {
        parent::__construct($data);
        $this->resolveSoftDeleteOptions();
        $this->bootSoftDeleteAppend();
    }

    /**
     * 按真实表结构推断当前模型的软删配置。
     */
    protected function resolveSoftDeleteOptions(): void
    {
        if (!in_array(SoftDelete::class, $this->allTraits(), true)) {
            return;
        }

        $field = $this->getOption('deleteTime', 'delete_time');
        if ($field === false) {
            return;
        }

        try {
            $fields = array_keys((array)$this->getFields());
        } catch (Throwable $exception) {
            return;
        }

        if (in_array(strval($field), $fields, true)) {
            return;
        }

        $options = [];
        foreach (['delete_time', 'deleted_time', 'deleted_at'] as $name) {
            if (in_array($name, $fields, true)) {
                $options['deleteTime'] = $name;
                break;
            }
        }

        if (empty($options) && in_array('deleted', $fields, true)) {
            $options['deleteTime'] = 'deleted';
            $options['defaultSoftDelete'] = 0;
        }

        if (empty($options)) {
            $options['deleteTime'] = false;
        }

        foreach ($options as $name => $value) {
            $this->setOption($name, $value);
        }
    }

    /**
     * 收集当前模型及父类声明的全部 Trait。
     * @return array<int, string>
     */
    private function allTraits(): array
    {
        $traits = [];
        foreach ([static::class, ...class_parents(static::class)] as $class) {
            $traits = array_merge($traits, class_uses($class) ?: []);
        }

        return array_values(array_unique($traits));
    }

    /**
     * 为软删模型统一追加 deleted 虚拟属性。
     */
    protected function bootSoftDeleteAppend(): void
    {
        if (!$this->usesSoftDeleteQuery()) {
            return;
        }

        $append = (array)$this->getOption('append', []);
        foreach (['deleted'] as $field) {
            if (!in_array($field, $append, true)) {
                $append[] = $field;
            }
        }
        $this->setOption('append', $append);
    }

    /**
     * 判断当前模型是否启用了软删除查询。
     */
    private function usesSoftDeleteQuery(): bool
    {
        return in_array(SoftDelete::class, $this->allTraits(), true)
            && $this->getOption('deleteTime', 'delete_time') !== false;
    }

    /**
     * 静态魔术方法.
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return false|int|mixed|QueryHelper
     */
    public static function __callStatic($method, $args)
    {
        return QueryHelper::make(static::class, $method, $args, function ($method, $args) {
            return parent::__callStatic($method, $args);
        });
    }

    /**
     * 创建查询实例.
     * @return Mongo|Query
     */
    public static function mq(array $data = [])
    {
        return QueryFactory::build(static::mk($data)->newQuery());
    }

    /**
     * 创建模型实例.
     * @template t of static
     * @param mixed $data
     * @return static|t
     */
    public static function mk($data = [])
    {
        return new static($data);
    }

    /**
     * 调用魔术方法.
     * @param string $method 方法名称
     * @param array $args 调用参数
     * @return $this|false|mixed
     */
    public function __call($method, $args)
    {
        if (($compat = $this->handleCompatQueryCall($method, $args)) !== null) {
            return $compat;
        }

        $oplogs = [
            'onAdminSave' => '修改%s[%s]状态',
            'onAdminUpdate' => '更新%s[%s]记录',
            'onAdminInsert' => '增加%s[%s]成功',
            'onAdminDelete' => '删除%s[%s]成功',
        ];
        if (isset($oplogs[$method])) {
            if ($this->oplogType && $this->oplogName) {
                $changeIds = $args[0] ?? '';
                if (is_callable(static::$oplogCall)) {
                    $changeIds = call_user_func(static::$oplogCall, $method, $changeIds, $this);
                }
                sysoplog($this->oplogType, lang($oplogs[$method], [lang($this->oplogName), $changeIds]));
            }
            return $this;
        }
        return parent::__call($method, $args);
    }

    /**
     * 在模型魔术调用阶段兜底兼容旧软删查询写法。
     * @param string $method 调用方法
     * @param array $args 调用参数
     */
    private function handleCompatQueryCall(string $method, array &$args): mixed
    {
        $query = $this->db();
        if ($this->normalizeLegacySoftDeleteCall($query, $method, $args)) {
            return $query;
        }

        return null;
    }

    /**
     * 拦截旧 where 系列软删条件，转换为 ORM 当前标准。
     * @param BaseQuery $query 查询对象
     * @param string $method 调用方法
     * @param array $args 调用参数
     */
    public function normalizeLegacySoftDeleteCall(BaseQuery $query, string $method, array &$args): bool
    {
        if (!$this->usesSoftDeleteQuery()) {
            return false;
        }

        return match ($method) {
            'where', 'whereOr' => $this->normalizeLegacySoftDeleteWhere($query, $args),
            'whereNull', 'whereNotNull' => $this->normalizeLegacySoftDeleteNull($args),
            default => false,
        };
    }

    /**
     * 兼容 where / whereOr 中的旧 deleted 条件。
     * @param BaseQuery $query 查询对象
     * @param array $args 调用参数
     */
    private function normalizeLegacySoftDeleteWhere(BaseQuery $query, array &$args): bool
    {
        if (!isset($args[0])) {
            return false;
        }

        $first = $args[0];
        if (is_array($first)) {
            [$filters, $mode, $matched] = $this->normalizeLegacySoftDeleteFilters($first);
            if (!$matched) {
                return false;
            }
            if ($mode === 'onlyTrashed') {
                $query->onlyTrashed();
            }
            $args[0] = $filters;
            return empty($filters);
        }

        if (!is_string($first) || $first !== 'deleted') {
            return false;
        }

        $value = $args[2] ?? ($args[1] ?? null);
        if ($this->isDeletedTruthy($value)) {
            $query->onlyTrashed();
        }

        return true;
    }

    /**
     * 批量转换数组条件中的旧软删字段。
     * @return array{0:array,1:string,2:bool}
     */
    private function normalizeLegacySoftDeleteFilters(array $filters): array
    {
        $matched = false;
        $mode = 'default';
        $result = [];

        if (array_is_list($filters)) {
            foreach ($filters as $item) {
                if (is_array($item) && isset($item[0]) && $item[0] === 'deleted') {
                    $matched = true;
                    $value = $item[2] ?? ($item[1] ?? null);
                    if ($this->isDeletedTruthy($value)) {
                        $mode = 'onlyTrashed';
                    }
                    continue;
                }

                if (is_array($item) && isset($item[0]) && in_array($item[0], ['deleted_at', 'deleted_time'], true)) {
                    $matched = true;
                    $item[0] = $this->softDeleteField();
                }

                $result[] = $item;
            }
        } else {
            foreach ($filters as $key => $value) {
                if ($key === 'deleted') {
                    $matched = true;
                    if ($this->isDeletedTruthy($value)) {
                        $mode = 'onlyTrashed';
                    }
                    continue;
                }

                if (in_array($key, ['deleted_at', 'deleted_time'], true)) {
                    $matched = true;
                    $result[$this->softDeleteField()] = $value;
                    continue;
                }

                $result[$key] = $value;
            }
        }

        return [$result, $mode, $matched];
    }

    /**
     * 判断旧 deleted 标记是否表示已删除。
     * @param mixed $value 字段值
     */
    private function isDeletedTruthy(mixed $value): bool
    {
        return in_array($value, [1, '1', true], true);
    }

    /**
     * 获取当前模型实际使用的软删字段名。
     */
    private function softDeleteField(): string
    {
        $field = $this->getOption('deleteTime', 'delete_time');
        return is_string($field) && $field !== '' ? $field : 'delete_time';
    }

    /**
     * 兼容 whereNull / whereNotNull 的旧软删字段名。
     * @param array $args 调用参数
     */
    private function normalizeLegacySoftDeleteNull(array &$args): bool
    {
        if (empty($args[0]) || !is_string($args[0])) {
            return false;
        }

        if (in_array($args[0], ['deleted_at', 'deleted_time'], true)) {
            $args[0] = $this->softDeleteField();
        }

        return true;
    }

    /**
     * 追加模型数据并标记为待持久化变更。
     */
    public function appendData(array $data, bool $overwrite = false): static
    {
        foreach ($data as $name => $value) {
            if ($overwrite || !$this->hasData($name)) {
                $this->setAttr($name, $value);
            }
        }

        return $this;
    }

    /**
     * 兼容旧软删字段赋值入口。
     * @param string $name 字段名称
     * @param mixed $value 字段数据
     * @return $this
     */
    public function set(string $name, $value)
    {
        [$name, $value] = $this->normalizeLegacySoftDeleteSet($name, $value);
        return parent::set($name, $value);
    }

    /**
     * 兼容旧 deleted / deleted_at / deleted_time 写入。
     * @return array{0:string,1:mixed}
     */
    private function normalizeLegacySoftDeleteSet(string $name, mixed $value): array
    {
        if (!in_array($name, ['deleted', 'deleted_at', 'deleted_time'], true) || !$this->usesSoftDeleteQuery()) {
            return [$name, $value];
        }

        $field = $this->softDeleteField();
        if ($name === 'deleted') {
            if ($field === 'deleted') {
                return [$field, $this->isDeletedTruthy($value) ? 1 : 0];
            }

            return [$field, $this->normalizeLegacySoftDeleteTime($value)];
        }

        if ($field === 'deleted') {
            return [$field, $this->isDeletedTruthy($value) ? 1 : 0];
        }

        return [$field, $this->normalizeLegacySoftDeleteTime($value)];
    }

    /**
     * 将旧布尔软删标记转换为 ORM 标准时间字段值。
     * @param mixed $value 字段值
     */
    private function normalizeLegacySoftDeleteTime(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0' || $value === false) {
            return null;
        }

        if ($this->isDeletedTruthy($value) || $value === 1 || $value === '1') {
            return date('Y-m-d H:i:s');
        }

        return is_scalar($value) ? strval($value) : date('Y-m-d H:i:s');
    }

    /**
     * 兼容读取旧版 deleted_at 访问器。
     * @param mixed $value 原始字段值
     * @param array $data 当前模型数据
     */
    public function getDeletedAtAttr($value, array $data): mixed
    {
        $field = $this->softDeleteField();
        if ($field === 'deleted') {
            return $data['delete_time'] ?? $data['deleted_time'] ?? $value;
        }
        return $data[$field] ?? $data['delete_time'] ?? $data['deleted_time'] ?? $value;
    }

    /**
     * 追加 deleted 虚拟属性，兼容旧模板与旧业务判断。
     * @param mixed $value 原始字段值
     * @param array $data 当前模型数据
     */
    public function getDeletedAttr($value, array $data): int
    {
        $field = $this->softDeleteField();
        return empty($data[$field] ?? ($data['delete_time'] ?? $data['deleted_time'] ?? null)) ? 0 : 1;
    }

    /**
     * 兼容旧 deleted 写法，自动映射为当前软删字段值。
     * @param mixed $value 字段数据
     */
    public function setDeletedAttr($value): int|string
    {
        return $this->softDeleteField() === 'deleted'
            ? ($this->isDeletedTruthy($value) ? 1 : 0)
            : (is_scalar($value) ? strval($value) : '');
    }
}
