<?php

require_once __DIR__ . '/Config.php'; // phpcs:ignore
require_once __DIR__ . '/AutoloadExportCSV.php'; // phpcs:ignore

use ExportCSV\ExportCSV;

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
