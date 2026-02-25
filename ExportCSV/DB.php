<?php

/**
 * Export CSV
 * php version 7
 *
 * @category  CSV
 * @package   ExportCSV
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Export-CSV
 * @since     Class available since Release 1.0.0
 */

namespace ExportCSV;

use ExportCSV\DBInterface;

/**
 * Export MySQL query results as CSV
 * php version 7
 *
 * @category  CSV
 * @package   ExportCSV
 * @author    Ramesh N. Jangid (Sharma) <polygon.co.in@gmail.com>
 * @copyright © 2026 Ramesh N. Jangid (Sharma)
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Export-CSV
 * @since     Class available since Release 1.0.0
 */
class DB
{
    /**
     * Allow creation of temporary file required for streaming large data
     *
     * @var bool
     */
    public $useTmpFile = false;

    /**
     * DB Engine
     *
     * @var null|string
     */
    public $dbType = null;

    /**
     * DB Class Object as per dbType
     *
     * @var null|DBInterface
     */
    public $dbTypeObj = null;

    /**
     * Constructor
     *
     * @param string $dbType Database Type (eg. MySQL)
     */
    public function __construct($dbType)
    {
        $this->dbType = $dbType;
        $class = "ExportCSV\\" . $this->dbType;
        $this->dbTypeObj = new $class();
    }

    /**
     * Connect DB
     *
     * @param string $hostname hostname
     * @param string $username username
     * @param string $password password
     * @param string $database database
     *
     * @return void
     * @throws \Exception
     */
    public function connect($hostname, $username, $password, $database): void
    {
        $this->dbTypeObj->connect(
            hostname: $hostname,
            username: $username,
            password: $password,
            database: $database
        );
    }

    /**
     * Returns Shell Command
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     */
    public function getShellCommand($sql, $params = []): string
    {
        // Validation
        if (empty($sql)) {
            throw new \Exception(message: 'Empty Sql query');
        }

        return $this->dbTypeObj->getShellCommand(
            sql: $sql,
            params: $params
        );
    }
}
