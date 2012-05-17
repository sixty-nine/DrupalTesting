<?php

namespace Liip\Drupal\Testing\Tests;

require __DIR__ . '/../Helper/Client.php';

use Liip\Drupal\Testing\Helper\Client;

class ClientTestCase extends \PHPUnit_Framework_TestCase {

    public function testConstructor()
    {
        $client = new Client();
        $out = $client->get('http://drupal-test.lo');

        var_dump($out);
    }
}
