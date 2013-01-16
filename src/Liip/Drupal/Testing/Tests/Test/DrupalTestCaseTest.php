<?php

namespace Liip\Drupal\Testing\Tests;

use Liip\Drupal\Testing\Test\DrupalTestCase;
use Liip\Drupal\Modules\DrupalConnector\ConnectorFactory;


class DrupalTestCaseTest extends DrupalTestCase
{
    public function __construct()
    {
        parent::__construct(DRUPAL_BASEURL);
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
        $this->assertCanLogin($user);

        // Delete the user
        $this->drupalDeleteUser($user);
        $this->assertCannotLogin($user->name, $user->pass_raw);
    }

    public function testDrupalLoginLogout()
    {
        $user = $this->drupalCreateUser(null, null, null, array('administer nodes'));

        // Try to get an unauthorized page
        $this->client->request('GET', $this->baseUrl . '/node/add');
        $this->assertResponseStatusEquals(403);

        // Login
        $this->drupalLogin($user);

        // Check we can now get an unauthorized page
        $this->client->request('GET', $this->baseUrl . '/user/' . $user->uid);
        $this->assertResponseStatusEquals(200);

        // Logout
        $this->drupalLogout();

        // Try to get an unauthorized page
        $this->client->request('GET', $this->baseUrl . '/user/' . $user->uid);
        $this->assertResponseStatusEquals(403);

        $this->drupalDeleteUser($user);
    }

    public function testAssertModuleEnabled()
    {
        // Hopefully the "block" module is enabled
        $this->assertModuleEnabled('block');

        // This is a phpunit hack, this assertion is expected to fail because
        // there should be no module named 'unexisting-module' installed.
        // @see http://aventures-logicielles.blogspot.com/2011/03/phpunit-detect-failing-skipped-and.html
        try {
            $this->assertModuleEnabled('unexisting-module');
        } catch (\PHPUnit_Framework_ExpectationFailedException $ex) {
            // As expected the assertion failed, silently return
            return;
        }
        // The assertion did not fail, make the test fail
        $this->fail('Failed to assert that the module "unexisting-module" is not installed and enabled');
    }

    public function testEnableDisableModule()
    {
        // The name of a module that we expect to be disabled
        $hopefullyNotEnabledModule = 'taxonomy';

        if (ConnectorFactory::getModuleConnector()->module_exists($hopefullyNotEnabledModule)) {
            // The module is enabled, so skip this test
            $this->markTestSkipped();
        }

        // Check the module is disabled, then enable and disable it
        $this->assertModuleDisabled($hopefullyNotEnabledModule);
        $this->drupalEnableModule(array($hopefullyNotEnabledModule));
        $this->assertModuleEnabled($hopefullyNotEnabledModule);
        $this->drupalDisableModule(array($hopefullyNotEnabledModule));
        $this->assertModuleDisabled($hopefullyNotEnabledModule);
    }

    public function testCreateRemoveNode()
    {
        // Create the node and check it has been saved to the DB
        $node = $this->drupalCreateNode();
        $this->assertInstanceOf('stdClass', $node);
        $this->assertTrue(isset($node->nid) && is_numeric($node->nid) && $node->nid > 0);

        $drupalNode = ConnectorFactory::getNodeConnector()->node_load($node->nid);
        $this->assertSameNode($drupalNode, $node);

        // Delete the node and check it is not in the DB anymore
        $this->drupalDeleteNode($node->nid);
        $drupalNode = ConnectorFactory::getNodeConnector()->node_load($node->nid);
        $this->assertFalse($drupalNode);
    }
}
