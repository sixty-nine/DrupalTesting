<?php

require __DIR__ . '/vendor/autoload.php';

// Check the required constant were defined
foreach (array('DRUPAL_ROOT', 'DRUPAL_BASEURL') as $constant) {
    if (!defined($constant)) {
        die("You must define the constant $constant in your phpunit.xml\n");
    }
}

