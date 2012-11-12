<?php

// Load the composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Check the required constant were defined
foreach (array('DRUPAL_ROOT', 'DRUPAL_BASEURL', 'DRUPAL_ADMIN_PASSWORD') as $constant) {
    if (!defined($constant)) {
        die("You must define the constant $constant in your phpunit.xml\n");
    }
}

