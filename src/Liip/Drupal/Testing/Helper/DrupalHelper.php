<?php

namespace Liip\Drupal\Testing\Helper;

use Goutte\Client;

class DrupalHelper
{
    protected $connector;

    public function __construct()
    {
        $this->connector = new DrupalConnector();
    }

    public function getConnector()
    {
        return $this->connector;
    }

    public function drupalBootstrap($httpHost = null)
    {
        if (defined('DRUPAL_ROOT')) {

            $defaults = array(
                'PHP_SELF' => '/index.php',
                'QUERY_STRING' => '',
                'REQUEST_URI' => '/',
                'SCRIPT_NAME' => NULL,
                'REMOTE_ADDR' => NetHelper::getServerAddress(),
                'REQUEST_METHOD' => 'GET',
                'SERVER_NAME' => NULL,
                'SERVER_SOFTWARE' => NULL,
                'HTTP_USER_AGENT' => 'console',
            );

            if ($httpHost) {
                $defaults['HTTP_HOST'] = $httpHost;
            }

            $_SERVER = $_SERVER + $defaults;

            require_once DRUPAL_ROOT . '/includes/bootstrap.inc';
            require_once DRUPAL_ROOT . '/includes/entity.inc';
            require_once DRUPAL_ROOT . '/includes/common.inc';
            require_once DRUPAL_ROOT . '/modules/system/system.module';
            require_once DRUPAL_ROOT . '/includes/database/select.inc';

            if (!defined('DISABLE_CACHE_REPLACEMENT') || !DISABLE_CACHE_REPLACEMENT) {
                $this->connector->drupal_swap_cache_backend();
            }
            $this->connector->drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

        }
        else {
            throw new \InvalidArgumentException('Constant DRUPAL_ROOT is not defined.');
        }
    }

    /**
     * Log in to Drupal
     * @param string $user
     * @param string $pass
     * @param bool $expectedToFail Set this to true if you expect the credentials to be wrong
     * @return void
     */
    public function drupalLogin($user, $pass, $expectedToFail = false)
    {
        // TODO: implement without the client/crawler
    }

    /**
     * Logout from Drupal
     * @return void
     */
    public function drupalLogout()
    {
        // TODO: implement without the client/crawler
    }

    /**
     * Return true is the current user is logged in
     * @return bool
     */
    public function drupalIsLoggedIn()
    {
        // TODO: implement without the client/crawler
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
     * @param array $domains Array of domain_id where the user has access
     * @return object|false
     *   A fully loaded user object with pass_raw property, or FALSE if account
     *   creation fails.
     */
    public function drupalCreateUser($name = null, $email = null, $pass = null, array $permissions = array(), $domains = array())
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

        // Assign the domain access
        $edit['domain_user'] = array();
        foreach ($domains as $domainId) {
            $edit['domain_user'][$domainId] = $domainId;
        }

        $account = $this->connector->user_save($this->connector->drupal_anonymous_user(), $edit);

//        $this->assertTrue(!empty($account->uid), sprintf('Could not create user %s', $name));
//        $this->log(sprintf('User created with name %s and pass %s', $name, $pass), Logger::INFO);

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
    public function drupalCreateRole(array $permissions, $name = NULL)
    {
        // Generate random name if it was not passed.
        if (!$name) {
            $name = uniqid('role_');
        }

        // Create new role.
        $role = new \stdClass();
        $role->name = $name;
        $this->connector->user_role_save($role);
        $this->connector->user_role_grant_permissions($role->rid, $permissions);

        if ($role && !empty($role->rid)) {

            $count = $this->connector->db_query(
                'SELECT COUNT(*) FROM {role_permission} WHERE rid = :rid',
                array(':rid' => $role->rid)
            )->fetchField();

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
    public function drupalDeleteUser($account)
    {
        $this->connector->user_delete($account->uid);
    }

    public function drupalGetUserByName($name)
    {
        $list = $this->connector->db_query(
            'SELECT uid FROM {users} WHERE name = :name',
            array(':name' => $name)
        )->fetchAllAssoc('uid');

        if (!empty($list)) {
            $item = reset($list);
            return $this->connector->user_load($item->uid);
        }
        return false;
    }

    public function drupalGetNodeByTitle($title)
    {
        $list = $this->connector->db_query(
            'SELECT nid FROM {node} WHERE title = :title',
            array(':title' => $title)
        )->fetchAllAssoc('nid');

        if (!empty($list)) {
            $item = reset($list);
            return $this->connector->node_load($item->nid);
        }
        return false;
    }

    /**
     * Install and enable Drupal modules
     * @param array $moduleList
     * @param bool $enableDependencies
     * @return void
     */
    public function drupalEnableModule(array $moduleList, $enableDependencies = false)
    {
        $this->connector->module_enable($moduleList, $enableDependencies);
    }

    /**
     * Disable Drupal modules
     * @param array $moduleList
     * @param bool $disableDependencies
     * @return void
     */
    public function drupalDisableModule(array $moduleList, $disableDependencies = false)
    {
        $this->connector->module_disable($moduleList, $disableDependencies);
    }

    /**
     * Return true if the given module is enabled
     * @param string $moduleName
     * @return bool
     */
    public function drupalModuleEnabled($moduleName)
    {
        return $this->connector->module_exists($moduleName);
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
    public function drupalCreateNode($settings = array())
    {
        // Populate defaults array.
        $settings += array(
            'body' => array(LANGUAGE_NONE => array(array())),
            'title' => uniqid('node_'),
            'comment' => 2,
            'changed' => REQUEST_TIME,
            'moderate' => 0,
            'promote' => 0,
            'revision' => 1,
            'log' => '',
            'status' => 1,
            'sticky' => 0,
            'type' => 'page',
            'revisions' => NULL,
            'language' => LANGUAGE_NONE,
        );

        // Use the original node's created time for existing nodes.
        if (isset($settings['created']) && !isset($settings['date'])) {
            $settings['date'] = $this->connector->format_date($settings['created'], 'custom', 'Y-m-d H:i:s O');
        }

        if (!isset($settings['uid'])) {
            $settings['uid'] = $this->connector->current_user()->uid;
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

        $node = (object)$settings;
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
    public function drupalDeleteNode($nid)
    {
        $this->connector->node_delete($nid);
    }

    /**
     * Return the non-standard roles (i.e. user defined) for a given user
     * @param object $user A loaded Drupal user
     * @return array An array of role IDs
     */
    public function drupalGetUserNonStandardRoles($user)
    {
        // TODO: check if what is above is always true
        // Here we use a trick, $user->roles will return an array containing another array:
        //  - for standard roles: array(RID => role name)
        //  - for non-standard roles: array(RID => RID)
        // Here we search for the second possibility
        $roles = array();
        foreach ($user->roles as $key => $val) {
            // Do not use strict equality here: one is never sure if Drupal returns a string or an int
            if ($key == $val) {
                $roles[] = $key;
            }
        }
        return $roles;
    }

}
