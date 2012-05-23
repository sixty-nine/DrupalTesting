<?php

namespace Liip\Drupal\Testing\Test;

use Liip\Drupal\Testing\Helper\DrupalConnector;

class DrupalTestCase extends WebTestCase
{
    protected $connector;

    protected $baseUrl;

    public function __construct($baseUrl)
    {
        parent::__construct();
        
        $this->baseUrl = $baseUrl;
        $this->connector = new DrupalConnector();
        //$this->connector->bootstrapDrupal();
    }

    /**
     * Log in to Drupal
     * @param string $user
     * @param string $pass
     * @return void
     */
    protected function drupalLogin($user, $pass)
    {
        $crawler = $this->getCrawler($this->baseUrl . '/user');
        $this->assertResponseStatusEquals(200);

        $form = $crawler->selectButton('Log in')->form();
        $this->submitForm($form, array('name' => $user, 'pass' => $pass));
    }

    /**
     * Logout from Drupal
     * @return void
     */
    protected function drupalLogout()
    {
        $this->getCrawler($this->baseUrl . '/user/logout');
    }
}
