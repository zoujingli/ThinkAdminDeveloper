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

namespace plugin\helper\migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use plugin\helper\plugin\PluginRegistry;
use think\App;
use think\console\Output;

final class MigrationExporter
{
    public function __construct(
        private readonly App $app,
        private readonly Output $output,
    ) {}

    /**
     * @return array<string, array{file:string, tables:array<int, string>}>
     */
    public function export(array $plugins = [], array $tables = []): array
    {
        $results = [];
        $schemaManager = $this->makeConnection()->createSchemaManager();
        $available = array_values(array_filter($schemaManager->listTableNames(), static function (string $table) use ($tables) {
            return $table !== 'migrations' && (empty($tables) || in_array($table, $tables, true));
        }));

        foreach (PluginRegistry::selected($plugins) as $plugin => $config) {
            $matched = array_values(array_filter($available, static fn (string $table) => PluginRegistry::matchPlugin($table) === $plugin));
            if (empty($matched) && empty($config['export_empty'])) {
                continue;
            }

            $content = $this->buildMigration($config['class'], $config['name'], $matched, $schemaManager);
            $target = $this->app->getRootPath() . $config['target'] . DIRECTORY_SEPARATOR . $config['file'];
            is_dir(dirname($target)) || mkdir(dirname($target), 0777, true);
            file_put_contents($target, $content);

            $results[$plugin] = ['file' => $target, 'tables' => $matched];
            $this->output->writeln(sprintf('Exported %s -> %s', $plugin, $config['file']));
        }

        return $results;
    }

    private function buildMigration(string $class, string $name, array $tables, object $schemaManager): string
    {
        $br = PHP_EOL;
        $methods = [];
        $changes = [];

        foreach ($tables as $tableName) {
            /** @var Table $table */
            $table = $schemaManager->introspectTable($tableName);
            [$fields, $indexes] = $this->normalizeTable($table);
            $method = '_create_' . $tableName;
            $methods[] = "        \$this->{$method}();";
            $changes[] = <<<PHP
    private function {$method}(): void
    {
        \$table = PhinxExtend::table(\$this, '{$tableName}');
        PhinxExtend::upgrade(\$table, {$fields}, {$indexes}, true);
    }
PHP;
        }

        $changeBody = empty($methods) ? '        // no tables' : implode($br, $methods);
        $methodBody = empty($changes) ? '' : $br . implode($br . $br, $changes) . $br;

        return <<<PHP
<?php

declare(strict_types=1);

use plugin\\helper\\migration\\PhinxExtend;
use think\\migration\\Migrator;

@set_time_limit(0);
@ini_set('memory_limit', '-1');

class {$class} extends Migrator
{
    public function getName(): string
    {
        return '{$name}';
    }

    public function change(): void
    {
{$changeBody}
    }
{$methodBody}}
PHP;
    }

    /**
     * @return array{string, string}
     */
    private function normalizeTable(Table $table): array
    {
        $columns = [];
        $columnMap = [];

        foreach ($table->getColumns() as $column) {
            $name = $column->getName();
            if ($name === 'id') {
                continue;
            }

            $target = $name;

            $columnMap[$name] = $target;
            $columns[$target] = $this->exportColumn($target, $column);
        }

        $fields = '[' . PHP_EOL . implode(PHP_EOL, array_values($columns)) . PHP_EOL . '        ]';
        $indexes = $this->exportIndexes($table, $columnMap);

        return [$fields, $indexes];
    }

    private function exportColumn(string $name, Column $column): string
    {
        $type = $this->mapType($column);
        $options = [
            'default' => $this->normalizeDefault($column->getDefault()),
            'null' => !$column->getNotnull(),
            'comment' => $column->getComment(),
        ];

        if (($limit = $column->getLength()) !== null && !in_array($type, ['datetime', 'date', 'time', 'text', 'json', 'blob'], true)) {
            $options['limit'] = $limit;
        }

        if (($precision = $column->getPrecision()) !== null && $precision > 0) {
            $options['precision'] = $precision;
            $options['scale'] = $column->getScale();
        }

        if ($column->getUnsigned()) {
            $options['signed'] = false;
        }

        return sprintf(
            "            ['%s', '%s', %s],",
            $name,
            $type,
            $this->exportArray($options)
        );
    }

    private function mapType(Column $column): string
    {
        $name = Type::lookupName($column->getType());

        return match ($name) {
            Types::BIGINT => 'biginteger',
            Types::SMALLINT => 'smallinteger',
            Types::INTEGER => 'integer',
            Types::BOOLEAN => 'boolean',
            Types::FLOAT => 'float',
            Types::DECIMAL => 'decimal',
            Types::DATE_MUTABLE, Types::DATE_IMMUTABLE => 'date',
            Types::TIME_MUTABLE, Types::TIME_IMMUTABLE => 'time',
            Types::DATETIME_MUTABLE, Types::DATETIME_IMMUTABLE, Types::DATETIMETZ_MUTABLE, Types::DATETIMETZ_IMMUTABLE => 'datetime',
            Types::TEXT => 'text',
            Types::BLOB, Types::BINARY => 'blob',
            Types::JSON => 'json',
            default => 'string',
        };
    }

    private function normalizeDefault(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (is_string($value) && strtoupper($value) === 'NULL') {
            return null;
        }

        return $value;
    }

    private function exportIndexes(Table $table, array $columnMap): string
    {
        $items = [];

        foreach ($table->getIndexes() as $index) {
            if ($index->isPrimary()) {
                continue;
            }

            $columns = [];
            foreach ($index->getColumns() as $column) {
                if (isset($columnMap[$column])) {
                    $columns[] = $columnMap[$column];
                }
            }

            $columns = array_values(array_unique($columns));
            if (empty($columns)) {
                continue;
            }

            $items[] = $this->exportIndex($columns, $index);
        }

        return '[' . PHP_EOL . implode(PHP_EOL, $items) . PHP_EOL . '        ]';
    }

    private function exportIndex(array $columns, Index $index): string
    {
        $payload = [
            'columns' => $columns,
        ];

        if ($index->isUnique()) {
            $payload['unique'] = true;
        }

        return '            ' . $this->exportArray($payload) . ',';
    }

    private function exportArray(array $data): string
    {
        $items = [];
        foreach ($data as $key => $value) {
            $items[] = var_export($key, true) . ' => ' . $this->exportValue($value);
        }

        return '[' . implode(', ', $items) . ']';
    }

    private function exportValue(mixed $value): string
    {
        if (is_array($value)) {
            $items = array_map(fn ($item) => $this->exportValue($item), $value);
            return '[' . implode(', ', $items) . ']';
        }

        return var_export($value, true);
    }

    private function makeConnection(): Connection
    {
        $config = $this->app->db->connect()->getConfig();
        $config['host'] = $config['hostname'] ?? '';
        $config['user'] = $config['username'] ?? '';
        $config['dbname'] = $config['database'] ?? '';
        if (in_array($config['type'], ['mysql', 'sqlite', 'oci'], true)) {
            $config['driver'] = 'pdo_' . $config['type'];
        }

        return DriverManager::getConnection($config);
    }
}
