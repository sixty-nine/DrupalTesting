<?php

namespace Liip\Drupal\Testing\Test;

use Liip\Drupal\Testing\Test\DrupalTestCase;

class DrupalTestCaseTest extends DrupalTestCase
{
    public function __construct()
    {
        parent::__construct('http://drupal-test.lo');
    }

    public function testDrupalGetUrl()
    {
        // Calling the function executes an implicit assertion
        $this->drupalGetUrl('http://unexiting-url.com', 404);
        $this->drupalGetUrl($this->baseUrl, 200);
        $this->drupalGetUrl($this->baseUrl);
        $this->drupalGetUrl('http://google.com', 301);
    }

    public function testDrupalSubmitForm()
    {
        // Before to login, we cannot add nodes
        $this->drupalGetUrl($this->baseUrl . '/node/add', 403);

        // Get the login form and extract the form_id and form_build_id
        $resp = $this->drupalSubmitForm('/user/login', 'user-login', array('name' => 'admin', 'pass' => '123123'));
        $this->assertEquals(302, $resp->getStatus());

        // After the login it's possible to add nodes
        $this->drupalGetUrl($this->baseUrl . '/node/add', 200);
    }

    public function testDrupalCreateUser()
    {
        $user = $this->drupalCreateUser(uniqid('test_user_'), 'test@test.com', '123123');
        //var_dump($user);
    }
}
