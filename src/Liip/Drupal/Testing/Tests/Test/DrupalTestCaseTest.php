<?php

namespace Liip\Drupal\Testing\Test;

use Liip\Drupal\Testing\Test\DrupalTestCase;

class DrupalTestCaseTest extends DrupalTestCase
{
    public function __construct()
    {
        parent::__construct('http://drupal-test.lo');
    }

    public function testDrupalLoginLogout()
    {
        // Try to get an unauthorized page
        $this->client->request('GET', 'http://drupal-test.lo/node/add');
        $this->assertResponseStatusEquals(403);

        // Login
        $this->drupalLogin('admin', '123123');

        // Check we can now get an unauthorized page
        $this->client->request('GET', 'http://drupal-test.lo/node/add');
        $this->assertResponseStatusEquals(200);

        // Logout
        $this->drupalLogout();

        // Try to get an unauthorized page
        $this->client->request('GET', 'http://drupal-test.lo/node/add');
        $this->assertResponseStatusEquals(403);
    }
}
