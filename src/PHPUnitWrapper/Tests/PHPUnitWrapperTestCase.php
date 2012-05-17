<?php

define('DRUPAL_ROOT', __DIR__ . '/../../../../../../../');
$_SERVER['REQUEST_METHOD'] = 'get';
$_SERVER['REMOTE_ADDR'] = '10.86.194.17';

require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

require __DIR__ . '/../Test/DrupalTestCase.php';

use PHPUnitWrapper\Test\DrupalTestCase;

class PHPUnitWrapperTestCase extends DrupalTestCase {

    public function testSomething() {

        drupal_install_system();
        $this->drupalGet('http://drupal-test.lo');
        $this->assertTrue(true);
    }
}
