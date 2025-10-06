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

use ExportCSV\DB;

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
class ExportCSV
{
    /**
     * Allow creation of temporary file required for streaming large data
     *
     * @var bool
     */
    public $useTmpFile = true;

    /**
     * Used to remove file once CSV content is transferred on client machine
     *
     * @var bool
     */
    public $unlink = true;

    /**
     * DB Engine
     *
     * @var null|string
     */
    public $dbType = null;

    /**
     * DB Object
     *
     * @var null|DB
     */
    public $db = null;

    /**
     * Constructor
     *
     * @param string $dbType Database Type (eg. MySQL)
     *
     * @throws \Exception
     */
    public function __construct($dbType)
    {
        $this->dbType = $dbType;
        $this->db = new DB(dbType: $this->dbType);
    }

    /**
     * Connect DB
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
        $this->db->connect(
            hostname: $hostname,
            username: $username,
            password: $password,
            database: $database
        );
        $this->validateConnection();
    }

    /**
     * Validate Connection
     *
     * @return void
     * @throws \Exception
     */
    private function validateConnection(): void
    {
        $sql = 'SELECT 1;';

        $toggle = $this->useTmpFile;
        $this->useTmpFile = false;
        [$shellCommand, $tmpFilename] = $this->getShellCommand(
            sql: $sql
        );
        $this->useTmpFile = $toggle;

        $lines = shell_exec(command: $shellCommand);
        $linesArr = explode(separator: PHP_EOL, string: $lines);

        if (!($linesArr[0] == '"1"' && $linesArr[1] == '"1"')) {
            throw new \Exception(message: 'Issue while connecting to MySQL Host');
        }
    }

    /**
     * Validate file location.
     *
     * @param $fileLocation CSV file location.
     *
     * @return void
     * @throws \Exception
     */
    private function vFileLocation($fileLocation): void
    {
        if (!file_exists(filename: $fileLocation)) {
            throw new \Exception(
                message: "File '{$fileLocation}' already exists"
            );
        }
    }

    /**
     * Get Shell Command
     *
     * @param string      $sql                 query
     * @param array       $params              query params
     * @param null|string $csvAbsoluteFilePath Absolute file path
     *
     * @return array
     * @throws \Exception
     */
    private function getShellCommand(
        $sql,
        $params = [],
        $csvAbsoluteFilePath = null
    ): array {
        $shellCommand = $this->db->getShellCommand(sql: $sql, params: $params);
        $shellCommand .= ' | sed -e \'s/"/""/g ; s/\t/","/g ; s/^/"/g ; s/$/"/g\'';

        if (!is_null(value: $csvAbsoluteFilePath)) {
            $tmpFilename = $csvAbsoluteFilePath;
            $shellCommand .= ' > ' . escapeshellarg(arg: $tmpFilename);
        } elseif ($this->useTmpFile) {
            // Generate temporary file for storing output of shell command on server
            $tmpFilename = tempnam(directory: sys_get_temp_dir(), prefix: 'CSV');
            $shellCommand .= ' > ' . escapeshellarg(arg: $tmpFilename);
        } else {
            $tmpFilename = null;
            $shellCommand .= ' 2>&1';
        }

        return [$shellCommand, $tmpFilename];
    }

    /**
     * Initialize download.
     *
     * @param $csvFilename         Name of CSV file on client side.
     * @param $sql                 query
     * @param $params              query params
     * @param $csvAbsoluteFilePath Absolute file path with filename
     *
     * @return void
     */
    public function initDownload(
        $csvFilename,
        $sql,
        $params = [],
        $csvAbsoluteFilePath = null
    ): void {
        [$shellCommand, $tmpFilename] = $this->getShellCommand(
            sql: $sql,
            params: $params,
            csvAbsoluteFilePath: $csvAbsoluteFilePath
        );

        if (!is_null(value: $csvAbsoluteFilePath)) {
            $this->useTmpFile = true;
            $this->unlink = false;
        }

        if ($this->useTmpFile) {
            // Execute shell command
            // The shell command to create CSV export file.
            shell_exec(command: $shellCommand);
            $this->streamCsvFile(
                fileLocation: $tmpFilename,
                csvFilename: $csvFilename
            );
        } else {
            // Set headers
            $this->setCsvHeaders(csvFilename: $csvFilename);

            // Execute shell command
            // The shell command echos the output.
            echo shell_exec(command: $shellCommand);
        }
    }

    /**
     * Initialize download.
     *
     * @param $sql                 query
     * @param $params              query params
     * @param $csvAbsoluteFilePath Absolute file path with filename
     *
     * @return void
     */
    public function saveCsvExport(
        $sql,
        $params = [],
        $csvAbsoluteFilePath = null
    ): void {
        [$shellCommand, $tmpFilename] = $this->getShellCommand(
            sql: $sql,
            params: $params,
            csvAbsoluteFilePath: $csvAbsoluteFilePath
        );

        // Execute shell command
        // The shell command saves exported CSV data to provided path
        shell_exec(command: $shellCommand);
    }

    /**
     * Set CSV file headers
     *
     * @param $csvFilename Name to be used to save CSV file on client machine.
     *
     * @return void
     */
    private function setCsvHeaders($csvFilename): void
    {
        // CSV headers
        header(header: "Content-type: text/csv");
        header(header: "Content-Disposition: attachment; filename={$csvFilename}");
        header(header: "Pragma: no-cache");
        header(header: "Expires: 0");
    }

    /**
     * Stream CSV file to client.
     *
     * @param $fileLocation Absolute file location of CSV file.
     * @param $csvFilename  Name to be used to save CSV file on client machine.
     *
     * @return void
     */
    private function streamCsvFile($fileLocation, $csvFilename): void
    {
        // Validation
        $this->vFileLocation(fileLocation: $fileLocation);

        // Set headers
        $this->setCsvHeaders(csvFilename: $csvFilename);

        // Start streaming
        $srcStream = fopen(filename: $fileLocation, mode: 'r');
        $destStream = fopen(filename: 'php://output', mode: 'w');

        stream_copy_to_stream(from: $srcStream, to: $destStream);

        fclose(stream: $destStream);
        fclose(stream: $srcStream);

        if ($this->unlink && !unlink(filename: $fileLocation)) { // Unable to delete
            //handle error via logs.
        }
    }
}
