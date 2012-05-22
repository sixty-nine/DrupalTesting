<?php

namespace Liip\Drupal\Testing\Test;

use Goutte\Client;

class GoutteTest extends  \PHPUnit_Framework_TestCase
{
    public function testDrupalLogin()
    {
        // Goutte makes almost all this code obsolete...

        $client = new Client();

        // Try to get an unauthorized page
        $client->request('GET', 'http://drupal-test.lo/node/add');
        $this->assertEquals(403, $client->getResponse()->getStatus());

        // Login
        $crawler = $client->request('GET', 'http://drupal-test.lo/user');
        $this->assertEquals(200, $client->getResponse()->getStatus());
        $form = $crawler->selectButton('Log in')->form();
        $form['name'] = 'admin';
        $form['pass'] = '123123';
        $client->submit($form);

        // Check we can now get an unauthorized page
        $client->request('GET', 'http://drupal-test.lo/node/add');
        $this->assertEquals(200, $client->getResponse()->getStatus());
    }
}
