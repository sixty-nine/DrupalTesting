<?php

namespace Liip\Drupal\Testing\Test;

use Liip\Drupal\Testing\Test\DrupalTestCase;

// TODO: remove binding to my local install
define ('DRUPAL_ROOT', '/home/dev/drupal-test/src');

class DrupalTestCaseTest extends DrupalTestCase
{
    public function __construct()
    {
        // TODO: remove binding to my local install
        $baseUrl = 'http://drupal-test.lo';
        parent::__construct($baseUrl);
    }

    public function testDrupalLoginLogout()
    {
        // Try to get an unauthorized page
        $this->client->request('GET', $this->baseUrl . '/node/add');
        $this->assertResponseStatusEquals(403);

        // Login
        $this->drupalLogin('admin', '123123');

        // Check we can now get an unauthorized page
        $this->client->request('GET', $this->baseUrl . '/node/add');
        $this->assertResponseStatusEquals(200);

        // Logout
        $this->drupalLogout();

        // Try to get an unauthorized page
        $this->client->request('GET', $this->baseUrl . '/node/add');
        $this->assertResponseStatusEquals(403);
    }

    public function testDrupalCreateUser()
    {
        // Create the user
        $user = $this->drupalCreateUser();
        $this->assertInstanceOf('stdClass', $user);
        $this->assertEquals('test_user_', substr($user->name, 0, strlen('test_user_')));
        $this->assertRegExp('/test_user_(.*)@test\.com/', $user->mail);
        $this->assertTrue(is_array($user->roles));
        $this->assertContains('anonymous user', $user->roles);
        $this->assertContains('authenticated user', $user->roles);
        $this->assertCanLogin($user->name, $user->pass_raw);

        // Delete the user
        $this->drupalDeleteUser($user);
        $this->assertCannotLogin($user->name, $user->pass_raw);
    }
}
