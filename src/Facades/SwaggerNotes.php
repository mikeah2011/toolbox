<?php

namespace Toolbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static object setRequest($request)
 * @method static object setResponse($response)
 * 
 * @method static object setComments(array $tables = [], array $rules = [])
 * @method static object setSummary(string $summary = '')
 * @method static object setDescription(string $description = '')
 * @method static object setOperationId(string $function = '')
 * @method static object setTags(string $class = '')
 *
 * @method static object generate()
 *
 * @see \Toolbox\SwaggerNotes
 */
class SwaggerNotes extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Toolbox\SwaggerNotes::class;
    }
}
