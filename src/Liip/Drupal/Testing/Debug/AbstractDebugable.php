<?php

namespace Liip\Drupal\Testing\Debug;

use Monolog\Logger;

class AbstractDebugable implements DebugableInterface
{
    /**
     * @var \Monolog\Logger
     */
    protected $logger;

    /**
     * Set the logger
     * @param \Monolog\Logger $logger
     * @return void
     */
    function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger
     * @return void
     */
    function getLogger()
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
    function log($msg, $level, $context = array())
    {
        if (!is_null($this->logger)) {
            $this->logger->addRecord($level, $msg, $context);
        }
    }
}
