<?php

namespace Liip\Drupal\Testing\Test;

use Liip\Drupal\Testing\Helper\DrupalConnector;

use Symfony\Component\DomCrawler\Crawler;

use Monolog\Logger;

abstract class DrupalTestCase extends WebTestCase
{
    protected $connector;

    protected $baseUrl;

    public function __construct($baseUrl)
    {
        parent::__construct();

        $this->baseUrl = $baseUrl;
        $this->connector = new DrupalConnector();
        $this->connector->bootstrapDrupal();
    }

    /**
     * Log in to Drupal
     * @param string $user
     * @param string $pass
     * @param bool $expectedToFail Set this to true if you expect the credentials to be wrong
     * @return void
     */
    protected function drupalLogin($user, $pass, $expectedToFail = false)
    {
        $crawler = $this->getCrawler($this->baseUrl . '/user');
        $this->assertResponseStatusEquals(200);

        $form = $crawler->selectButton('Log in')->form();
        $this->submitForm($form, array('name' => $user, 'pass' => $pass));

        $isLoggedIn = $this->drupalIsLoggedIn();

        if (!$expectedToFail) {
            $this->assertTrue($isLoggedIn, sprintf("Login failed for user %s, pass %s", $user, $pass));
            $this->log(sprintf('User %name successfully logged in.', $user->name), Logger::INFO);
        } else {
            $this->assertFalse($isLoggedIn, sprintf("Login succeeded but was expected to fail for user %s, pass %s", $user, $pass));
        }
    }

    /**
     * Logout from Drupal
     * @return void
     */
    protected function drupalLogout()
    {
        $this->getCrawler($this->baseUrl . '/user/logout');
    }

    /**
     * Create a user with a given set of permissions.
     *
     * (from simpletest)
     *
     * @param string $name
     * @param string $email
     * @param string $pass
     * @param array $permissions
     *   Array of permission names to assign to user. Note that the user always
     *   has the default permissions derived from the "authenticated users" role.
     * @return object|false
     *   A fully loaded user object with pass_raw property, or FALSE if account
     *   creation fails.
     */
    protected function drupalCreateUser($name = null, $email = null, $pass = null, array $permissions = array())
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
        $edit['name'] = is_null($name) ? uniqid('test_user_') : $name;
        $edit['mail'] = is_null($email) ? $edit['name'] . '@test.com' : $email;
        $edit['pass'] = is_null($pass) ? $this->connector->user_password() : $pass;
        $edit['status'] = 1;
        if ($rid) {
            $edit['roles'] = array($rid => $rid);
        }

        $account = $this->connector->user_save($this->connector->drupal_anonymous_user(), $edit);

        $this->assertTrue(!empty($account->uid), sprintf('Could not create user %s', $name));
        $this->log(sprintf('User created with name %s and pass %s', $name, $pass), Logger::INFO);

        // Add the raw password so that we can log in as this user.
        $account->pass_raw = $edit['pass'];
        return $account;
    }

    /**
     * Internal helper function; Create a role with specified permissions.
     *
     * (from simpletest)
     *
     * @param array $permissions Array of permission names to assign to role.
     *   Array of permission names to assign to role.
     * @param $name
     *   (optional) String for the name of the role.  Defaults to a random string.
     * @return mixed Role ID of newly created role, or FALSE if role creation failed.
     */
    protected function drupalCreateRole(array $permissions, $name = NULL)
    {
        // Generate random name if it was not passed.
        if (!$name) {
            $name = uniqid('role_');
        }

        // Check the all the permissions strings are valid.
        if (!$this->assertValidPermissions($permissions)) {
            return FALSE;
        }

        // Create new role.
        $role = new stdClass();
        $role->name = $name;
        $this->connector->user_role_save($role);
        $this->connector->user_role_grant_permissions($role->rid, $permissions);

        $this->assertTrue(isset($role->rid), sprintf('Could not create role %s', $name));
        $this->log(sprintf("Created role '%s', RID = %s", $name, $role->rid), Logger::INFO);

        if ($role && !empty($role->rid)) {
            
            $count = $this->connector->db_query(
                'SELECT COUNT(*) FROM {role_permission} WHERE rid = :rid',
                array(':rid' => $role->rid)
            )->fetchField();
            
            $this->assertTrue($count == count($permissions), sprintf('The permissions count for %s does not match', $name));
            $this->log(sprintf('Created permissions: %s', implode(', ', $permissions)), Logger::INFO);

            return $role->rid;
        }
        else {
            return FALSE;
        }
    }

    /**
     * Delete a Drupal user
     * @param $account
     * @return void
     */
    protected function drupalDeleteUser($account)
    {
        $this->connector->user_delete($account->uid);
    }

    /**
     * Install and enable Drupal modules
     * @param array $moduleList
     * @param bool $enableDependencies
     * @return void
     */
    protected function drupalEnableModule(array $moduleList, $enableDependencies = false)
    {
        $this->connector->module_enable($moduleList, $enableDependencies);
    }

    /**
     * Disable Drupal modules
     * @param array $moduleList
     * @param bool $disableDependencies
     * @return void
     */
    protected function drupalDisableModule(array $moduleList, $disableDependencies = false)
    {
        $this->connector->module_disable($moduleList, $disableDependencies);
    }

    /**
     * Return true if the given module is enabled
     * @param string $moduleName
     * @return bool
     */
    protected function drupalModuleEnabled($moduleName)
    {
        return $this->connector->module_exists($moduleName);
    }

    /**
     * Return true is the current user is logged in
     * @return bool
     */
    public function drupalIsLoggedIn()
    {
        $crawler = $this->getCrawler($this->baseUrl . '/user');
        $this->assertResponseStatusEquals(200);

        // Search for the logout link (even on non standard install where there is a prefix before the usr /user/logout)
        $list = $crawler->filterXPath('//a');
        foreach($list as $el) {
            if ($el->hasAttribute('href')) {
                $value = $el->attributes->getNamedItem('href')->value;
                if (preg_match('/\/user\/logout/', $value)) {
                    // We found a logout link
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Creates a node based on default settings.
     *
     * (from simpletest)
     *
     * @param array $settings An associative array of settings to change from the defaults, keys are
     *   An associative array of settings to change from the defaults, keys are
     *   node properties, for example 'title' => 'Hello, world!'.
     * @return object
     *   Created node object.
     */
    protected function drupalCreateNode($settings = array())
    {
        // Populate defaults array.
        $settings += array(
            'body'      => array(LANGUAGE_NONE => array(array())),
            'title'     => uniqid('node_'),
            'comment'   => 2,
            'changed'   => REQUEST_TIME,
            'moderate'  => 0,
            'promote'   => 0,
            'revision'  => 1,
            'log'       => '',
            'status'    => 1,
            'sticky'    => 0,
            'type'      => 'page',
            'revisions' => NULL,
            'language'  => LANGUAGE_NONE,
        );

      // Use the original node's created time for existing nodes.
        if (isset($settings['created']) && !isset($settings['date'])) {
            $settings['date'] = format_date($settings['created'], 'custom', 'Y-m-d H:i:s O');
        }

        // TODO: Cleanup if it's working
        // If the node's user uid is not specified manually, use the currently
        // logged in user if available, or else the user running the test.
        if (!isset($settings['uid'])) {
//          if ($this->loggedInUser) {
//              $settings['uid'] = $this->loggedInUser->uid;
//          }
//          else {
            $settings['uid'] = $this->connector->current_user()->uid;
//              global $user;
//              $settings['uid'] = $user->uid;
//          }
        }

        // Merge body field value and format separately.
        $content = uniqid('node_body_');
        $body = array(
            'value' => $content,
            'summary' => null,
            'format' => $this->connector->filter_default_format(),
            'safe_value' => "<p>$content</p>\n",
            'safe_summary' => '',
        );
        $settings['body'][$settings['language']][0] += $body;

        $node = (object) $settings;
        $this->connector->node_save($node);

        // Small hack to link revisions to our test user.
        $this->connector->db_update('node_revision')
            ->fields(array('uid' => $node->uid))
            ->condition('vid', $node->vid)
            ->execute();

        return $node;
    }

    /**
     * Delete a Drupal node
     * @param int $nid The node id to delete
     * @return void
     */
    protected function drupalDeleteNode($nid) {
        $this->connector->node_delete($nid);
    }


    // ----- ASSERTIONS -------------------------------------------------------

    /**
     * Check to make sure that the array of permissions are valid.
     *
     * (from simpletest)
     *
     * @param array $permissions Permissions to check.
     * @param bool $reset Reset cached available permissions.
     * @return bool TRUE or FALSE depending on whether the permissions are valid.
     */
    protected function assertValidPermissions(array $permissions, $reset = FALSE)
    {
        $available = &$this->connector->drupal_static(__FUNCTION__);

        if (!isset($available) || $reset) {
            $available = array_keys($this->connector->module_invoke_all('permission'));
        }

        $valid = TRUE;
        foreach ($permissions as $permission) {
            if (!in_array($permission, $available)) {
                $this->fail(sprintf('Invalid permission %permission.', $permission));
                $valid = FALSE;
            }
        }
        return $valid;
    }

    /**
     * Assert the given credentials are valid to login to Drupal
     * @param string $user
     * @param string $pass
     * @return void
     */
    protected function assertCanLogin($user, $pass)
    {
        $this->drupalLogin($user, $pass);
        $this->drupalLogout();
    }

    /**
     * Assert the given credentials are not valid to login to Drupal
     * @param string $user
     * @param string $pass
     * @return void
     */
    protected function assertCannotLogin($user, $pass)
    {
        $this->drupalLogin($user, $pass, true);
    }

    /**
     * Assert the access to a node
     * @param mixed $user A loaded Drupal user or the uid
     * @param mixed $node A loaded Drupal node or the nid
     * @param string $op The operation to test: view, update, delete, create
     * @param bool $expectAccess TRUE if the user is expected to be able to access the node
     * @return void
     */
    protected function assertNodeAccess($user, $node, $op = 'view', $expectAccess = true)
    {
        if (is_numeric($user)) {
            $account = $this->connector->user_load($user);
        } else {
            $account = $user;
        }

        if (is_numeric($node)) {
            $node = $this->connector->node_load($node);
        }

        $this->assertEquals(
            $expectAccess,
            $this->connector->node_access($op, $account, $node),
            sprintf(
                'Failed to assert that the user %s %s %s access to node %s',
                $user->uid,
                $expectAccess ? 'has' : 'does not have',
                $op,
                $node->nid
            )
        );
    }

    /**
     * Assert the given user has the right to do the given operation on a node
     * @param mixed $user A loaded Drupal user or the uid
     * @param mixed $node A loaded Drupal node or the nid
     * @param string $op The operation to test: view, update, delete, create
     * @return void
     */
    protected function assertUserCanAccess($user, $node, $op = 'view') {
        $this->assertTrue($this->assertNodeAccess($op, $user, $node));
    }

    /**
     * Assert the given user does not have the right to do the given operation on a node
     * @param mixed $user A loaded Drupal user or the uid
     * @param mixed $node A loaded Drupal node or the nid
     * @param string $op The operation to test: view, update, delete, create
     * @return void
     */
    protected function assertUserCannotAccess($user, $node, $op = 'view') {
        $this->assertFalse($this->assertNodeAccess($op, $user, $node, false));
    }

    /**
     * Assert a module is installed and enabled
     * @param $moduleName
     * @return void
     */
    protected function assertModuleEnabled($moduleName) {
        $this->assertTrue(
            $this->drupalModuleEnabled($moduleName),
            sprintf('The module %s is not enabled', $moduleName)
        );
    }

    /**
     * Assert a module is installed and enabled
     * @param $moduleName
     * @return void
     */
    protected function assertModuleDisabled($moduleName) {
        $this->assertFalse(
            $this->drupalModuleEnabled($moduleName),
            sprintf('The module %s is not disabled', $moduleName)
        );
    }

    /**
     * Assert that all the properties of the actual node that are also defined in the expected node
     * have the same values in both objects.
     *
     * This is usefull to test that a node created with drupalCreateNode correspond to the same node
     * saved in Drupal. Indeed during the save and load of nodes, Drupal might add/remove properties.
     *
     * @param $expectedNode
     * @param $actualNode
     * @return void
     */
    protected function assertSameNode($expectedNode, $actualNode) {

        foreach ($actualNode as $key => $val) {
            if (isset($expectedNode->$key)) {
                $this->assertEquals(
                    $expectedNode->$key, $val,
                    sprintf(
                        "Failed to assert that the value of the property '%s' is the same in expected and actual node (%s <> %s)",
                        $key,
                        print_r($expectedNode->$key, true),
                        $val
                    )
                );
            }
        }
    }

}
