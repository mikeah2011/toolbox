<?php


namespace Toolbox;
/**
 *
 * DB数据库类的助手函数
 *
 */
class DBBuilder
{
    /**
     * @description 查看指定DBName库的tableName表中columnName字段的指定字段信息
     *
     * @param array $tableNames 支持數組
     * @param array $columns
     *
     * @return array
     */
    public function getColumnsInfo(array $tableNames, array $columns = []): array
    {
        $relNames = "'" . implode("','", $tableNames) . "'";
        $where = $columns ? " AND a.attname IN ('" . implode("', '", $columns) . "')" : "";
        $sql = <<<EOF
SELECT COL_DESCRIPTION(a.attrelid, a.attnum) AS comment, t.typname AS typename, a.attname AS name, a.attnotnull AS "notnull"
FROM pg_class AS c,
     pg_attribute AS a
         INNER JOIN pg_type AS t ON t.oid = a.atttypid
WHERE c.relname IN ({$relNames})
  AND a.attrelid = c.oid
{$where}
  AND a.attnum > 0;
EOF;

        return \DB::select($sql);
    }

    /**
     * @description 根据filter筛选条件重建ORM-CURD操作
     *
     * @param array $filter       key由[column|operator]组成，
     *                            支持vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php:179中的$operators
     * @param null  $queryBuilder 查询构造器
     *
     * @return mixed
     * @example
     * $filter = [
     *      'field'     => $value,
     *      'field|in'  => $valueArray,
     *      'field|eq'  => $value,
     *      'field|neq' => $notValue,
     *      ...
     *      'page'      => 1,
     *      'pageSize'  => 20,
     *      'orderBy'   => [
     *                  'created_timestamp' => 'DESC',
     *               ],
     *      'groupBy'   => ['field1','field2', ...]
     *      'having'    => [
     *                  'field1|lt' => $value1,
     *                  'field2|gt' => $value2,
     *                  ...
     *               ],
     *      'queryResult' => [
     *                  'type' => '', // insert | delete | update | get
     *                  'value'=> [], // columns | data
     *               ],
     *  ];
     */
    public function queryBuilder(array $filter, $queryBuilder)
    {
        // 关键字 page pageSize orderBy groupBy having
        $page = $filter['page'] ?? 0;
        $pageSize = $filter['pageSize'] ?? 0;
        $orderBy = $filter['orderBy'] ?? [];
        $groupBy = $filter['groupBy'] ?? [];
        $having = $filter['having'] ?? [];
        $queryResult = $filter['queryResult'] ?? [];
        $method = $queryResult['type'] ?? 'get';
        $data = $queryResult['value'] ?? NULL;
        // 过滤关键字
        unset($filter['page'], $filter['pageSize'], $filter['orderBy'], $filter['groupBy'], $filter['having'], $filter['queryResult']);
        $queryBuilder = $this->filterBuilder($filter, $queryBuilder);
        // 排序
        foreach ($orderBy as $column => $desc) {
            $queryBuilder = $queryBuilder->orderBy($column, $desc);
        }
        // 分组
        foreach ($groupBy as $column) {
            $queryBuilder = $queryBuilder->groupBy($column);
        }
        // 聚合
        $customOperators = [
            'neq' => '!=',
            'lt' => '<',
            'lte' => '<=',
            'gt' => '>',
            'gte' => '>=',
        ];
        foreach ($having as $field => $value) {
            // 解析field字段
            $explode = explode('|', $field);
            $column = reset($explode);
            $operate = end($explode);
            $operator = count($explode) > 1 ? $customOperators[$operate] ?? $operate : '>';
            $queryBuilder = $queryBuilder->having($column, $operator, $value);
        }
        // 分页
        $page && $pageSize && $queryBuilder = $queryBuilder->offset(($page - 1) * $pageSize);
        // 条目
        $pageSize && $queryBuilder = $queryBuilder->limit($pageSize);
        // 如果需要输出SQL，请加入 putenv('DEBUG_SQL=true')，调试完毕后请删除;
        if (app()->runningInConsole() || env('DEBUG_SQL', false)) {
            echo PHP_EOL;
            dump(str_replace_array('?', $queryBuilder->getBindings(), $queryBuilder->toSql()));
        }
        // 软删除字段是否存在
        if ($method === 'delete' && str_contains(str_replace_array('?', $queryBuilder->getBindings(), $queryBuilder->toSql()), 'deleted_at')) {
            $method = 'update';
            $data = ['deleted_at' => date('Y-m-d H:i:s')];
        }
        // CURD
        $queryBuilder = $queryBuilder->{$method}($data);

        return method_exists($queryBuilder, 'toArray') ? $queryBuilder->toArray() : $queryBuilder;
    }

    /**
     * @description 根据filter筛选条件重建查询构造器
     *
     * @param array $filter       key由[column|operator]组成
     *                            支持vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php:179中的$operators
     * @param null  $queryBuilder 查询构造器
     *
     * @return mixed
     * @example
     * $filter = [
     *      'field'     => $value,
     *      'field|in'  => $valueArray,
     *      'field|eq'  => $value,
     *      'field|neq' => $notValue,
     *      ...
     *  ];
     *
     */
    public function filterBuilder(array $filter, $queryBuilder)
    {
        // 内置的驼峰where - 枚举值
        $studlyCase = [
            'in',
            'not_in',

            'null',
            'not_null',

            'between',
            'not_between',

            'day',
            'date',
            'month',
            'year',
            'time',

            'raw',
            'column',
        ];
        // 自定义书写的操作转换
        $customOperators = [
            'eq' => '=',
            'neq' => '!=',
            'lt' => '<',
            'lte' => '<=',
            'gt' => '>',
            'gte' => '>=',
        ];
        foreach ($filter as $field => $value) {
            // 解析field字段
            $explode = explode('|', $field);
            $column = array_first($explode);
            $operate = array_last($explode);
            // 操作符，默认=
            $operator = count($explode) > 1 ? $customOperators[$operate] ?? $operate : '=';
            // 非区间值数组，且字段不含' '字符，操作符为IN
            !str_contains($operator, 'between') && !str_contains($column, ' ') && is_array($value) && $operator = 'in';

            $where = 'where';
            $params = [$column, $operator, $value];

            // 存在驼峰where即转换 whereStudlyCase，仅需要column和value即可
            if (in_array($operator, $studlyCase, true)) {
                $where .= studly_case($operator);
                $params = [$column];
                // 非null操作带有value
                !str_contains($operator, 'null') && $params[] = $value;
            }

            $queryBuilder = $queryBuilder->{$where}(...$params);
        }

        return $queryBuilder;
    }
}
