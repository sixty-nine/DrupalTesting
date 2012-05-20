<?php

namespace Liip\Drupal\Testing\Test;

use Liip\Drupal\Testing\Web\Client,
    Liip\Drupal\Testing\Web\Curl,
    Liip\Drupal\Testing\Helper\Drupal;

use Monolog\Logger,
    Monolog\Formatter\LineFormatter,
    Monolog\Handler\TestHandler;

class DrupalTestCase extends \PHPUnit_Framework_TestCase
{
    protected $logger;

    protected $logHandler;

    protected $client;

    protected $baseUrl;

    public function __construct($baseUrl)
    {
        $this->logHandler = new TestHandler();
        $this->logger = new Logger('my_logger');
        $this->logger->pushHandler($this->logHandler);
        $this->baseUrl = $baseUrl;

        Drupal::bootstrap();
    }

    public function setUp()
    {
        $curl = new Curl();
        $curl->setLogger($this->logger);

        $this->client = new Client($curl);
        $this->client->setLogger($this->logger);
    }

    protected function drupalGetUrl($url, $expectedStatus = 200)
    {
        $resp = $this->client->get($url);
        $this->assertEquals($expectedStatus, $resp->getStatus(), sprintf("Getting URL '%s', expected status %s, got %s", $url, $expectedStatus, $resp->getStatus()));
        return $resp;
    }

    protected function drupalLogin($user, $pass)
    {
        $resp = $this->drupalSubmitForm('/user/login', 'user-login', array('name' => 'admin', 'pass' => '123123'));
        $this->assertEquals(302, $resp->getStatus(), 'Invalid credentials');
        return $resp;
    }

    protected function drupalSubmitForm($relativeUrl, $formId, $values = array())
    {
        $url = $this->baseUrl . $relativeUrl;

        $fields = $this
            ->drupalGetUrl($url)
            ->getCrawler()
            ->getFields($formId);

        foreach ($values as $key => $val) {
            $fields[$key] = $val;
        }

        return $this->client->post($url, $fields);
    }

    /**
     * Create a user with a given set of permissions.
     *
     * @param array $permissions
     *   Array of permission names to assign to user. Note that the user always
     *   has the default permissions derived from the "authenticated users" role.
     *
     * @return object|false
     *   A fully loaded user object with pass_raw property, or FALSE if account
     *   creation fails.
     */
    protected function drupalCreateUser($name, $email, $pass, array $permissions = array())
    {
      // Create a role with the given permission set, if any.
      $rid = FALSE;
      if ($permissions) {
        $rid = $this->drupalCreateRole($permissions);
        if (!$rid) {
          return FALSE;
        }
      }

      // Create a user assigned to that role.
      $edit = array();
      $edit['name']   = $name;
      $edit['mail']   = $email;
      $edit['pass']   = $pass;
      $edit['status'] = 1;
      if ($rid) {
        $edit['roles'] = array($rid => $rid);
      }

      $account = user_save(drupal_anonymous_user(), $edit);

      $this->assertTrue(!empty($account->uid), t('User created with name %name and pass %pass', array('%name' => $edit['name'], '%pass' => $edit['pass'])), t('User login'));
      if (empty($account->uid)) {
        return FALSE;
      }

      // Add the raw password so that we can log in as this user.
      $account->pass_raw = $edit['pass'];
      return $account;
    }


    protected function dumpLogger()
    {
        foreach ($this->logHandler->getRecords() as $record) {
            echo $record['formatted'];
        }
    }
}
