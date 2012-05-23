<?php

namespace Liip\Drupal\Testing\Test;

use Monolog\Logger;

abstract class DebuggableTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @private
     * @var \Monolog\Logger
     */
    private $logger;

    /**
     * Set the logger
     * @param \Monolog\Logger $logger
     * @return void
     */
    protected function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger
     * @return void
     */
    protected function getLogger()
    {
        return $this->logger;
    }

    /**
     * Log a message
     * @param string $msg
     * @param int $level
     * @param array $context
     * @return void
     */
    protected function log($msg, $level, $context = array())
    {
        if (!is_null($this->logger)) {
            $this->logger->addRecord($level, $msg, $context);
        }
    }

}
