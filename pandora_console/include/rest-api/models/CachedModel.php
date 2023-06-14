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
    public static function fromDB(array $filter, ?float $ratio=0, ?float $widthRatio=0): Model
    {
        global $config;
        $save_cache = false;
        if ($ratio == 0 && $filter['cache_expiration'] > 0 && $widthRatio == 0) {
            $data = static::fetchCachedData($filter);
            $save_cache = true;
            if (isset($filter['type']) === true
                && (int) $filter['type'] === GROUP_ITEM
                && empty($data) === false
            ) {
                // GROUP ITEM with cache.
                if (isset($data['statusImageSrc']) === true) {
                    $img = explode('images/console/icons/', $data['statusImageSrc']);
                    if (empty($img[1]) === false) {
                        $img_path = 'images/console/icons/'.$img[1];
                        $data['statusImageSrc'] = ui_get_full_url(
                            $img_path,
                            false,
                            false,
                            false
                        );
                    }

                    if (empty($img[0]) === false
                        && isset($data['link']) === true
                    ) {
                        $img_aux = explode('images/console/icons/', $data['statusImageSrc']);
                        if ($img_aux[0] !== $img[0]) {
                            $data['link'] = str_replace($img[0], $img_aux[0], $data['link']);
                        }
                    }
                }
            }
        }

        if (isset($data) === false) {
            $data = static::fetchDataFromDB($filter, $ratio, $widthRatio);
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
