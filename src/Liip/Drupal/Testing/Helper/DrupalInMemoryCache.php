<?php

/**
 * @file
 * In memory cache implementation to be used during unit testing
 *
 * The cache class is replaced in DrupalConnector::drupal_swap_cache_backend
 *
 * Provides a way to keep a "permanent" and unaltered storage of the original
 * primed cached (which happened during bootstrap) - see WebTestCase::setUp.
 * After each test completes (see WebTestCase::tearDown) the cache is
 * restored back to its initial state
 */

class DrupalInMemoryCache extends DrupalDatabaseCache implements DrupalCacheInterface
{

    /**
     * @var array
     */
    private $storage = array();

    /**
     * @var array
     */
    private $originalStorage = array();

    /**
     * @var
     * Storage for variables to avoid using variable(_get/_set) within the class
     */
    protected static $vars;

    /**
     * @var DrupalInMemoryCache[]
     */
    protected static $cacheObjects = array();

    /**
     * Constructs a new DrupalInMemoryCache object.
     */
    public function __construct($bin)
    {
        $this->bin = $bin;
        // store a reference of all cache instances
        self::$cacheObjects[$bin] = $this;
    }

    /**
     * Used in setUp method to make a copy of the storage for all existing
     * cache objects
     */
    public static function enableTempStorage()
    {
        foreach (self::$cacheObjects as $obj) {
            $obj->storeOriginalStorage();
        }
    }

    /**
     * Restore the original copy of storage for all existing cache objects
     */
    public static function disableTempStorage()
    {
        foreach (self::$cacheObjects as $obj) {
            $obj->restoreOriginalStorage();
        }
    }

    /**
     * Create a copy of the storage. Uses un(serialize) so that objects are
     * dereferenced (copied by value)
     */
    public function storeOriginalStorage()
    {
        $this->originalStorage = unserialize(serialize($this->storage));
    }

    /**
     * Restore the original copy of the storage
     * Restore the original copy of the storage
     */
    public function restoreOriginalStorage()
    {
        $this->storage = unserialize(serialize($this->originalStorage));
    }

    /**
     * Replacement of variable_set
     *
     * @param $name
     * @param $value
     */
    public static function variableSet($name, $value)
    {
        self::$vars[$name] = $value;
    }

    /**
     * Replacement of variable_get
     *
     * @param $name
     * @param null $default
     * @return null
     */
    public static function variableGet($name, $default = NULL)
    {
        return isset(self::$vars[$name]) ? self::$vars[$name] : $default;
    }

    /**
     * Implements DrupalCacheInterface::getMultiple().
     */
    function getMultiple(&$cids)
    {
        // Garbage collection necessary when enforcing a minimum cache lifetime.
        $this->garbageCollection($this->bin);

        $cache = array();
        foreach ($this->storage as $key_cid => $data) {
            if (in_array($key_cid, $cids)) {
                $item = $this->prepareItem($data);
                if ($data) {
                    $cache[$data->cid] = $data;
                }
            }
        }

        $cids = array_diff($cids, array_keys($cache));
        return $cache;
    }

    /**
     * Garbage collection for get() and getMultiple().
     *
     * @param $bin
     *   The bin being requested.
     */
    protected function garbageCollection()
    {
        $cache_lifetime = DrupalInMemoryCache::variableGet('cache_lifetime', 0);

        // Clean-up the per-user cache expiration session data, so that the session
        // handler can properly clean-up the session data for anonymous users.
        if (isset($_SESSION['cache_expiration'])) {
            $expire = REQUEST_TIME - $cache_lifetime;
            foreach ($_SESSION['cache_expiration'] as $bin => $timestamp) {
                if ($timestamp < $expire) {
                    unset($_SESSION['cache_expiration'][$bin]);
                }
            }
            if (!$_SESSION['cache_expiration']) {
                unset($_SESSION['cache_expiration']);
            }
        }

        // Garbage collection of temporary items is only necessary when enforcing
        // a minimum cache lifetime.
        if (!$cache_lifetime) {
            return;
        }
        // When cache lifetime is in force, avoid running garbage collection too
        // often since this will remove temporary cache items indiscriminately.
        $cache_flush = DrupalInMemoryCache::variableGet('cache_flush_' . $this->bin, 0);
        if ($cache_flush && ($cache_flush + $cache_lifetime <= REQUEST_TIME)) {
            // Reset the variable immediately to prevent a meltdown in heavy load situations.
            DrupalInMemoryCache::variableSet('cache_flush_' . $this->bin, 0);
            // Time to flush old cache data
            foreach ($this->storage as $key_cid => $data) {
                if ($data->expire != CACHE_PERMANENT && $data->expire <= $cache_flush) {
                    unset($this->storage[$key_cid]);
                }
            }
        }
    }

    /**
     * Prepares a cached item.
     *
     * Checks that items are either permanent or did not expire, and unserializes
     * data as appropriate.
     *
     * @param $cache
     *   An item loaded from cache_get() or cache_get_multiple().
     *
     * @return
     *   The item with data unserialized as appropriate or FALSE if there is no
     *   valid item to load.
     */
    protected function prepareItem($cache)
    {
        global $user;

        if (!isset($cache->data)) {
            return FALSE;
        }
        // If the cached data is temporary and subject to a per-user minimum
        // lifetime, compare the cache entry timestamp with the user session
        // cache_expiration timestamp. If the cache entry is too old, ignore it.
        if ($cache->expire != CACHE_PERMANENT && DrupalInMemoryCache::variableGet('cache_lifetime', 0) && isset($_SESSION['cache_expiration'][$this->bin]) && $_SESSION['cache_expiration'][$this->bin] > $cache->created) {
            // Ignore cache data that is too old and thus not valid for this user.
            return FALSE;
        }

        // If the data is permanent or not subject to a minimum cache lifetime,
        // unserialize and return the cached data.
        if ($cache->serialized && is_string($cache->data)) {
            $cache->data = unserialize($cache->data);
        }

        return $cache;
    }

    /**
     * Implements DrupalCacheInterface::set().
     */
    function set($cid, $data, $expire = CACHE_PERMANENT)
    {
        $fields = array(
            'serialized' => 0,
            'created' => REQUEST_TIME,
            'expire' => $expire,
        );

        // serialization is required even for in memory storage because get()
        // should return a clone of an object
        if (!is_string($data)) {
            $fields['data'] = serialize($data);
            $fields['serialized'] = 1;
        }
        else {
            $fields['data'] = $data;
            $fields['serialized'] = 0;
        }

        $this->storage[$cid] = (object)$fields;
    }

    /**
     * Implements DrupalCacheInterface::clear().
     */
    function clear($cid = NULL, $wildcard = FALSE)
    {
        global $user;

        if (empty($cid)) {
            if (DrupalInMemoryCache::variableGet('cache_lifetime', 0)) {
                // We store the time in the current user's session. We then simulate
                // that the cache was flushed for this user by not returning cached
                // data that was cached before the timestamp.
                $_SESSION['cache_expiration'][$this->bin] = REQUEST_TIME;

                $cache_flush = DrupalInMemoryCache::variableGet('cache_flush_' . $this->bin, 0);
                if ($cache_flush == 0) {
                    // This is the first request to clear the cache, start a timer.
                    DrupalInMemoryCache::variableSet('cache_flush_' . $this->bin, REQUEST_TIME);
                }
                elseif (REQUEST_TIME > ($cache_flush + DrupalInMemoryCache::variableGet('cache_lifetime', 0))) {
                    foreach ($this->storage as $key_cid => $data) {
                        if ($data->expire != CACHE_PERMANENT && $data->expire < REQUEST_TIME) {
                            unset($this->storage[$key_cid]);
                        }
                    }
                    DrupalInMemoryCache::variableSet('cache_flush_' . $this->bin, 0);
                }
            }
            else {
                // No minimum cache lifetime, flush all temporary cache entries now.
                foreach ($this->storage as $key_cid => $data) {
                    if ($data->expire != CACHE_PERMANENT && $data->expire < REQUEST_TIME) {
                        unset($this->storage[$key_cid]);
                    }
                }
            }
        }
        else {
            if ($wildcard) {
                if ($cid == '*') {
                    $this->storage = array();
                }
                else {
                    foreach ($this->storage as $key_cid => $data) {
                        if (strpos($key_cid, $cid) !== FALSE) {
                            unset($this->storage[$key_cid]);
                        }
                    }
                }
            }
            elseif (is_array($cid)) {
                $this->storage = array_intersect($this->storage, array_combine($cid, $cid));
            }
            else {
                unset($this->storage[$cid]);
            }
        }
    }

    /**
     * Implements DrupalCacheInterface::isEmpty().
     */
    function isEmpty()
    {
        return empty($this->storage);
    }
}
