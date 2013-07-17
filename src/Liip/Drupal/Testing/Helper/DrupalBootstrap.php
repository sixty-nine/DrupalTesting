<?php

namespace Liip\Drupal\Testing\Helper;

use Goutte\Client;

class DrupalBootstrap
{
    /**
     * Static function provided for convenience of bootstraping Drupal without instanciating this class.
     * @static
     * @param $root
     */
    public static function bootstrap($root = null)
    {
        $helper = new DrupalBootstrap();
        $helper->bootstrapDrupal($root);
    }

    /**
     * Bootstrap Drupal.
     *
     * This function will try to find the Drupal root directory as follow:
     *
     *  - If the DRUPAL_ROOT constant is defined, use it and ignore the $root provided.
     *  - Else if $root is provided then search for a valid Drupal root in that path and its parents.
     *    IF A VALID ROOT IS FOUND THE DRUPAL_ROOT CONSTANT WILL BE DEFINED !
     *  - Otherwise fail.
     *
     * @param string|null $root
     * @param string $httpHost
     */
    public function bootstrapDrupal($root = null, $httpHost = null)
    {
        $connector = new DrupalConnector();

        if (!defined('DRUPAL_ROOT')) {

            // If a $root is provided, search the Drupal root there.
            if (!is_null($root)) {
                $drupalRoot = $this->lookupDrupalRoot($root);
                if ($drupalRoot) {
                    define('DRUPAL_ROOT', $drupalRoot);
                }
                else {
                    throw new \InvalidArgumentException("DRUPAL_ROOT constant not defined and no Drupal install could be found in the provided path.");
                }
            }
        }

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
            $connector->drupal_swap_cache_backend();
        }
        $connector->drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    }

    /**
     * Try to locate the Drupal root in the given path or any of it parents.
     * @param string $path
     */
    public function lookupDrupalRoot($path)
    {
        while ($path !== '/') {
            if ($this->isDrupalRoot($path)) {
                return $path;
            }
            $path = dirname($path);
        }

        return false;
    }

    /**
     * Try to determine if the given path contains a valid Drupal installation.
     * @param string $path
     * @return bool
     */
    public function isDrupalRoot($path)
    {
        if (!is_dir($path)) {
            throw new \InvalidArgumentException(sprintf("Invalid directory '%s'", $path));
        }

        $path = realpath($path);

        // Check if the drupal configuration file exists
        if (!file_exists($path . '/sites/default/settings.php')) {
            return false;
        }

        // Check if the drupal bootstrap exists
        if (!file_exists($path . '/includes/bootstrap.inc')) {
            return false;
        }

        return true;
    }
}
