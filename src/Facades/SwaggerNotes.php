<?php

namespace Toolbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static object call($request, $response, array $tables, array $columnsRules, string $namespace, string $function, string $class, string $summary, string $description)
 * @method static object generateNotes(array $params)
 *
 * @method static object setSummary(string $summary)
 * @method static object setDescription(string $description)
 * @method static object setOperationId(string $function)
 * @method static object setTags(string $class)
 * @method static object setNotesPath(string $namespace)
 * @method static object setComments(array $tables, array $rules)
 * @method static object setRequest($request)
 * @method static object setResponse($response)
 * @method static object generate($request = NULL, $response = NULL)
 *
 * @method static string getTags(string $class)
 * @method static string getPath(string $namespace, string $class)
 * @method static string formatNotes()
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
