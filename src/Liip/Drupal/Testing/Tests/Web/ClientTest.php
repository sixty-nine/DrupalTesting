<?php

namespace Liip\Drupal\Testing\Tests\Web;

use Liip\Drupal\Testing\Web\Client,
    Liip\Drupal\Testing\Web\Curl;

use Monolog\Logger,
    Monolog\Formatter\LineFormatter,
    Monolog\Handler\TestHandler;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    protected $logger;

    protected $logHandler;

    protected $client;

    public function __construct()
    {
        $this->logHandler = new TestHandler();
        $this->logger = new Logger('my_logger');
        $this->logger->pushHandler($this->logHandler);
    }

    public function setUp()
    {
        $curl = new Curl();
        $curl->setLogger($this->logger);

        $this->client = new Client($curl);
        $this->client->setLogger($this->logger);
    }

    public function testGet()
    {
        // Search for <span>Powered by <a href="http://drupal.org">Drupal</a></span>  </div>
        $resp = $this->client->get('http://drupal-test.lo');
        $this->assertEquals(200, $resp->getStatus());

        $crawler = $resp->getCrawler();
        $this->assertEquals('Drupal', (string)reset($crawler->xpath('//span/a[@href="http://drupal.org"]')));
    }

    /**
     * Test Drupal login
     * @return void
     */
    public function testLogin()
    {
        // Before to login, we cannot add nodes
        $resp = $this->client->get('http://drupal-test.lo/node/add');
        $this->assertEquals(403, $resp->getStatus());

        // Get the login form and extract the form_id and form_build_id
        $resp = $this->client->get('http://drupal-test.lo/user/login');
        $this->assertEquals(200, $resp->getStatus());

        $crawler = $resp->getCrawler();

        // Login and check we are redirected
        $resp = $this->client->post(
            'http://drupal-test.lo/user/login',
            array(
                'name'          => 'admin',
                'pass'          => '123123',
                'form_build_id' => $crawler->getInputValue('user-login', 'form_build_id'),
                'form_id'       => $crawler->getInputValue('user-login', 'form_id'),
            )
        );
        $this->assertEquals(302, $resp->getStatus());

        // After the login it's possible to add nodes
        $resp = $this->client->get('http://drupal-test.lo/node/add');
        $this->assertEquals(200, $resp->getStatus());

        //$this->dumpLogger();
    }

    protected function dumpLogger()
    {
        foreach ($this->logHandler->getRecords() as $record) {
            echo $record['formatted'];
        }
    }
}
