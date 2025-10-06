<?php

/**
 * Export CSV
 * php version 7
 *
 * @category  CSV
 * @package   ExportCSV
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
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
 * @author    Ramesh N Jangid <polygon.co.in@gmail.com>
 * @copyright 2025 Ramesh N Jangid
 * @license   MIT https://opensource.org/license/mit
 * @link      https://github.com/polygoncoin/Export-CSV
 * @since     Class available since Release 1.0.0
 */
class MySQL implements DBInterface
{
    /**
     * Hostname
     *
     * @var null|string
     */
    private $hostname = null;

    /**
     * Username
     *
     * @var null|string
     */
    private $username = null;

    /**
     * Password
     *
     * @var null|string
     */
    private $password = null;

    /**
     * Database
     *
     * @var null|string
     */
    private $database = null;

    /**
     * Mysql Client binary location (One can find this by "which mysql" command)
     *
     * @var string
     */
    private $binaryLoc = '/usr/local/bin/mysql';

    /**
     * Constructor
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        $requiredExtension = 'mysqli';
        if (!extension_loaded(extension: $requiredExtension)) {
            if (!dl(extension_filename: $requiredExtension . '.so')) {
                throw new \Exception(
                    message: "Required PHP extension '{$requiredExtension}' missing"
                );
            }
        }
        if (!file_exists(filename: $this->binaryLoc)) {
            throw new \Exception(message: 'Issue: missing MySQL Client locally');
        }
    }

    /**
     * Set connection details.
     *
     * @param string $hostname Hostname
     * @param string $username Username
     * @param string $password Password
     * @param string $database Database
     *
     * @return void
     * @throws \Exception
     */
    public function connect($hostname, $username, $password, $database): void
    {
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * Validate
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return void
     * @throws \Exception
     */
    private function validate($sql, $params): void
    {
        if (empty($sql)) {
            throw new \Exception(message: 'Empty Sql query');
        }

        if (count(value: $params) === 0) {
            return;
        }

        //Validate parameterized query.
        if (
            substr_count(
                haystack: $sql,
                needle: ':'
            ) !== count(value: $params)
        ) {
            throw new \Exception(
                message: 'Parameterized query has mismatch in number of params'
            );
        }

        $paramKeys = array_keys(array: $params);
        $paramPos = [];
        foreach ($paramKeys as $value) {
            if (substr_count(haystack: $sql, needle: $value) > 1) {
                throw new \Exception(
                    message: 'Parameterized query has more than one ' .
                        "occurrence of param '{$value}'"
                );
            }
            $paramPos[$value] = strpos(haystack: $sql, needle: $value);
        }
        foreach ($paramPos as $key => $value) {
            if (
                substr(
                    string: $sql,
                    offset: $value,
                    length: strlen(string: $key)
                ) !== $key
            ) {
                throw new \Exception(message: "Invalid param key '{$key}'");
            }
        }
    }

    /**
     * Generate raw Sql query from parameterized query via PDO.
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     * @throws \Exception
     */
    private function generateRawSqlQuery($sql, $params): string
    {
        if (empty($params) || count(value: $params) === 0) {
            return $sql;
        }

        $this->validate(sql: $sql, params: $params);

        //mysqli connection
        $mysqli = mysqli_connect(
            hostname: $this->hostname,
            username: $this->username,
            password: $this->password,
            database: $this->database
        );
        if (!$mysqli) {
            throw new \Exception(
                message: 'Connection error: ' . mysqli_connect_error()
            );
        }

        //Generate bind params
        $bindParams = [];
        foreach ($params as $key => $values) {
            if (is_array(value: $values)) {
                $tmpParams = [];
                $count = 1;
                foreach ($values as $value) {
                    if (is_array(value: $value)) {
                        throw new \Exception(
                            message: "Invalid params for key '{$key}'"
                        );
                    }
                    $newKey = $key . $count;
                    if (in_array(needle: $newKey, haystack: $tmpParams)) {
                        throw new \Exception(
                            message: "Invalid parameterized params '{$newKey}'"
                        );
                    }
                    $tmpParams[$key . $count++] = $value;
                }
                $sql = str_replace(
                    search: $key,
                    replace: implode(
                        separator: ', ',
                        array: array_keys(array: $tmpParams)
                    ),
                    subject: $sql
                );
                $bindParams = array_merge($bindParams, $tmpParams);
            } else {
                $bindParams[$key] = $values;
            }
        }

        //Replace parameterized values.
        foreach ($bindParams as $key => $value) {
            if (!ctype_digit(text: $value)) {
                $value = "'" .
                    mysqli_real_escape_string(
                        mysql: $mysqli,
                        string: $value
                    ) .
                "'";
            }
            $sql = str_replace(search: $key, replace: $value, subject: $sql);
        }

        // Close mysqli connection.
        mysqli_close(mysql: $mysqli);

        return $sql;
    }

    /**
     * Returns Shell Command
     *
     * @param string $sql    query
     * @param array  $params query params
     *
     * @return string
     */
    public function getShellCommand($sql, $params = null): string
    {
        $sql = $this->generateRawSqlQuery(sql: $sql, params: $params);

        // Shell command.
        $shellCommand = $this->binaryLoc . ' '
            . '--host=' . escapeshellarg(arg: $this->hostname) . ' '
            . '--user=' . escapeshellarg(arg: $this->username) . ' '
            . '--password=' . escapeshellarg(arg: $this->password) . ' '
            . '--database=' . escapeshellarg(arg: $this->database) . ' '
            . '--execute=' . escapeshellarg(arg: $sql);

        return $shellCommand;
    }
}
