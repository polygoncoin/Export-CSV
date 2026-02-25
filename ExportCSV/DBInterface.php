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
interface DBInterface
{
    /**
     * Set MySql connection details.
     *
     * @param string $hostname Hostname
     * @param string $username Username
     * @param string $password Password
     * @param string $database Database
     *
     * @return void
     * @throws \Exception
     */
    public function connect($hostname, $username, $password, $database): void;

    /**
     * Returns Shell Command
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     */
    public function getShellCommand($sql, $params = null): string;
}
