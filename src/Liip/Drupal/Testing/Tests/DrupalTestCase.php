<?php

namespace Liip\Drupal\Testing\Tests;

require __DIR__ . '/../Helper/Drupal.php';

use Liip\Drupal\Testing\Helper\Drupal;

class DrupalTestCase extends \PHPUnit_Framework_TestCase {

    public function testConstructor()
    {
        $drupal = new Drupal();
        $drupal->install();
    }
}
