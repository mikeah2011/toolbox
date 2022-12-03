### 開發工具箱 之 DBBuilder

>   DBBuilder - DB類查詢構造器
>
>   **以數組方式重構 DB - CURD**

項目維護久了，很多地方都會佈滿->where()等，如：

![image-20221203172047321](https://cdn.jsdelivr.net/gh/mikeah2011/oss@main/uPic/image-20221203172047321.png)

![image-20221203172336469](https://cdn.jsdelivr.net/gh/mikeah2011/oss@main/uPic/image-20221203172336469.png)



如果換成如下的數組方式：

![image-20221203173648759](https://cdn.jsdelivr.net/gh/mikeah2011/oss@main/uPic/image-20221203173648759.png)

```php
// 查詢一群租戶的指定字段，排序創建時間倒序
$filter = [
    'mid'         => $customerTid,
    'jing_uuid'   => $omniUserUuids,
    'orderBy'     => [
        'created_timestamp' => 'DESC',
    ],
    'queryResult' => [
        'value' => $columns,
    ],
];
$omniUserLists = DBBuilder::queryBuilder($filter, DB::connection()->table('tableName'));
```

刪除

```php
orm_filter([
    'mid' => $customerTid,
    'omni_user_uuid' => $omniUserUuids, 
    'queryResult' => [
        'type' => 'delete'
    ]
], DB::connection('omni_mysql')->table('omni_leads_phase_calculate_record'));
```

更新

```php
orm_filter([
    'mid' => $customerTid,
    'jing_uuid' => $item['jing_uuid'], 
    'queryResult' => [
        'type' => 'update', 
        'value' => $updateDataTmp
    ]
], $omniRepository->getQuery($customerTid));
```

聚合

```php
orm_filter($filter + [
    'queryResult' => ['type' => 'count', 'value' => ['jing_uuid']]
], $smsMysqlConnection->table('sms_history'));
```

