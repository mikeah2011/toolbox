### 開發工具箱 之 `SwaggerNotes`生成工具



>   註：該工具僅生成注釋內容，依賴`swagger-php`包才能生成`.yaml`接口文件



1. 本地開發環境安裝依賴包

     ```php
     composer require --dev michael-ma/toolbox
     ```



2.   請在 `Controller` 類中對應的方法 `return` 前，加入如下代碼：

     ```php
     \Toolbox\Facades\SwaggerNotes::setRequest($request)
         ->setResponse($this->jsonRender(TransformHelper::camelSnakeCase($result, 'camel_case')))
         ->setComments(['affilliate_web', 'affilliate', 'member'], $request->rules($this->affiliateService))
         ->setSummary('查詢大聯盟會員資料')
         ->setDescription('含是否填寫個人資料、賬戶資料')
         ->setOperationId(__FUNCTION__)
         ->setTags(__CLASS__)
         ->generate();
     ```



3.   默認生成路徑在`swagger/SwaggerNotes`目錄下，層級結構如下：

     ```shell
     swagger
     ├── SwaggerNotes					# 生成的注釋目錄
     │   ├── AffiliateTransfer		  # 生成的接口目錄
     │   │   └── affiliateView.php	 # 生成的接口注釋文件
     │   └── swagger.php				   # 生成的注釋頭部信息文件
     ├── swagger-constants.php
     ├── swagger-info.php
     ├── swagger.yaml				 
     └── swagger_doc.yaml 			   # 生成的接口文件
     ```

     

3.   附表

     | 方法              | 入參類型 | 自定義否 | 釋義作用                                  | 缺省            |
     | ----------------- | :------: | :------: | ----------------------------------------- | --------------- |
     | `setRequest`[^1]  | `object` |    ❌     | 請求類 解析`method`、`params`、`pathInfo` | `Request`       |
     | `setResponse`     | `object` |    ✔️     | 返回數據，可直接複製`return`後的代碼      | `Response`      |
     | `setComments`[^2] | `array`  |    ✔️     | 出入參涉及到的表，入參校驗規則            | `[]`，`rules()` |
     | `setSummary`[^3]  | `string` |    ✔️     | 接口文檔的`summary`概述匯總               | `name()`        |
     | `setDescription`  | `string` |    ✔️     | 接口文檔的`description`簡要描述           | `空`            |
     | `setOperationId`  | `string` |    ❌     | 接口文檔的`operation_id`                  | `__FUNCTION__`  |
     | `setTags`         | `string` |    ❌     | 接口文檔的`tags`標籤                      | `__CLASS__`     |





[^1]: 如果有任何擴展或者依賴，請自行調整入參為原生`Request`類
[^2]:如果沒任何擴展或者依賴，可以不傳`$rules`，腳本會讀`rules()`方法
[^3]:如果在路由中定義`->name()`，可以不傳`$summary`，腳本會讀`name`內容

```php
#...
Route::get('kkpartner/view', [AffiliateTransferController::class, 'affiliateView'])->name('查詢大聯盟會員資料');
#...
```

