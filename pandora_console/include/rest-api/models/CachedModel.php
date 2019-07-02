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

        if ($filter['cache_expiration'] > 0) {
            // Obtain the item's data from cache.
            $cachedData = static::fetchCachedData($filter);
            if ($cachedData === null) {
                $userId = (static::$indexCacheByUser === true) ? $config['id_user'] : null;

                // Delete expired data cache.
                static::clearCachedData(
                    [
                        'vc_item_id' => $filter['id'],
                        'vc_id'      => $filter['id_layout'],
                        'user_id'    => $userId,
                    ]
                );
                // Obtain the item's data from the database.
                $data = static::fetchDataFromDB($filter);
                // Save the item's data in cache.
                static::saveCachedData(
                    [
                        'vc_item_id' => $filter['id'],
                        'vc_id'      => $filter['id_layout'],
                        'user_id'    => $userId,
                        'expiration' => $filter['cache_expiration'],
                    ],
                    $data
                );
            } else {
                $data = $cachedData;
            }
        } else {
            $data = static::fetchDataFromDB($filter);
        }

        return static::fromArray($data);
    }


}
