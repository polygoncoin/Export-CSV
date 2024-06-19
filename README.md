# **PHP-Download-CSV**
 
Export / Download MySQL query results as a CSV file
 

When it comes to Download CSV, most of the time developer faces issue of memory limit in PHP; especially when supporting downloads of more than 30,000 records at a time.
 

Below class solves this issue by executing shell command for MySql Client installed on the server from PHP script. Using this class one can download all the records in one go. There is no limit to number of rows returned by SQL query.<br>
 

## Examples
 
### To download the export CSV
 

    require "ExportCSV.php"
    
    define('HOSTNAME', '127.0.0.1'); 
    define('USERNAME', 'username'); 
    define('PASSWORD', 'password'); 
    define('DATABASE', 'database'); 
    
    $sql = "
        SELECT
            column1 as COLUMN1,
            column2 as COLUMN2,
            column3 as COLUMN3,
            column4 as COLUMN4
        FROM
            TABLE_NAME
        WHERE
            column5 = :column5
            column6 LIKE CONCAT('%' , :column6, '%');
            column7 IN (:column7);
    ";
    
    $params = [
        ':column5' => 'column5_value',
        ':column6' => 'column6_search_value',
        ':column7' => [
            'column7_value1',
            'column7_value2',
            'column7_value3'
        ]
    ];
    
    $csvFilename = 'export.csv'; 
    
    try { 
      $exportCSV = new ExportCSV(); 
      $exportCSV->connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);
      $exportCSV->useTmpFile = false; // defaults true for large data export.
      $exportCSV->initDownload($csvFilename, $sql, $params);
    } catch (\Exception $e) { 
      echo $e->getMessage(); 
    } 

### To export the CSV results in a file.
 

    $csvAbsoluteFilePath = '/<folder path>/<filename>.csv'; 
    
    try { 
      $exportCSV = new ExportCSV(); 
      $exportCSV->connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);
      $exportCSV->saveCsvExport($csvAbsoluteFilePath, $sql, $params); 
    } catch (\Exception $e) { 
      echo $e->getMessage(); 
    }

### To download as well as export the CSV results in a file.
 

    $csvAbsoluteFilePath = '/<folder path>/<filename>.csv'; 
    
    try { 
      $exportCSV = new ExportCSV();
      $exportCSV->connect(HOSTNAME, USERNAME, PASSWORD, DATABASE);
      $exportCSV->initDownload($csvFilename, $sql, $params, $csvAbsoluteFilePath);
    } catch (\Exception $e) { 
      echo $e->getMessage(); 
    } 

## Compression
 
To enable compression for downloading dynamically generated CSV files in NGINX if the browser supports compression, one can use the gzip\_types directive in the NGINX configuration file.
 

The gzip\_types directive is used to specify which MIME types should be compressed.
 
Here's an example of how you can enable compression for downloading dynamically generated CSV files in NGINX:
 

    http {
        # ...
        gzip on;
        gzip_types text/plain text/csv;
        # ...
    }