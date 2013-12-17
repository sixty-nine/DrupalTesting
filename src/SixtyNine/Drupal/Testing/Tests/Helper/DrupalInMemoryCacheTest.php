<?php

/**
 * @file
 * Tests functionality related to the DrupalInMemoryCache cache class
 */

namespace SixtyNine\Drupal\Testing\Tests;

use SixtyNine\Drupal\Testing\Test\DrupalTestCase;


class DrupalInMemoryCacheTest extends DrupalTestCase
{

    /**
     * @var string
     * The thing that will be stored in the cache storage
     */
    protected $thingy = 'DrupalInMemoryCacheTest';

    /**
     * @var string
     * Cache bin
     */
    protected $bin = 'DrupalInMemoryCacheBin';

    /**
     * @covers DrupalConnector::drupal_swap_cache_backend
     */
    protected function setUp()
    {
        if (!$this->connector->hasCustomCacheEnabled()) {
            $this->markTestSkipped('This test requires the in-memory cache replacement '
                . 'to be enabled. Check the phpunit.xml.dist file for the constant named '
                . 'DISABLE_CACHE_REPLACEMENT'
            );
            return;
        }
        parent::setUp();
    }

    /**
     * This test works in conjuction with the next one and ensures that the
     * cache storage is cleaned after each test is ran
     * @see    testCacheWiped
     *
     * @covers DrupalInMemoryCache::variableSet
     * @covers DrupalInMemoryCache::variableGet
     */
    public function testPreCacheWipe()
    {
        cache_set($this->thingy, $this->thingy, $this->bin);
        $this->assertEquals($this->thingy,
          cache_get($this->thingy, $this->bin)->data, 'Cache item fetched successfully'
        );
    }

    /**
     * Test that the cache item set in the previous test is not present anymore
     * @depends testPreCacheWipe
     * @covers  DrupalConnector::hasCustomCacheEnabled
     */
    public function testCacheWiped()
    {
        $this->assertFalse(cache_get($this->thingy, $this->bin), 'The object was not found in cache');
    }

    /**
     * @covers DrupalInMemoryCache::garbageCollection
     * @covers DrupalInMemoryCache::clear
     */
    public function testCacheClearAll()
    {
        global $conf;
        $conf['cache_lifetime'] = 0;
        //$conf['cache_flush_' . $this->bin] = 1;

        $this->setExpiredCacheItem();
        // @see #534092, #1774332
        // @TODO this should fail in D8
        $this->assertEquals(
          $this->thingy, cache_get($this->thingy, $this->bin)->data,
          'cache_get returned the expired item from storage (which is correct behaviour for D7)'
        );
        // now clear cache - we expect the item to be deleted
        cache_clear_all(NULL, $this->bin);
        $this->assertFalse(cache_get($this->thingy, $this->bin),
          'Cache item removed after cache_clear_all'
        );

        // test wildcards
        foreach(array(NULL, '*', 'DrupalIn', $this->thingy) as $cid) {
          $this->setExpiredCacheItem();
          // now clear cache - we expect the item to be deleted
          cache_clear_all($cid, $this->bin, TRUE);
          $this->assertFalse(cache_get($this->thingy, $this->bin),
            'Cache item removed after cache_clear_all with wildcard cid: '
            . !is_null($cid) ? $cid : ' NULL'
          );
        }

        // assert that cache_clear_all with wildcard set to TRUE and wrong CID
        // will not clear valid cache item
        $this->setExpiredCacheItem();
        cache_clear_all('NonExistantCid', $this->bin, TRUE);
        $this->assertEquals(
          $this->thingy, cache_get($this->thingy, $this->bin)->data
        );
    }

    /**
     * Set a cache with an expiry time in the past
     */
    protected function setExpiredCacheItem() {
        cache_set($this->thingy, $this->thingy, $this->bin, (time() - 24*60*60));
    }
}
