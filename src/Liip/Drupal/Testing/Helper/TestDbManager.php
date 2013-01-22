<?php

namespace Liip\Drupal\Testing\Helper;

use Liip\Drupal\Modules\DrupalConnector\ConnectorFactory;

class TestDbManager
{
    protected $testDbNamePrefix = 'test-db';

    /**
     * The base path where the test database(s) will be created
     * @var string
     */
    protected $basePath;

    /**
     * The source SQLite database to be used for the tests.
     * This DB will be copied and will not be changed by the tests.
     * @var string
     */
    protected $sourceDb;

    /**
     * The name of the current test db path.
     * Must be static because 2 consecutive instances of TestDbManager should not overwrite each other test DBs
     * @static
     * @var string
     */
    protected static $curDbPath;

    /**
     * The name of the current test db file
     * Must be static because 2 consecutive instances of TestDbManager should not overwrite each other test DBs
     * @static
     * @var string
     */
    protected static $curDbName;

    /**
     * @param string $basePath
     * @param string $sourceDb
     */
    public function __construct($basePath, $sourceDb)
    {
        if (!is_dir($basePath) || !is_writable($basePath)) {
            throw new \InvalidArgumentException(sprintf("The directory '%s' does not exist or is not writable", $basePath));
        }

        if (!file_exists($sourceDb)) {
            throw new \InvalidArgumentException(sprintf("The source database '%s' does not exist", $sourceDb));
        }

        $this->basePath = $basePath;
        $this->sourceDb = $sourceDb;
    }

    /**
     * Create a new test DB if it does not yet exist. If $force = true, create a new DB in any case (i.e. even if
     * a test DB already exists).
     * @param bool $force
     * @return string
     */
    public function createTestDb($force = false, $testName = '')
    {
        // Create the test DB dir if it has not been created yet
        if (is_null(self::$curDbPath)) {
            self::$curDbPath = $this->getNextAvailableDirName();
            // TODO: find a way not to polute phpunit output with echos
            echo sprintf("Creating base directory for test db: %s\n", self::$curDbPath);
            mkdir(self::$curDbPath);
        }

        // If the test DB does not exist or we force the creation of a new one, then create it
        if ($force || is_null(self::$curDbName)) {
            self::$curDbName = $this->getNewAvailableDbName($testName);
            // TODO: find a way not to polute phpunit output with echos
            echo sprintf("Creating test database: %s\n", self::$curDbName);
        } else {
            // TODO: find a way not to polute phpunit output with echos
            echo sprintf("Using test database: %s\n", self::$curDbName);
        }

        copy($this->sourceDb, self::$curDbName);
    }

    /**
     * @static
     * @return string
     * @throws InvalidArgumentException
     */
    public static function getCurTestDb()
    {
        if (is_null(self::$curDbPath) || is_null(self::$curDbName)) {
            throw new \Exception("You must call createTestDb() at least once before using getCurTestDb()");
        }

        return self::$curDbName;
    }

    /**
     * Switch to the current test DB.
     * DO NOT USE THIS BEFORE DRUPAL IS BOOTSTRAPPED !
     * @static
     * @param string $prefix
     */
    public static function useTestDb($prefix = '')
    {
        // Hopefully no one else than Drupal will define the drupal_bootstrap global function, otherwise, please, set
        // that programmer on fire...
        if (!function_exists('drupal_bootstrap')) {
            throw new \Exception("You must bootstrap Drupal before calling useTestDb()");
        }

        $connectionName = uniqid('testdb');
        $connectionInfo = array(
            'database' => self::getCurTestDb(),
            'driver' => 'sqlite',
            'prefix' => $prefix,
        );
        \Database::addConnectionInfo($connectionName, 'default', $connectionInfo);
        ConnectorFactory::getDatabaseConnector()->db_set_active($connectionName);
    }

    /**
     * @static
     */
    public static function useDrupalDb()
    {
        ConnectorFactory::getDatabaseConnector()->db_set_active();
    }

    /**
     * Get a directory name under $this->basePath, that does not yet exist and has the form:
     *
     *      YYMMDD-N
     *
     * Where YYMMDD is the current date and N is an index number.
     *
     * @return string
     */
    protected function getNextAvailableDirName()
    {
        $index = 1;

        do {
            $destinationDir = sprintf('%s/%s.%s', $this->basePath, date('Ymd'), $index);
            $index++;
        } while (file_exists($destinationDir) || is_dir($destinationDir));

        return $destinationDir;
    }

    protected function getNewAvailableDbName($testName = '')
    {
        $index = 1;

        do {
            if ($testName === '') {
                $filename = sprintf('%s/%s.%s.db', self::$curDbPath, $this->testDbNamePrefix, $index);
            } else {
                $filename = sprintf('%s/%s.%s.%s.db', self::$curDbPath, $this->testDbNamePrefix, $testName, $index);
            }

            $index++;
        } while (file_exists($filename));

        return $filename;
    }

}
