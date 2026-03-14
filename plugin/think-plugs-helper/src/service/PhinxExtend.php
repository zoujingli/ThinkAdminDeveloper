<?php

declare(strict_types=1);

namespace plugin\helper\service;

use Phinx\Db\Adapter\AdapterInterface;
use Phinx\Db\Adapter\MysqlAdapter;
use Phinx\Db\Table;
use plugin\system\model\SystemMenu;
use think\admin\service\PluginService;
use think\admin\Library;
use think\admin\extend\ArrayTree;
use think\admin\extend\FileTools;
use think\admin\service\ProcessService;
use think\helper\Str;
use think\migration\Migrator;

/**
 * 数据库迁移扩展.
 * @class PhinxExtend
 */
class PhinxExtend
{
    public static function table(Migrator $migrator, string $name, string $comment = ''): Table
    {
        return $migrator->table($name, static::tableOptions($comment));
    }

    public static function tableOptions(string $comment = ''): array
    {
        if (strtolower(Library::$sapp->db->connect()->getConfig('type', 'mysql')) === 'mysql') {
            return ['engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => $comment];
        }

        return [];
    }

    /**
     * 批量写入菜单.
     * @param array $zdata 菜单数据
     * @param mixed $exists 检测条件
     */
    public static function write2menu(array $zdata, $exists = [], ?Migrator $migrator = null): bool
    {
        if ($migrator instanceof Migrator) {
            return static::write2menuByMigrator($migrator, $zdata, (array) $exists);
        }
        try {
            if (!empty($exists) && SystemMenu::mk()->where($exists)->findOrEmpty()->isExists()) {
                return false;
            }
        } catch (\Exception $exception) {
            return false;
        }
        foreach ($zdata as $one) {
            $pid1 = static::write1menu($one);
            if (!empty($one['subs'])) {
                foreach ($one['subs'] as $two) {
                    $pid2 = static::write1menu($two, $pid1);
                    if (!empty($two['subs'])) {
                        foreach ($two['subs'] as $thr) {
                            static::write1menu($thr, $pid2);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 按插件服务标准配置写入菜单.
     * @param class-string $service 插件服务类
     * @param array $root 菜单根节点兜底配置
     * @param array $exists 菜单存在检测兜底条件
     */
    public static function writePluginMenu(string $service, array $root = [], array $exists = [], ?Migrator $migrator = null): bool
    {
        if (!class_exists($service)) {
            return false;
        }

        PluginService::assertMenus($service);
        $menus = PluginService::menus($service);
        $root = array_replace($root, PluginService::menuRoot($service));
        $exists = array_replace($exists, PluginService::menuExists($service));
        if (!empty($root)) {
            if (!empty($menus)) {
                $root['subs'] = $menus;
            }
            $menus = [$root];
        }

        return static::write2menu($menus, $exists, $migrator);
    }

    /**
     * 升级更新数据表.
     * @param array $fields 字段配置
     * @param array $indexs 索引配置
     * @param bool $force 强制更新
     */
    public static function upgrade(Table $table, array $fields, array $indexs = [], bool $force = false): Table
    {
        [$_exists, $_fields] = [[], array_column($fields, 0)];
        if ($isExists = $table->exists()) {
            if (empty($force)) {
                return $table;
            }
            foreach ($table->getColumns() as $column) {
                $_exists[] = $name = $column->getName();
                if (!in_array($name, $_fields)) {
                    // @todo 为保证数据安全暂不删字段
                }
            }
        }
        foreach ($fields as $field) {
            if (in_array($field[0], $_exists)) {
                $table->changeColumn($field[0], ...array_slice($field, 1));
            } else {
                $table->addColumn($field[0], ...array_slice($field, 1));
            }
        }
        foreach ($indexs as $spec) {
            [$columns, $options] = self::parseIndexSpec($table->getName(), $spec);
            if (empty($columns) || (!empty($isExists) && $table->hasIndex($columns))) {
                continue;
            }
            $table->addIndex($columns, $options);
        }
        $isExists ? $table->update() : $table->create();
        if ($table->hasColumn('id')) {
            $table->changeColumn('id', 'integer', ['limit' => 11, 'identity' => true]);
        }
        return $table;
    }

    /**
     * 创建数据库安装脚本.
     * @return string[]
     * @throws \Exception
     */
    public static function create2table(array $tables = [], string $class = 'InstallTable', bool $force = false): array
    {
        if (Library::$sapp->db->connect()->getConfig('type') !== 'mysql') {
            throw new \Exception(' ** Notify: 只支持 MySql 数据库生成数据库脚本');
        }
        $br = "\r\n";
        $content = static::_build2table($tables, true, $force);
        $content = substr($content, strpos($content, "\n") + 1);
        $content = '<?php' . "{$br}{$br}use plugin\\helper\\support\\PhinxExtend;{$br}use think\\migration\\Migrator;{$br}{$br}@set_time_limit(0);{$br}@ini_set('memory_limit', '-1');{$br}{$br}class {$class} extends Migrator{$br}{{$br}{$content}}{$br}";
        return ['file' => static::nextFile($class), 'text' => $content];
    }

    /**
     * 创建数据库备份脚本.
     * @throws \Exception
     */
    public static function create2backup(array $tables = [], string $class = 'InstallPackage', bool $progress = true): array
    {
        if (Library::$sapp->db->connect()->getConfig('type') !== 'mysql') {
            throw new \Exception(' ** Notify: 只支持 MySql 数据库生成数据库脚本');
        }
        [$menuData, $menuList] = [[], SystemMenu::mk()->where(['status' => 1])->order('sort desc,id asc')->select()->toArray()];
        foreach (ArrayTree::arr2tree($menuList) as $sub1) {
            $one = ['name' => $sub1['title'], 'icon' => $sub1['icon'], 'url' => $sub1['url'], 'node' => $sub1['node'], 'params' => $sub1['params'], 'subs' => []];
            if (!empty($sub1['sub'])) {
                foreach ($sub1['sub'] as $sub2) {
                    $two = ['name' => $sub2['title'], 'icon' => $sub2['icon'], 'url' => $sub2['url'], 'node' => $sub2['node'], 'params' => $sub2['params'], 'subs' => []];
                    if (!empty($sub2['sub'])) {
                        foreach ($sub2['sub'] as $sub3) {
                            $two['subs'][] = ['name' => $sub3['title'], 'url' => $sub3['url'], 'node' => $sub3['node'], 'icon' => $sub3['icon'], 'params' => $sub3['params']];
                        }
                    }
                    if (empty($two['subs'])) {
                        unset($two['subs']);
                    }
                    $one['subs'][] = $two;
                }
            }
            if (empty($one['subs'])) {
                unset($one['subs']);
            }
            $menuData[] = $one;
        }

        [$extra, $version] = [[], strstr($filename = static::nextFile($class), '_', true)];
        if (count($tables) > 0) {
            foreach ($tables as $table) {
                if (($count = ($db = Library::$sapp->db->table($table))->count()) > 0) {
                    $dataFileName = "{$version}/{$table}.data";
                    $dataFilePath = syspath("database/migrations/{$dataFileName}");
                    is_dir($dataDirectory = dirname($dataFilePath)) || mkdir($dataDirectory, 0777, true);
                    $progress && ProcessService::message(" -- Starting write {$table}.data ..." . PHP_EOL);
                    [$used, $fp] = [0, fopen($dataFilePath, 'w+')];
                    foreach ($db->cursor() as $item) {
                        fwrite($fp, json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\r\n");
                        if ($progress && ($number = sprintf('%.4f', (++$used / $count) * 100) . '%')) {
                            ProcessService::message(" -- -- write {$table}.data: {$used}/{$count} {$number}", 1);
                        }
                    }
                    fclose($fp);
                    $extra[$table] = $dataFileName;
                    $progress && ProcessService::message(" -- Finished write {$table}.data, Total {$used} rows.", 2);
                }
            }
        }

        $template = file_get_contents(dirname(__DIR__) . '/service/bin/package.stub');
        $dataJson = json_encode($extra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $menuJson = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $replaces = ['__CLASS__' => $class, '__MENU_JSON__' => $menuJson, '__DATA_JSON__' => $dataJson];
        return ['file' => $filename, 'text' => str_replace(array_keys($replaces), array_values($replaces), $template)];
    }

    /**
     * 单个写入菜单.
     * @param array $menu 菜单数据
     * @param int $ppid 上级菜单
     */
    private static function write1menu(array $menu, int $ppid = 0): int
    {
        return (int) SystemMenu::mk()->insertGetId(static::normalizeMenuRow($menu, $ppid));
    }

    /**
     * 使用迁移连接批量写入菜单。
     * SQLite 首次建库时必须和建表共用同一连接，否则事务中的新表对 ORM 不可见。
     * @param array<int, array<string, mixed>> $zdata
     * @param array<string, mixed> $exists
     */
    private static function write2menuByMigrator(Migrator $migrator, array $zdata, array $exists = []): bool
    {
        if (!empty($exists) && static::migrationRowExists($migrator, 'system_menu', $exists)) {
            return false;
        }
        foreach ($zdata as $one) {
            $pid1 = static::write1menuByMigrator($migrator, $one);
            if (!empty($one['subs'])) {
                foreach ($one['subs'] as $two) {
                    $pid2 = static::write1menuByMigrator($migrator, $two, $pid1);
                    if (!empty($two['subs'])) {
                        foreach ($two['subs'] as $thr) {
                            static::write1menuByMigrator($migrator, $thr, $pid2);
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * 使用迁移连接写入单个菜单。
     * @param array<string, mixed> $menu
     */
    private static function write1menuByMigrator(Migrator $migrator, array $menu, int $ppid = 0): int
    {
        $data = static::normalizeMenuRow($menu, $ppid);
        if ($id = static::migrationRowId($migrator, 'system_menu', [
            'pid' => $data['pid'],
            'title' => $data['title'],
            'node' => $data['node'],
            'url' => $data['url'],
        ])) {
            return $id;
        }

        $migrator->table('system_menu')->insert([$data])->saveData();
        return static::migrationRowId($migrator, 'system_menu', [
            'pid' => $data['pid'],
            'title' => $data['title'],
            'node' => $data['node'],
            'url' => $data['url'],
        ]);
    }

    /**
     * 标准化菜单写入字段。
     * @param array<string, mixed> $menu
     * @return array<string, mixed>
     */
    private static function normalizeMenuRow(array $menu, int $ppid = 0): array
    {
        return [
            'pid' => $ppid,
            'url' => empty($menu['url']) ? (empty($menu['node']) ? '#' : $menu['node']) : $menu['url'],
            'sort' => $menu['sort'] ?? 0,
            'icon' => $menu['icon'] ?? '',
            'node' => empty($menu['node']) ? (empty($menu['url']) ? '' : $menu['url']) : $menu['node'],
            'title' => $menu['name'] ?? ($menu['title'] ?? ''),
            'params' => $menu['params'] ?? '',
            'target' => $menu['target'] ?? '_self',
        ];
    }

    /**
     * 检查迁移连接中的数据是否存在。
     * @param array<string, mixed> $where
     */
    public static function migrationRowExists(Migrator $migrator, string $table, array $where = []): bool
    {
        return static::migrationRowId($migrator, $table, $where, false) > 0;
    }

    /**
     * 获取迁移连接中的记录编号。
     * @param array<string, mixed> $where
     */
    public static function migrationRowId(Migrator $migrator, string $table, array $where = [], bool $selectId = true): int
    {
        [$sql, $params] = static::buildMigrationWhereSql($migrator, $table, $where);
        $field = $selectId ? $migrator->getAdapter()->quoteColumnName('id') : '1';
        $stmt = $migrator->query("SELECT {$field} FROM {$sql} LIMIT 1", $params);
        if ($stmt === false) {
            return 0;
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($row === false) {
            return 0;
        }

        if (!$selectId) {
            return 1;
        }

        return intval($row['id'] ?? array_values($row)[0] ?? 0);
    }

    /**
     * 构建迁移查询的 where 片段。
     * @param array<string, mixed> $where
     * @return array{0:string,1:array<int, mixed>}
     */
    private static function buildMigrationWhereSql(Migrator $migrator, string $table, array $where = []): array
    {
        $sql = $migrator->getAdapter()->quoteTableName($table);
        if (empty($where)) {
            return [$sql, []];
        }

        $params = [];
        $clauses = [];
        foreach ($where as $column => $value) {
            $columns = array_values(array_filter(array_map('trim', explode('|', strval($column)))));
            if (empty($columns)) {
                continue;
            }

            if (count($columns) === 1) {
                $quoted = $migrator->getAdapter()->quoteColumnName($columns[0]);
                if ($value === null) {
                    $clauses[] = "{$quoted} IS NULL";
                } else {
                    $clauses[] = "{$quoted} = ?";
                    $params[] = $value;
                }
                continue;
            }

            $or = [];
            foreach ($columns as $name) {
                $quoted = $migrator->getAdapter()->quoteColumnName($name);
                if ($value === null) {
                    $or[] = "{$quoted} IS NULL";
                } else {
                    $or[] = "{$quoted} = ?";
                    $params[] = $value;
                }
            }
            $clauses[] = '(' . join(' OR ', $or) . ')';
        }

        return ["{$sql} WHERE " . join(' AND ', $clauses), $params];
    }

    /**
     * 生成索引名称.
     */
    private static function genIndexName(string $table, string|array $name, bool $unique = false): string
    {
        $getInitials = function (string $str): string {
            return implode('', array_map(function ($part) {
                return $part[0] ?? '';
            }, explode('_', $str)));
        };
        $suffix = is_array($name) ? implode('_', $name) : $name;
        $prefix = $unique ? 'uni' : 'idx';
        return sprintf('%s_%s_%s_%s', $prefix, substr(md5($table), -4), $getInitials($table), $suffix);
    }

    /**
     * @return array{0:array<int, string>,1:array<string, mixed>}
     */
    private static function parseIndexSpec(string $table, mixed $spec): array
    {
        if (is_string($spec)) {
            $columns = [$spec];
            return [$columns, ['name' => self::genIndexName($table, $columns)]];
        }

        if (is_array($spec) && array_is_list($spec)) {
            $columns = array_values(array_filter($spec, 'is_string'));
            return [$columns, ['name' => self::genIndexName($table, $columns)]];
        }

        if (is_array($spec)) {
            $columns = array_values(array_filter((array) ($spec['columns'] ?? []), 'is_string'));
            $unique = !empty($spec['unique']);
            $options = array_diff_key($spec, ['columns' => true]);
            $options['name'] = $options['name'] ?? self::genIndexName($table, $columns, $unique);
            return [$columns, $options];
        }

        return [[], []];
    }

    /**
     * 数组转代码
     */
    private static function _arr2str(array $data): string
    {
        return preg_replace(['#\s+#', '#, \)$#', '#^array \( #'], [' ', ']', '['], var_export($data, true));
    }

    /**
     * 生成数据库表格创建模板
     * @param array $tables 指定数据表
     * @param bool $rehtml 是否返回内容
     * @param bool $force 强制更新结构
     * @throws \Exception
     */
    private static function _build2table(array $tables = [], bool $rehtml = false, bool $force = false): string
    {
        $br = "\r\n";
        $connect = Library::$sapp->db->connect();
        if ($connect->getConfig('type') !== 'mysql') {
            throw new \Exception(' ** Notify: 只支持 MySql 数据库生成数据库脚本');
        }
        $schema = $connect->getConfig('database');
        $content = '<?php' . "{$br}{$br}\t/**{$br}\t * 创建数据库{$br}\t */{$br}\tpublic function change()\n\t{";
        foreach ($tables as $table) {
            $content .= "{$br}\t\t\$this->_create_{$table}();";
        }
        $content .= "{$br}\t}{$br}{$br}";

        $sizes = ['tinyint' => 4, 'smallint' => 6, 'mediumint' => 9, 'int' => 11, 'bigint' => 20];
        $types = [
            'tinyint' => AdapterInterface::PHINX_TYPE_TINY_INTEGER,
            'smallint' => AdapterInterface::PHINX_TYPE_SMALL_INTEGER,
            'int' => AdapterInterface::PHINX_TYPE_INTEGER,
            'bigint' => AdapterInterface::PHINX_TYPE_BIG_INTEGER,
            'varchar' => AdapterInterface::PHINX_TYPE_STRING,
            'tinytext' => AdapterInterface::PHINX_TYPE_TEXT,
            'mediumtext' => AdapterInterface::PHINX_TYPE_TEXT,
            'longtext' => AdapterInterface::PHINX_TYPE_TEXT,
            'set' => AdapterInterface::PHINX_TYPE_STRING,
            'enum' => AdapterInterface::PHINX_TYPE_STRING,
            'year' => AdapterInterface::PHINX_TYPE_INTEGER,
            'mediumint' => AdapterInterface::PHINX_TYPE_INTEGER,
            'tinyblob' => AdapterInterface::PHINX_TYPE_BLOB,
            'longblob' => AdapterInterface::PHINX_TYPE_BLOB,
            'mediumblob' => AdapterInterface::PHINX_TYPE_BLOB,
        ];

        foreach ($tables as $table) {
            $comment = Library::$sapp->db->table('information_schema.TABLES')->where([
                'TABLE_SCHEMA' => $schema, 'TABLE_NAME' => $table,
            ])->value('TABLE_COMMENT', '');

            $class = Str::studly($table);
            $content .= <<<CODE
    /**
     * 创建数据对象
     * @class {$class}
     * @table {$table}
     * @return void
     */
    private function _create_{$table}() 
    {
        // 创建数据表对象
        \$table = \$this->table('{$table}', [
            'engine' => 'InnoDB', 'collation' => 'utf8mb4_general_ci', 'comment' => '{$comment}',
        ]);
        // 创建或更新数据表
        PhinxExtend::upgrade(\$table, _FIELDS_, _INDEXS_, __FORCE__);
    }
CODE;
            $_fieldString = '[' . PHP_EOL;
            foreach (Library::$sapp->db->getFields($table) as $field) {
                if ($field['name'] === 'id') {
                    continue;
                }
                $type = $types[$field['type']] ?? $field['type'];
                $data = ['default' => $field['default'], 'null' => empty($field['notnull']), 'comment' => $field['comment'] ?? ''];
                if ($field['type'] === 'longtext') {
                    $data = array_merge(['limit' => MysqlAdapter::TEXT_LONG], $data);
                } elseif ($field['type'] === 'enum') {
                    $type = $types[$field['type']] ?? 'string';
                    $data = array_merge(['limit' => 10], $data);
                } elseif (preg_match('/(tinyblob|blob|mediumblob|longblob|varbinary|bit|binary|varchar|char)\((\d+)\)/', $field['type'], $attr)) {
                    $type = $types[$attr[1]] ?? 'string';
                    $data = array_merge(['limit' => intval($attr[2])], $data);
                } elseif (preg_match('/(tinyint|smallint|mediumint|int|bigint)\((\d+)\)/', $field['type'], $attr)) {
                    $type = $types[$attr[1]] ?? 'integer';
                    $data = array_merge(['limit' => intval($attr[2])], $data, ['default' => intval($data['default'])]);
                } elseif (preg_match('/(tinyint|smallint|mediumint|int|bigint)\s+unsigned/i', $field['type'], $attr)) {
                    $type = $types[$attr[1]] ?? 'integer';
                    if (isset($sizes[$attr[1]])) {
                        $data = array_merge(['limit' => $sizes[$attr[1]]], $data);
                    }
                    $data['default'] = intval($data['default']);
                } elseif (preg_match('/(float|decimal)\((\d+),(\d+)\)/', $field['type'], $attr)) {
                    $type = $types[$attr[1]] ?? 'decimal';
                    $data = array_merge(['precision' => intval($attr[2]), 'scale' => intval($attr[3])], $data);
                }
                $_fieldString .= "\t\t\t['{$field['name']}', '{$type}', " . self::_arr2str($data) . '],' . PHP_EOL;
            }
            $_fieldString .= "\t\t]";
            $_indexs = [];
            foreach (Library::$sapp->db->connect()->query("show index from {$table}") as $index) {
                $index['Key_name'] !== 'PRIMARY' && $_indexs[] = $index['Column_name'];
            }
            usort($_indexs, function ($a, $b) {
                return strlen($a) <=> strlen($b);
            });
            $_indexString = '[' . PHP_EOL . "\t\t\t";
            foreach ($_indexs as $index) {
                $_indexString .= "'{$index}', ";
            }
            $_indexString .= PHP_EOL . "\t\t]";
            $content = str_replace(['_FIELDS_', '_INDEXS_', '__FORCE__'], [$_fieldString, $_indexString, $force ? 'true' : 'false'], $content) . PHP_EOL . PHP_EOL;
        }
        return $rehtml ? $content : highlight_string($content, true);
    }

    /**
     * 生成下一个脚本名称.
     * @param string $class 脚本类名
     */
    private static function nextFile(string $class): string
    {
        [$snake, $items] = [Str::snake($class), [20010000000000]];
        FileTools::find(syspath('database/migrations'), 1, function (\SplFileInfo $info) use ($snake, &$items) {
            if ($info->isFile()) {
                $bname = pathinfo($info->getBasename(), PATHINFO_FILENAME);
                $items[] = $version = intval(substr($bname, 0, 14));
                if ($snake === substr($bname, 15) && unlink($info->getRealPath())) {
                    if (is_dir($dataPath = $info->getPath() . DIRECTORY_SEPARATOR . $version)) {
                        FileTools::remove($dataPath);
                    }
                }
            }
        });

        return sprintf("%s_{$snake}.php", min($items) - 1);
    }
}
