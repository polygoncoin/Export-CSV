<?php
require_once __DIR__ . '/Autoload.php'; // phpcs:ignore

use ExportCSV\ExportCSV;

define(constant_name: 'HOSTNAME', value: '127.0.0.1');
define(constant_name: 'USERNAME', value: 'root');
define(constant_name: 'PASSWORD', value: 'shames11');
define(constant_name: 'DATABASE', value: 'global');

$sql = "
    SELECT
        *
    FROM
        m001_master_clients
";

$params = [];

$csvFilename = 'export.csv';

try {
    $exportCSV = new ExportCSV(dbType: 'MySQL');
    $exportCSV->connect(
        hostname: HOSTNAME, 
        username: USERNAME, 
        password: PASSWORD, 
        database: DATABASE
    );
    $exportCSV->useTmpFile = false; // defaults true for large data export.
    $exportCSV->initDownload(
        csvFilename: $csvFilename, 
        sql: $sql, 
        params: $params
    );
} catch (\Exception $e) {
    echo $e->getMessage();
}
