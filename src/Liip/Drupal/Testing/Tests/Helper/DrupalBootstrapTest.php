<?php

namespace Liip\Drupal\Testing\Tests;

use Liip\Drupal\Testing\Test\DrupalTestCase,
    Liip\Drupal\Testing\Helper\DrupalBootstrap;


class DrupalBootstrapTest extends DrupalTestCase
{
  public function __construct()
  {
    parent::__construct(DRUPAL_BASEURL);

    $this->helper = new DrupalBootstrap();
  }

  public function testIsDrupalRoot()
  {
    $flag = $this->helper->isDrupalRoot(__DIR__);
    $this->assertFalse($flag);

    $flag = $this->helper->isDrupalRoot(DRUPAL_ROOT . '/sites/all/modules');
    $this->assertFalse($flag);

    $flag = $this->helper->isDrupalRoot(DRUPAL_ROOT);
    $this->assertTrue($flag);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testIsDrupalRootInvalidDir()
  {
    $this->helper->isDrupalRoot('/some/unexisting/dir');
  }

  public function testLookupDrupalRoot()
  {
    $root = $this->helper->lookupDrupalRoot(DRUPAL_ROOT);
    $this->assertEquals(DRUPAL_ROOT, $root);

    $root = $this->helper->lookupDrupalRoot(DRUPAL_ROOT . '/sites/all/modules');
    $this->assertEquals(DRUPAL_ROOT, $root);

    $root = $this->helper->lookupDrupalRoot(__DIR__);
    $this->assertFalse($root);
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testLookupDrupalRootInvalidDir()
  {
    $this->helper->lookupDrupalRoot('/some/unexisting/dir');
  }

  public function testBootstrapDrupal()
  {
    $this->helper->bootstrapDrupal();
    $admin = user_load(1);
    $this->assertEquals('admin', $admin->name);
  }

  public function testBootstrap()
  {
    DrupalBootstrap::bootstrap();
    $admin = user_load(1);
    $this->assertEquals('admin', $admin->name);
  }
}
