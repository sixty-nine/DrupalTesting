<?php

namespace Liip\Drupal\Testing\Helper;

// ---- STUFF TO REMOVE LATER -------------------------------------------------
define('DRUPAL_ROOT', '/home/dev/drupal-test/src');

require 'NetHelper.php';
// ----------------------------------------------------------------------------

use Liip\Drupal\Testing\Helper\NetHelper;

class Drupal
{
    public function __construct()
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['REMOTE_ADDR'] = NetHelper::getServerAddress();

        require_once DRUPAL_ROOT . '/includes/bootstrap.inc';

        //drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);
    }

    public function install()
    {
        require_once DRUPAL_ROOT . '/includes/install.core.inc';
        require_once DRUPAL_ROOT . '/includes/database/database.inc';

        global $databases;
        $databases['default'] = array(
            'default' => array(
                'driver' => 'sqlite',
                'database' => '/tmp/drupal.sqlite',
            )
        );

        $settings = array(
            'interactive' => false,
            'parameters' => array('profile' => 'default'),
        );

//        \Database::renameConnection('default', 'default.old');
//        \Database::addConnectionInfo('default', 'default', $database);

        install_drupal($settings);

//        \Database::removeConnection('default');
//        \Database::renameConnection('default.old', 'default');
    }

}
