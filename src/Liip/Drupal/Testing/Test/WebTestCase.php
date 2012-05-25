<?php

namespace Liip\Drupal\Testing\Test;

use Goutte\Client;
use Symfony\Component\DomCrawler\Form;

abstract class WebTestCase extends DebuggableTestCase
{
    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    /**
     * Get a crawler for the given URL.
     * @param string $url
     * @param string $method
     * @return \Symfony\Component\BrowserKit\Crawler
     */
    protected function getCrawler($url, $method = 'GET')
    {
        return $this->client->request($method, $url);
    }

    /**
     * Submit a form with the given values and assert the response status is 200
     * @param \Symfony\Component\DomCrawler\Form $form
     * @param array $values
     * @return void
     */
    protected function submitForm(Form $form, $values = array())
    {
        foreach ($values as $key => $val) {
            if (!$form->has($key)) {
                $this->fail(sprintf("The form does not have a field with name %s", $key));
            }
            $form[$key] = $val;
        }
        $this->client->submit($form);
        $this->assertResponseStatusEquals(200);
    }

    // ----- ASSERTIONS -------------------------------------------------------

    /**
     * Assert the status of the response to the request of the given URL.
     * @param string $url
     * @param int $expectedStatus
     * @param string $method
     * @return void
     */
    protected function assertResponseStatusEquals($expectedStatus)
    {
        $status = $this->client->getResponse()->getStatus();
        $uri = $this->client->getRequest()->getUri();
        
        $this->assertEquals(
            $expectedStatus,
            $status,
            sprintf("Requested '%s', expected response status %s, got %s", $uri, $expectedStatus, $status)
        );

    }

}
