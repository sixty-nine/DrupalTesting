<?php

// Load the composer autoloader
require __DIR__ . '/vendor/autoload.php';

use Liip\Drupal\Testing\Helper\TestDbManager;

$requiredConstants = array('DRUPAL_ROOT', 'DRUPAL_BASEURL', 'DRUPAL_ADMIN_PASSWORD');

// Check the required constant were defined
foreach ($requiredConstants as $constant) {
    if (!defined($constant)) {
        die("You must define the constant $constant in your phpunit.xml\n");
    }
}

if (!RESET_DB_ON_EACH_TEST) {

    $mgr = new TestDbManager(TEST_DB_BASE_PATH, SOURCE_TEST_DB);
    $mgr->createTestDb();
}
