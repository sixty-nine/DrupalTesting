<?php

namespace Liip\Drupal\Testing\Debug;

use Monolog\Logger;

interface DebugableInterface
{
    /**
     * Set the logger
     * @abstract
     * @param \Monolog\Logger $logger
     * @return void
     */
    function setLogger(Logger $logger);

    /**
     * Get the logger
     * @abstract
     * @return void
     */
    function getLogger();

    /**
     * Log a message
     * @abstract
     * @param string $msg
     * @param int $level
     * @param array $context
     * @return void
     */
    function log($msg, $level, $context = array());
}
