### 開發工具箱

1. 本地開發環境安裝依賴包
     ```php
     composer require --dev michaelma/toolbox
     ```

2.   請在 `Controller` 類中對應的方法 `return` 前，加入如下代碼：

     ```php
     \Toolbox\Facades\SwaggerNotes::setNotesPath(__NAMESPACE__)
         ->setOperationId(__FUNCTION__)
         ->setTags(__CLASS__)
         ->setSummary('查詢大聯盟會員資料')
         ->setDescription('含是否填寫個人資料、賬戶資料')
         ->setComments(['affilliate_web', 'affilliate', 'member'], $request->rules($this->affiliateService))
         ->setResponse($this->jsonRender(TransformHelper::camelSnakeCase($result, 'camel_case')))
         ->setRequest($request)
         ->generate();
     ```
     
     或「看你喜歡咯…」
     
     ```php
     \Toolbox\Facades\SwaggerNotes::call(
         $request,
         $this->jsonRender(TransformHelper::camelSnakeCase($result, 'camel_case')),
         ['affilliate_web', 'affilliate', 'member'],
         $request->rules($this->affiliateService),
         __NAMESPACE__,
         __FUNCTION__,
         __CLASS__,
         '查詢大聯盟會員資料',
         '含是否填寫個人資料、賬戶資料'
     );
     ```
     
     甚至「你也可以這樣」
     
     ```php
     \Toolbox\Facades\SwaggerNotes::generateNotes([
         'api_info'     => [
             'tags'         => __CLASS__,
             'summary'      => '查詢大聯盟會員資料',
             'description'  => '含是否填寫個人資料、賬戶資料',
             'operation_id' => __FUNCTION__,
             'tables'       => ['affilliate_web', 'affilliate', 'member'],
             'rules'        => $request->rules($this->affiliateService),
         ],
         'request'      => $request,
         'response'     => $this->jsonRender(TransformHelper::camelSnakeCase($result, 'camel_case')),
         'swagger_path' => [__NAMESPACE__, __FUNCTION__],
     ]);
     ```



​			上述代碼雖然`CV`就可以，但還是需要改一改的，具體修改釋義，可以參考附表。



3.   附表

     | 字段                  | 類型     | 必含 | 自定義 | 釋義作用                                     |
     | --------------------- | -------- | :--: | :----: | -------------------------------------------- |
     | api_info              | `array`  |  ✅   |   ❌    | 接口信息集合                                 |
     | api_info.tags         | `string` |  ✅   |   ❌    | 接口文檔的`tags`標籤                         |
     | api_info.summary      | `string` |  ❌   |   ✔️    | 接口文檔的`summary`概述匯總                  |
     | api_info.description  | `string` |  ❌   |   ✔️    | 接口文檔的`description`簡要描述              |
     | api_info.operation_id | `string` |  ✅   |   ❌    | 接口執行的`operation_id`                     |
     | api_info.tables       | `array`  |  ✅   |   ✔️    | 出入參涉及到的表 = 解析表字段備註            |
     | api_info.rules        | `array`  |  ✅   |   ✔️    | 入參校驗規則 = 解析是否必填                  |
     | request               | `object` |  ✅   |   ✔️    | 請求類為了獲取`method`、`params`、`pathInfo` |
     | response              | `object` |  ✅   |   ✔️    | 實體類                                       |
     | swagger_path          | `array`  |  ✅   |   ❌    | 命名空間、方法，定義生成文檔的路徑           |
