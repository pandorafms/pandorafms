<?php

declare(strict_types=1);

namespace Models;
use Models\Model;

/**
 * This class should be extended to add functionalities to
 * fetch, clear and save item cache.
 */
abstract class CachedModel extends Model
{

    /**
     * Used to decide if the cache should also be indexed by user or not.
     *
     * @var boolean
     */
    protected static $indexCacheByUser = false;


    /**
     * Obtain a data structure from the database using a filter.
     *
     * @param array $filter Filter to retrieve the modeled element.
     *
     * @return array The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @abstract
     */
    abstract protected static function fetchCachedData(array $filter);


    /**
     * Stores the data structure obtained.
     *
     * @param array $filter Filter to retrieve the modeled element.
     * @param array $data   Data to store in cache.
     *
     * @return array The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @abstract
     */
    abstract protected static function saveCachedData(
        array $filter,
        array $data
    ): bool;


    /**
     * Deletes previous data that are not useful.
     *
     * @param array $filter Filter to retrieve the modeled element.
     *
     * @return array The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @abstract
     */
    abstract protected static function clearCachedData(array $filter): int;


    /**
     * Obtain a model's instance from the database using a filter.
     *
     * @param array $filter Filter to retrieve the modeled element.
     *
     * @return self A modeled element's instance.
     *
     * @overrides Model::fromDB.
     */
    public static function fromDB(array $filter): Model
    {
        global $config;
        $save_cache = false;
        if ($filter['cache_expiration'] > 0) {
            $data = static::fetchCachedData($filter);
            $save_cache = true;
        }

        if (isset($data) === false) {
            $data = static::fetchDataFromDB($filter);
        } else {
            // Retrieved from cache.
            $save_cache = false;
        }

        if ($save_cache === true) {
            // Rebuild cache.
            if (static::saveCachedData($filter, $data) !== true) {
                throw new \Exception(
                    $config['dbconnection']->error
                );
            }
        }

        return static::fromArray($data);
    }


}
