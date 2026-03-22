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

namespace think\admin\helper;

use think\admin\Helper;
use think\admin\Library;
use think\admin\model\QueryFactory;
use think\admin\runtime\SystemContext;
use think\admin\service\AppService;
use think\Container;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\PDOConnection;
use think\db\Query;
use think\exception\HttpResponseException;
use think\Model;

/**
 * 查询、分页与表格处理器.
 * @see Query
 * @mixin \think\db\Query
 * @class QueryHelper
 * @method bool mSave(array $data = [], string $field = '', mixed $where = []) 快捷更新
 * @method bool mDelete(string $field = '', mixed $where = []) 快捷删除
 * @method array|bool mForm(string $tpl = '', string $field = '', mixed $where = [], array $data = []) 快捷表单
 * @method bool|int mUpdate(array $data = [], string $field = '', mixed $where = []) 快捷保存
 */
class QueryHelper extends Helper
{
    /**
     * 当前数据操作.
     */
    protected Query $query;

    /**
     * 初始化默认数据.
     */
    protected array $input = [];

    /**
     * 克隆属性复制.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
    }

    /**
     * QueryHelper 魔术方法调用.
     *
     * 支持链式调用 Query 类的所有方法
     * 如果方法名以 _ 开头或返回 Query 对象，则返回当前实例
     *
     * @param string $name 调用方法名称
     * @param array $args 调用参数内容
     * @return $this|mixed 返回当前实例或查询结果
     */
    public function __call(string $name, array $args)
    {
        return static::make($this->query, $name, $args, function ($name, $args) {
            if (is_callable($callable = [$this->query, $name])) {
                $value = call_user_func_array($callable, $args);
                if ($name[0] === '_' || $value instanceof $this->query) {
                    return $this;
                }
                return $value;
            }
            return $this;
        });
    }

    /**
     * 快捷助手调用钩子.
     *
     * 根据方法名调用对应的 Helper 类
     * 支持的快捷方法：mForm, mSave, mQuery, mDelete, mUpdate
     *
     * @param Model|Query|string $model 模型对象或查询
     * @param string $method 方法名称
     * @param array $args 方法参数
     * @param null|callable $nohook 未匹配时的回调函数
     * @return false|int|mixed|QueryHelper 返回处理结果
     */
    public static function make(Model|Query|string $model, string $method = 'init', array $args = [], ?callable $nohook = null): mixed
    {
        $hooks = [
            'mForm' => [FormHelper::class, 'init'],
            'mSave' => [SaveHelper::class, 'init'],
            'mQuery' => [QueryHelper::class, 'init'],
            'mDelete' => [DeleteHelper::class, 'init'],
            'mUpdate' => [AppService::class, 'update'],
        ];
        if (isset($hooks[$method])) {
            [$class, $method] = $hooks[$method];
            return Container::getInstance()->invokeClass($class)->{$method}($model, ...$args);
        }
        return is_callable($nohook) ? $nohook($method, $args) : false;
    }

    /**
     * 获取当前数据库查询对象
     *
     * @return Query 返回当前的 Query 对象
     */
    public function db(): Query
    {
        return $this->query;
    }

    /**
     * 初始化查询构建器.
     *
     * @param BaseQuery|Model|string $dbQuery 数据库查询对象或模型
     * @param null|array|string $input 输入数据（默认为空，自动从请求获取）
     * @param null|callable $callable 初始化回调函数
     * @return $this
     */
    public function init(BaseQuery|Model|string $dbQuery, array|string|null $input = null, ?callable $callable = null): QueryHelper
    {
        $this->input = $this->getInputData($input);
        $this->query = $this->autoSortQuery($dbQuery);
        is_callable($callable) && call_user_func($callable, $this, $this->query);
        return $this;
    }

    /**
     * 绑定排序并返回查询对象
     *
     * 自动根据请求参数绑定排序字段
     * 支持 POST 请求的拖拽排序功能
     *
     * @param BaseQuery|Model|string $dbQuery 数据库查询对象或模型
     * @param string $field 默认排序字段
     * @return Query 返回处理后的 Query 对象
     * @throws \InvalidArgumentException 不支持的查询类型时抛出
     */
    public function autoSortQuery(BaseQuery|Model|string $dbQuery, string $field = 'sort'): Query
    {
        $query = QueryFactory::build($dbQuery);
        if (!$query instanceof Query) {
            throw new \InvalidArgumentException('QueryHelper only supports relational Query instances.');
        }
        if ($this->app->request->isPost() && $this->app->request->post('action') === 'sort') {
            SystemContext::instance()->isLogin() or $this->class->error('请重新登录！');
            if (method_exists($query, 'getTableFields') && in_array($field, $query->getTableFields(), true)) {
                if ($this->app->request->has($pk = $query->getPk() ?: 'id', 'post')) {
                    $map = [$pk => $this->app->request->post($pk, 0)];
                    $data = [$field => intval($this->app->request->post($field, 0))];
                    try {
                        $query->newQuery()->where($map)->update($data);
                    } catch (\Throwable) {
                        $this->class->error('列表排序失败！');
                    }
                    $this->class->success('列表排序成功！', '');
                }
            }
            $this->class->error('列表排序失败！');
        }
        return $query;
    }

    /**
     * 设置 Like 查询条件.
     * @param array|string $fields 查询字段
     * @param string $split 前后分割符
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @return $this
     */
    public function like(array|string $fields, string $split = '', array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        $data = $this->getInputData($input ?: $this->input);
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            [$dk, $qk] = [$field, $field];
            if (stripos($field, $alias) !== false) {
                [$dk, $qk] = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                $this->query->whereLike($dk, "%{$split}{$data[$qk]}{$split}%");
            }
        }
        return $this;
    }

    /**
     * 设置 Equal 查询条件.
     * @param array|string $fields 查询字段
     * @param null|array|string $input 输入类型
     * @param string $alias 别名分割符
     * @return $this
     */
    public function equal(array|string $fields, array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        $data = $this->getInputData($input ?: $this->input);
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            [$dk, $qk] = [$field, $field];
            if (stripos($field, $alias) !== false) {
                [$dk, $qk] = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                $this->query->where($dk, strval($data[$qk]));
            }
        }
        return $this;
    }

    /**
     * 设置 IN 区间查询.
     * @param array|string $fields 查询字段
     * @param string $split 输入分隔符
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @return $this
     */
    public function in(array|string $fields, string $split = ',', array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        $data = $this->getInputData($input ?: $this->input);
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            [$dk, $qk] = [$field, $field];
            if (stripos($field, $alias) !== false) {
                [$dk, $qk] = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                $this->query->whereIn($dk, explode($split, strval($data[$qk])));
            }
        }
        return $this;
    }

    /**
     * 两字段范围查询.
     * @example field1:field2#field,field11:field22#field00
     * @param array|string $fields 查询字段
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @return $this
     */
    public function valueRange(array|string $fields, array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        $data = $this->getInputData($input ?: $this->input);
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            if (str_contains($field, ':')) {
                if (stripos($field, $alias) !== false) {
                    [$dk0, $qk0] = explode($alias, $field);
                    [$dk1, $dk2] = explode(':', $dk0);
                } else {
                    [$qk0] = [$dk1, $dk2] = explode(':', $field, 2);
                }
                if (isset($data[$qk0]) && $data[$qk0] !== '') {
                    $this->query->where([[$dk1, '<=', $data[$qk0]], [$dk2, '>=', $data[$qk0]]]);
                }
            }
        }
        return $this;
    }

    /**
     * 设置内容区间查询.
     * @param array|string $fields 查询字段
     * @param string $split 输入分隔符
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @return $this
     */
    public function valueBetween(array|string $fields, string $split = ' ', array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        return $this->setBetweenWhere($fields, $split, $input, $alias);
    }

    /**
     * 设置日期时间区间查询.
     * @param array|string $fields 查询字段
     * @param string $split 输入分隔符
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @return $this
     */
    public function dateBetween(array|string $fields, string $split = ' - ', array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        return $this->setBetweenWhere($fields, $split, $input, $alias, static function ($value, $type) {
            if (preg_match('#^\d{4}(-\d\d){2}\s+\d\d(:\d\d){2}$#', $value)) {
                return $value;
            }
            return $type === 'after' ? "{$value} 23:59:59" : "{$value} 00:00:00";
        });
    }

    /**
     * 仅查询已软删除的数据.
     * @return $this
     */
    public function onlyTrashed(): QueryHelper
    {
        $field = strval($this->query->getOption('deleteTime', 'delete_time'));
        $condition = ['not null', ''];
        $softDelete = $this->query->getOption('soft_delete');
        if (is_array($softDelete) && isset($softDelete[0])) {
            $field = strval($softDelete[0]);
            $softCondition = $softDelete[1] ?? null;
            if (is_array($softCondition) && isset($softCondition[0])) {
                $operator = strtolower(strval($softCondition[0]));
                if ($operator === '=' && array_key_exists(1, $softCondition)) {
                    $condition = ['<>', $softCondition[1]];
                }
            }
        }
        $this->query->useSoftDelete($field, $condition);
        return $this;
    }

    /**
     * 设置时间戳区间查询.
     * @param array|string $fields 查询字段
     * @param string $split 输入分隔符
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @return $this
     */
    public function timeBetween(array|string $fields, string $split = ' - ', array|string|null $input = null, string $alias = '#'): QueryHelper
    {
        return $this->setBetweenWhere($fields, $split, $input, $alias, static function ($value, $type) {
            if (preg_match('#^\d{4}(-\d\d){2}\s+\d\d(:\d\d){2}$#', $value)) {
                return strtotime($value);
            }
            return $type === 'after' ? strtotime("{$value} 23:59:59") : strtotime("{$value} 00:00:00");
        });
    }

    /**
     * 清空数据并保留表结构.
     * @return $this
     */
    public function empty(): QueryHelper
    {
        $table = $this->query->getTable();
        $ctype = strtolower($this->query->getConfig('type'));
        $connection = $this->query->getConnection();
        if ($ctype === 'mysql' && $connection instanceof PDOConnection) {
            $connection->execute("truncate table `{$table}`");
        } elseif (in_array($ctype, ['sqlsrv', 'oracle', 'pgsql'], true) && $connection instanceof PDOConnection) {
            $connection->execute("truncate table {$table}");
        } else {
            try {
                $this->query->newQuery()->whereRaw('1=1')->delete();
            } catch (\Throwable $exception) {
                trace_file($exception);
            }
        }
        return $this;
    }

    /**
     * 中间回调处理.
     * @return $this
     */
    public function filter(callable $after): QueryHelper
    {
        call_user_func($after, $this, $this->query);
        return $this;
    }

    /**
     * 输出 Layui.Table 组件数据或普通列表 JSON。
     * @param ?callable $befor 表单前置操作
     * @param ?callable $after 表单后置操作
     * @param string $template 视图模板文件
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function layTable(?callable $befor = null, ?callable $after = null, string $template = '')
    {
        if (in_array($this->output, ['get.json', 'get.layui.table'])) {
            if (is_callable($after)) {
                call_user_func($after, $this, $this->query);
            }
            if ($this->output === 'get.json') {
                $this->applyOrderParams($this->query);
                return $this->page(true, true, false, 0, $template);
            }
            $this->applyOrderParams($this->query);
            $get = $this->app->request->get();
            if (empty($get['page']) || empty($get['limit'])) {
                $data = $this->query->select()->toArray();
                $result = ['msg' => '', 'code' => 0, 'count' => count($data), 'data' => $data];
            } else {
                $cfg = ['list_rows' => $get['limit'], 'query' => $get];
                $data = $this->query->paginate($cfg, self::getCount($this->query))->toArray();
                $result = ['msg' => '', 'code' => 0, 'count' => $data['total'], 'data' => $data['data']];
            }
            if ($this->class->callback('_page_filter', $result['data'], $result) !== false) {
                self::xssFilter($result['data']);
                throw new HttpResponseException(json($result));
            }
            return $result;
        }
        if (is_callable($befor)) {
            call_user_func($befor, $this, $this->query);
        }
        $this->class->fetch($template);
        return null;
    }

    /**
     * 执行分页查询并按控制器约定渲染结果。
     * @param bool|int $page 是否启用分页
     * @param bool $display 是否渲染模板
     * @param bool|int $total 集合分页记录数
     * @param int $limit 集合每页记录数
     * @param string $template 模板文件名称
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function page($page = true, bool $display = true, $total = false, int $limit = 0, string $template = ''): array
    {
        if ($page !== false) {
            $get = $this->app->request->get();
            $limits = [10, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 120, 130, 140, 150, 160, 170, 180, 190, 200];
            if ($limit <= 1) {
                $limit = intval($get['limit'] ?? 20);
                if (!in_array($limit, $limits, true)) {
                    $limit = 20;
                }
            }
            $inner = strpos($get['spm'] ?? '', 'm-') === 0;
            $prefix = $inner ? (sysuri('system/index/index') . '#') : '';
            $config = ['list_rows' => $limit, 'query' => $get];
            if (is_numeric($page)) {
                $config['page'] = $page;
            } elseif (isset($get['page']) && is_numeric($get['page'])) {
                $config['page'] = max(1, intval($get['page']));
            }
            $data = ($paginate = $this->query->paginate($config, self::getCount($this->query, $total)))->toArray();
            $result = ['page' => ['limit' => $data['per_page'], 'total' => $data['total'], 'pages' => $data['last_page'], 'current' => $data['current_page']], 'list' => $data['data']];
            $select = "<select onchange='location.href=this.options[this.selectedIndex].value'>";
            if (in_array($limit, $limits, true)) {
                foreach ($limits as $num) {
                    $get = array_merge($get, ['limit' => $num, 'page' => 1]);
                    $url = $this->app->request->baseUrl() . '?' . http_build_query($get, '', '&', PHP_QUERY_RFC3986);
                    $select .= sprintf('<option data-num="%d" value="%s" %s>%d</option>', $num, $prefix . $url, $limit === $num ? 'selected' : '', $num);
                }
            } else {
                $select .= "<option selected>{$limit}</option>";
            }
            $html = lang('共 %s 条记录，每页显示 %s 条，共 %s 页当前显示第 %s 页。', [$data['total'], "{$select}</select>", $data['last_page'], $data['current_page']]);
            $link = $inner ? str_replace('<a href="', '<a data-open="' . $prefix, $paginate->render() ?: '') : ($paginate->render() ?: '');
            $this->class->assign('pagehtml', "<div class='pagination-container nowrap'><span>{$html}</span>{$link}</div>");
        } else {
            $result = ['list' => $this->query->select()->toArray()];
        }
        if ($this->class->callback('_page_filter', $result['list'], $result) !== false && $display) {
            if ($this->output === 'get.json') {
                $this->class->success('JSON-DATA', $result);
            } else {
                $this->class->fetch($template, $result);
            }
        }
        return $result;
    }

    /**
     * 获取输入数据.
     * @param null|array|string $input
     */
    private function getInputData($input): array
    {
        if (is_array($input)) {
            return $input;
        }
        $input = $input ?: 'request';
        return $this->app->request->{$input}();
    }

    /**
     * 设置区域查询条件.
     * @param array|string $fields 查询字段
     * @param string $split 输入分隔符
     * @param null|array|string $input 输入数据
     * @param string $alias 别名分割符
     * @param null|callable $callback 回调函数
     * @return $this
     */
    private function setBetweenWhere($fields, string $split = ' ', $input = null, string $alias = '#', ?callable $callback = null): QueryHelper
    {
        $data = $this->getInputData($input ?: $this->input);
        foreach (is_array($fields) ? $fields : explode(',', $fields) as $field) {
            [$dk, $qk] = [$field, $field];
            if (stripos($field, $alias) !== false) {
                [$dk, $qk] = explode($alias, $field);
            }
            if (isset($data[$qk]) && $data[$qk] !== '') {
                [$begin, $after] = explode($split, strval($data[$qk]));
                if (is_callable($callback)) {
                    $after = call_user_func($callback, $after, 'after');
                    $begin = call_user_func($callback, $begin, 'begin');
                }
                $this->query->whereBetween($dk, [$begin, $after]);
            }
        }
        return $this;
    }

    /**
     * 根据查询参数补充排序规则。
     */
    private function applyOrderParams(Query $query): void
    {
        $get = $this->app->request->get();
        if (isset($get['_field_'], $get['_order_'])) {
            $query->order("{$get['_field_']} {$get['_order_']}");
        }
    }

    /**
     * 查询对象数量统计。
     * @param bool|int $total
     * @return bool|int|string
     * @throws DbException
     */
    private static function getCount(Query $query, $total = false)
    {
        if ($total === true || is_numeric($total)) {
            return $total;
        }
        [$query, $options] = [clone $query, $query->getOptions()];
        if (isset($options['order'])) {
            $query->removeOption('order');
        }
        Library::$sapp->db->trigger('think_before_page_count', $query);
        if (empty($options['union'])) {
            return $query->count();
        }
        $table = [$query->buildSql() => '_union_count_'];
        return $query->newQuery()->table($table)->count();
    }

    /**
     * 输出 XSS 过滤处理。
     */
    private static function xssFilter(array &$items): void
    {
        foreach ($items as &$item) {
            if (is_array($item)) {
                self::xssFilter($item);
            } elseif (is_string($item)) {
                $item = htmlspecialchars($item, ENT_QUOTES);
            }
        }
    }
}
