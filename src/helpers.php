<?php

use Illuminate\Support\Str;

if (!function_exists('camel_snake')) {
    /**
     * @description 对数组的key进行蛇形&驼峰互相转换
     *
     * @param array|null $array       数组数据源
     * @param string     $caseType    枚举支持的类型：
     *                                缺省为空''   - 自动转换；
     *                                camel_case  - 转成小驼峰；
     *                                snake_case  - 转成蛇形
     * @param array      $mapping     映射字段集合
     *                                為了解決[數據庫字段]與[入參字段]的對應關係
     *                                規則為：['數據庫字段column' => '入參paramKey']
     *                                如：['settle_oid' => 'oid'];
     *
     * @return array|null
     */
    function camel_snake(?array &$array, string $caseType = '', array $mapping = []): ?array
    {
        $defaultCaseType = $caseType;
        foreach ((array)$array as $k => $v) {
            $tmpK = $mapping[$k] ?? $k;
            is_array($v) && camel_snake($v, $defaultCaseType, $mapping);
            unset($array[$k], $array[$tmpK]);
            $k !== $tmpK && $k = $tmpK;
            // 没有默認的caseType就自动识别是否有 - 或者 _
            empty($defaultCaseType) && $caseType = Str::contains($k, ['-', '_']) ? 'camel_case' : 'snake_case';
            $array[$caseType($k)] = $v;
        }

        return $array;
    }
}

if (!function_exists('')) {
    /**
     * @description 獲取符合swagger的數據類型
     *
     * @param $value
     *
     * @return string
     */
    function get_type($value): string
    {
        $type = gettype($value);
        $type === 'array' && !isset($value[0]) && $type = 'object';

        return $type === 'NULL' ? 'string' : $type;
    }
}