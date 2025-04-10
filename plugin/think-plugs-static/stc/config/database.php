<?php

// +----------------------------------------------------------------------
// | Static Plugin for ThinkAdmin
// +----------------------------------------------------------------------
// | 版权所有 2014~2025 ThinkAdmin [ thinkadmin.top ]
// +----------------------------------------------------------------------
// | 官方网站: https://thinkadmin.top
// +----------------------------------------------------------------------
// | 开源协议 ( https://mit-license.org )
// | 免责声明 ( https://thinkadmin.top/disclaimer )
// +----------------------------------------------------------------------
// | gitee 代码仓库：https://gitee.com/zoujingli/think-plugs-static
// | github 代码仓库：https://github.com/zoujingli/think-plugs-static
// +----------------------------------------------------------------------

return [
    // 默认使用的数据库连接配置
    'default'         => env('DB_TYPE', 'sqlite'),
    // 自定义时间查询规则
    'time_query_rule' => [],
    // 自动写入时间戳字段
    'auto_timestamp'  => true,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 数据库连接配置信息
    'connections'     => [
        'mysql'  => [
            // 数据库类型
            'type'            => 'mysql',
            // 服务器地址
            'hostname'        => env('DB_MYSQL_HOST', '127.0.0.1'),
            // 服务器端口
            'hostport'        => env('DB_MYSQL_PORT', '3306'),
            // 数据库名
            'database'        => env('DB_MYSQL_DATABASE', 'thinkadmin'),
            // 用户名
            'username'        => env('DB_MYSQL_USERNAME', 'root'),
            // 密码
            'password'        => env('DB_MYSQL_PASSWORD', ''),
            // 数据库连接参数
            'params'          => [],
            // 数据库表前缀
            'prefix'          => env('DB_MYSQL_PREFIX', ''),
            // 数据库编码默认采用 utf8mb4
            'charset'         => env('DB_MYSQL_CHARSET', 'utf8mb4'),
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => false,
            // 监听SQL执行日志
            'trigger_sql'     => true,
            // 开启字段类型缓存
            'fields_cache'    => isOnline(),
        ],
        'sqlite' => [
            // 数据库类型
            'type'        => 'sqlite',
            // 数据库文件
            'database'    => syspath('database/sqlite.db'),
            // 数据库编码默认采用 utf8
            'charset'     => 'utf8',
            // 监听执行日志
            'trigger_sql' => true,
            // 其他参数字段
            'deploy'      => 0,
            'suffix'      => '',
            'prefix'      => '',
            'hostname'    => '',
            'hostport'    => '',
            'username'    => '',
            'password'    => '',
        ],
    ],
];
