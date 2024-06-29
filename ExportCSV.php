<?php
/**
 * Export MySQL query results as a CSV file
 *
 * @category   CSV
 * @package    Export CSV
 * @author     Ramesh Narayan Jangid
 * @copyright  Ramesh Narayan Jangid
 * @version    Release: @1.0.0@
 * @since      Class available since Release 1.0.0
 */
class ExportCSV
{
    /**
     * @var MySql hostname.
     */
    private $hostname = null;

    /**
     * @var MySql username.
     */
    private $username = null;

    /**
     * @var MySql password.
     */
    private $password = null;

    /**
     * @var MySql database.
     */
    private $database = null;

    /**
     * @var MySql PDO object.
     */
    private $pdo = null;

    /** 
     * @var boolean Allow creation of temporary file required for streaming large data. 
     */ 
    public $useTmpFile = true;

    /** 
     * @var boolean Used to remove file once CSV content is transferred on client machine. 
     */ 
    public $unlink = true; 

    /** 
     * Constructor
     * 
     * @return void 
     */ 
    public function __construct()
    {
        $requiredExtension = 'mysqli';
        if (!extension_loaded($requiredExtension)) {
            if (!dl($requiredExtension . '.so')) {
                throw new Exception("Required PHP extension '{$requiredExtension}' missing"); 
            }
        }
    }

    /** 
     * Validate Sql query. 
     * 
     * @param $sql MySql query whose output is used to be used to generate a CSV file. 
     * 
     * @return void 
     */ 
    private function vSql($sql) 
    { 
        if (empty($sql)) {
            throw new Exception('Empty Sql query'); 
        } 
    } 

    /** 
     * Validate CSV filename. 
     * 
     * @param $csvFilename Name to be used to save CSV file on client machine. 
     * 
     * @return void 
     */ 
    private function vCsvFilename($csvFilename) 
    { 
        if (empty($csvFilename)) { 
            throw new Exception('Empty CSV filename'); 
        } 
    } 

    /** 
     * Validate file location. 
     * 
     * @param $csvFilename Name to be used to save CSV file on client machine. 
     * 
     * @return void 
     */ 
    private function vFileLocation($fileLocation) 
    { 
        if (!file_exists($fileLocation)) { 
            throw new Exception('Invalid file location : ' . $fileLocation); 
        } 
    } 

    /** 
     * Set MySql connection details. 
     * 
     * @param $hostname MySql hostname.
     * @param $username MySql username.
     * @param $password MySql password.
     * @param $database MySql database.
     * 
     * @return void 
     */ 
    public function connect($hostname, $username, $password, $database)
    { 
        $this->hostname = $hostname;
        $this->username = $username;
        $this->password = $password;
        $this->database = $database;

        $sql = 'SELECT 1;';

        $useTmpFile = $this->useTmpFile;
        $this->useTmpFile = false;
        list($shellCommand, $tmpFilename) = $this->getShellCommand($sql);
        $this->useTmpFile = $useTmpFile;

        $lines = shell_exec($shellCommand);
        $linesArr = explode(PHP_EOL, $lines);

        if (!($linesArr[0] == '"1"' && $linesArr[1] == '"1"')) {
            throw new Exception('Issue while connecting to MySQL Client / Host');
        }
    } 

    /** 
     * Initialise download. 
     * 
     * @param $csvFilename         Name to be used to save CSV file on client machine.  
     * @param $sql                 MySql query whose output is used to be used to generate a CSV file. 
     * @param $params              MySql query bng params used to generate raw Sql. 
     * @param $csvAbsoluteFilePath Absolute file path with filename to be used to save CSV.  
     * 
     * @return void 
     */ 
    public function initDownload($csvFilename, $sql, $params = [], $csvAbsoluteFilePath = null)
    { 
        // Validation 
        $this->vSql($sql); 
        $this->vCsvFilename($csvFilename); 

        $sql = $this->generateRawSqlQuery($sql, $params);

        $this->setCsvHeaders($csvFilename);
        list($shellCommand, $tmpFilename) = $this->getShellCommand($sql, $csvAbsoluteFilePath);

        if (!is_null($csvAbsoluteFilePath)) {
            $this->useTmpFile = true;
            $this->unlink = false;
        }
        
        if ($this->useTmpFile) {
            // Execute shell command 
            // The shell command to create CSV export file. 
            shell_exec($shellCommand);
            $this->streamCsvFile($tmpFilename, $csvFilename);
        } else {
            // Execute shell command
            // The shell command echos the output. 
            echo shell_exec($shellCommand);
        }
    } 

    /** 
     * Initialise download. 
     * 
     * @param $csvAbsoluteFilePath Absolute file path with filename to be used to save CSV.  
     * @param $sql                 MySql query whose output is used to be used to generate a CSV file. 
     * @param $params              MySql query bng params used to generate raw Sql. 
     * 
     * @return void 
     */
    public function saveCsvExport($csvAbsoluteFilePath, $sql, $params = [])
    {
        // Validation 
        $this->vSql($sql); 

        $sql = $this->generateRawSqlQuery($sql, $params);

        list($shellCommand, $tmpFilename) = $this->getShellCommand($sql, $csvAbsoluteFilePath);

        // Execute shell command 
        // The shell command saves exported CSV data to provided $csvAbsoluteFilePath path. 
        shell_exec($shellCommand);
    }

    /** 
     * Generate raw Sql query from parameterised query via PDO.
     * 
     * @param $sql    MySql query whose output is used to be used to generate a CSV file. 
     * @param $params MySql query bng params used to generate raw Sql. 
     * 
     * @return string
     */ 
    private function generateRawSqlQuery($sql, $params)
    {
        if (count($params) > 0) {
            //mysqli connection
            $mysqli = mysqli_connect($this->hostname, $this->username, $this->password, $this->database);
            if (!$mysqli) {
                throw new Exception('Connection error: ' . mysqli_connect_error());
            }

            //Validate parameterised query.
            if(substr_count($sql, ':') !== count($params)) {
                throw new Exception("Parameterised query has mismatch in number of params");
            }
            $paramKeys = array_keys($params);
            $paramPos = [];
            foreach ($paramKeys as $value) {
                if (substr_count($sql, $value) > 1) {
                    throw new Exception("Parameterised query has more than one occurance of param '{$value}'");
                }
                $paramPos[$value] = strpos($sql, $value);
            }
            foreach ($paramPos as $key => $value) {
                if (substr($sql, $value, strlen($key)) !== $key) {
                    throw new Exception("Invalid param key '{$key}'");
                }
            }

            //Generate bind params 
            $bindParams = [];
            foreach ($params as $key => $values) {
                if (is_array($values)) {
                    $tmpParams = [];
                    $count = 1;
                    foreach($values as $value) {
                        if (is_array($value)) {
                            throw new Exception("Invalid params for key '{$key}'");
                        }
                        $newKey = $key.$count;
                        if (in_array($newKey, $paramKeys)) {
                            throw new Exception("Invalid parameterised params '{$newKey}'");
                        }
                        $tmpParams[$key.$count++] = $value;
                    }
                    $sql = str_replace($key, implode(', ',array_keys($tmpParams)), $sql);
                    $bindParams = array_merge($bindParams, $tmpParams);
                } else {
                    $bindParams[$key] = $values;
                }
            }

            //Replace Paremeteried values.
            foreach ($bindParams as $key => $value) {
                if (!ctype_digit($value)) {
                    $value = "'" . mysqli_real_escape_string($mysqli, $value) . "'";
                }
                $sql = str_replace($key, $value, $sql);
            }

            // Close mysqli connection.
            mysqli_close($mysqli);
        }

        return $sql;
    }

    /** 
     * Set CSV file headers
     * 
     * @param $csvFilename Name to be used to save CSV file on client machine.  
     * 
     * @return void 
     */ 
    private function setCsvHeaders($csvFilename)
    {
        // CSV headers 
        header("Content-type: text/csv"); 
        header("Content-Disposition: attachment; filename={$csvFilename}"); 
        header("Pragma: no-cache"); 
        header("Expires: 0");
    }

    /** 
     * Executes SQL and saves output to a temporary file on server end. 
     * 
     * @param $sql                 MySql query whose output is used to be used to generate a CSV file. 
     * @param $csvAbsoluteFilePath (Optional)Absolute file path with filename to be used to save CSV.  
     * 
     * @return array
     */ 
    private function getShellCommand($sql, $csvAbsoluteFilePath = null) 
    { 
        // Validation 
        $this->vSql($sql);

        // Shell command. 
        $shellCommand = 'mysql '
            . '--host='.escapeshellarg($this->hostname).' '
            . '--user='.escapeshellarg($this->username).' ' 
            . '--password='.escapeshellarg($this->password).' '
            . '--database='.escapeshellarg($this->database).' ' 
            . '--execute='.escapeshellarg($sql).' '
            . '| sed -e \'s/"/""/g ; s/\t/","/g ; s/^/"/g ; s/$/"/g\'';

        if (!is_null($csvAbsoluteFilePath)) {
            $tmpFilename = $csvAbsoluteFilePath;
            $shellCommand .= ' > '.escapeshellarg($tmpFilename);
        } elseif ($this->useTmpFile) {
            // Generate temporary file for storing output of shell command on server side. 
            $tmpFilename = tempnam(sys_get_temp_dir(), 'CSV');
            $shellCommand .= ' > '.escapeshellarg($tmpFilename);
        } else {
            $tmpFilename = null;
            $shellCommand .= ' 2>&1';
        }

        return [$shellCommand, $tmpFilename];
    } 
    /** 
     * Stream CSV file to client. 
     * 
     * @param $fileLocation Abolute file location of CSV file. 
     * @param $csvFilename  Name to be used to save CSV file on client machine. 
     * 
     * @return void 
     */ 
    private function streamCsvFile($fileLocation, $csvFilename) 
    { 
        // Validation 
        $this->vFileLocation($fileLocation); 
        $this->vCsvFilename($csvFilename); 

        // Start streaming
        $srcStream = fopen($fileLocation, 'r');
        $destStream = fopen('php://output', 'w');

        stream_copy_to_stream($srcStream, $destStream);

        fclose($destStream);
        fclose($srcStream);

        if ($this->unlink && !unlink($fileLocation)) { // Unable to delete file 
            //handle error via logs. 
        } 
    } 
}
