<?php


namespace Toolbox\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getColumnsInfo(?array $tableNames, array $columns = [])
 * @method static mixed queryBuilder(array $filter, $queryBuilder)
 * @method static mixed filterBuilder(array $filter, $queryBuilder)
 *
 * @see Toolbox\DBHelper
 */
class DBBuilder extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return \Toolbox\DBBuilder::class;
    }
}
