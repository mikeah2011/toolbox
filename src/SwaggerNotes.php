<?php

namespace Toolbox;

use OpenApi\Generator;
use Toolbox\Facades\DBBuilder;

class SwaggerNotes
{
    /**
     * @var object 請求體，解析出 method pathInfo validated 等
     */
    private $request;
    /**
     * @var array   返回数据结构
     */
    private $response;
    /**
     * @var array   入參、出參及其規則字段對應的備註信息和必填
     */
    private $comments;

    /**
     * @var string  SwaggerPHP注釋的接口概述
     */
    private $summary = '';
    /**
     * @var string  SwaggerPHP注釋的接口描述
     */
    private $description = '';
    /**
     * @var string  SwaggerPHP注釋的操作ID
     */
    private $operationId;
    /**
     * @var string  SwaggerPHP注釋的標籤
     */
    private $tags;

    /**
     * @description 設置請求體結構
     *
     * @param $request
     *
     * @return $this
     */
    public function setRequest($request): SwaggerNotes
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @description 設置返回體結構
     *
     * @param $response
     *
     * @return $this
     */
    public function setResponse($response): SwaggerNotes
    {
        is_object($response) && $response = $response->original;
        $this->response = $response;

        return $this;
    }

    /**
     * @description 獲取出入參字段的備註信息「含是否必填」
     *
     * @param array $tables 指定表
     * @param array $rules  字段規則
     *
     * @return SwaggerNotes
     */
    public function setComments(array $tables = [], array $rules = []): SwaggerNotes
    {
        $params = $this->request->validated() + $this->response;
        camel_snake($params, 'snake');
        !empty($rules) && $rules = $this->request->rules();
        camel_snake($rules, 'camel');
        // 遞歸過濾出字段，因為response的結構可能會很深
        $columns = [];
        array_walk_recursive($params, static function ($value, $key) use (&$columns) {
            in_array($key, $columns, true) && $columns[$key] = $value;
        });
        // 獲取指定表的字段信息
        $columnsInfo = DBBuilder::getColumnsInfo($tables, $columns);
        foreach ($columnsInfo as &$value) {
            $value = (array)$value;
            $rule = $rules[$value['name']] ?? [''];
            is_string($rule) && $rule = explode('|', $rule);
            $value['required'] = array_first($rule) === 'required';
        }
        unset($value);
        $this->comments = array_column($columnsInfo, NULL, 'name');

        return $this;
    }

    /**
     * @description 設置SwaggerPHP注釋的接口概述
     *
     * @param string $summary
     *
     * @return $this
     */
    public function setSummary(string $summary): SwaggerNotes
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @description 設置SwaggerPHP注釋的接口描述
     *
     * @param string $description
     *
     * @return $this
     */
    public function setDescription(string $description): SwaggerNotes
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @description 設置SwaggerPHP注釋的操作ID
     *
     * @param string $function
     *
     * @return $this
     */
    public function setOperationId(string $function): SwaggerNotes
    {
        $this->operationId = $function;

        return $this;
    }

    /**
     * @description 設置SwaggerPHP注釋的標籤
     *
     * @param string $class
     *
     * @return $this
     */
    public function setTags(string $class): SwaggerNotes
    {
        $this->tags = get_class_name($class);

        return $this;
    }

    /**
     * @description 「生成|刷新」SwaggerPHP注釋內容以及SwaggerYaml文件
     *
     */
    public function generate(): void
    {
        $baseDir = base_path('swagger');
        $swaggerNotesDir = $baseDir . '/' . get_class_name(__CLASS__);
        generate_file($swaggerNotesDir, 'swagger.php', $this->formatInfo());
        generate_file($swaggerNotesDir . '/' . $this->tags, $this->operationId . '.php', $this->formatNotes());
        generate_file($baseDir, 'swagger_doc.yaml', Generator::scan([$swaggerNotesDir])->toYaml());
    }

    /**
     * @description 「生成|刷新」SwaggerPHP注釋Info信息
     *
     *
     * @return string
     */
    private function formatInfo(): string
    {
        return <<<EOF
<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="通用服務",
 *     description="- kibana log_type:<br>- kkday-common-svc: request log<br> - kkday-common-svc_db: 查看 db 相關 log<br/>"
 * )
 * @OA\Server(url="https://svc-affiliate-35.sit.kkday.com")
 * @OA\Server(url="https://common.sit.kkday.com")
 * @OA\Server(url="https://svc.sit.kkday.com")
 */
EOF;
    }

    /**
     * @description 構建SwaggerPHP注释内容
     */
    private function formatNotes(): string
    {
        $method = title_case($this->request->method());
        $summary = $this->summary ?: $this->request->route()->getAction('as', '');

        return <<<EOF
<?php

use OpenApi\Annotations as OA;

/**
 * @OA\\$method(
 *     path="{$this->request->getPathInfo()}",
 *     summary="$summary",
 *     description="$this->description",
 *     operationId="$this->operationId",
 *     tags={"$this->tags"},
{$this->formatRequestBody()}
{$this->formatResponse()}
 * )
 */
EOF;
    }

    /**
     * @description 格式化請求體
     *
     * @return string
     */
    private function formatRequestBody(): string
    {
        $requestBody = <<<EOF
EOF;
        if (in_array($this->request->method(), ['GET', 'HEAD'], true)) { // GET HEAD 只能用Parameter
            $requestBody .= trim($this->formatParameter(), PHP_EOL);
        } else { // POST 、 PUT 接口 均使用 RequestBody
            $type = get_type($this->request);
            $requiredStr = '"' . implode('","', array_keys((array_filter($this->comments, static function ($v) {
                    return $v['required'];
                })))) . '"';
            $requestBodyProperty = trim($this->formatProperty($this->request->validated()), PHP_EOL);
            $requestBody .= <<<EOF
 *     @OA\RequestBody(description="請求Body體",
 *         @OA\JsonContent(type="$type", required={$requiredStr},
$requestBodyProperty
 *         )
 *     ),
EOF;
        }

        return $requestBody;
    }

    /**
     * @description 格式化url路徑上的入參參數
     *
     * @return string
     */
    private function formatParameter(): string
    {
        $parameter = <<<EOF
EOF;
        foreach ($this->request->validated() as $field => $value) {
            $required = $this->comments[$field]['required'] ?? 'false';
            $description = $this->comments[$field]['comment'] ?? '';
            $type = get_type($value);
            $parameter .= <<<EOF
 *     @OA\Parameter(
 *         name="$field",
 *         in="query",
 *         description="$description",
 *         required=$required,
 *         @OA\Schema(
 *             type="$type",
 *             default="$value"
 *         )
 *     ),

EOF;
        }

        return $parameter;
    }

    /**
     * @description 格式化屬性內容
     *
     * @param array  $data
     * @param string $space
     *
     * @return string
     */
    private function formatProperty(array $data, string $space = ''): string
    {
        $property = <<<EOF
EOF;

        $propertyItems = 'Property';
        foreach ($data as $field => $value) {
            if ($propertyItems === 'Items') {
                continue;
            }
            // 如果field為整型，說明是列表，propertyItems使用Items，並把field置空
            is_int($field) && ($propertyItems = 'Items') && $field = '';
            // 獲取value的類型類型是否為數組或者對象
            $objectArr = in_array($type = get_type($value), ['object', 'array']);
            $objectValue = !$value && $field ? 'null' : '';
            is_bool($value) && $value = $value ? 'true' : 'false'; // 如果value為佈爾型，賦值為字符串
            !$value && $value = 'null';                            // 如果value為假，賦值為null字符串
            // 遍歷組裝propertiesItems的元素，只有$v存在且為真時，需要組裝進來
            $propertiesItems = [];
            $arr = [
                'property' => $field,
                'type' => $type,
                'description' => $this->comments[$field]['comment'] ?? '',
                'default' => $objectArr ? $objectValue : $value,
            ];
            foreach ($arr as $k => $v) {
                $v && $propertiesItems[] = $k . '="' . $v . '"';
            }
            // 打散成字符串
            $propertiesItems = implode(', ', $propertiesItems);
            $property .= <<<EOF
 *             $space@OA\\$propertyItems($propertiesItems),

EOF;
            if ($value !== 'null' && $objectArr) { // 值不為null字符串，且是个对象或者数组，就有子集
                // ),PHP_EOL ==> ,PHP_EOL
                $property = rtrim($property, '),' . PHP_EOL) . ',' . PHP_EOL;
                $spaces = $space . '    ';
                // 递归拼接子集
                $properties = trim($this->formatProperty($value, $spaces), PHP_EOL);
                $property .= <<<EOF
$properties
 *         $spaces),

EOF;
            }
        }

        return $property;
    }

    /**
     * @description 格式化返回結構
     *
     * @return string
     */
    private function formatResponse(): string
    {
        $responseProperty = trim($this->formatProperty($this->response), PHP_EOL);

        return <<<EOF
 *     @OA\Response(response=200, description="接口返回OK",
 *         @OA\JsonContent(type="object",
$responseProperty
 *         )
 *     ),
EOF;
    }
}