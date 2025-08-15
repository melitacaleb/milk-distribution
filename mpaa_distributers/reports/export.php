<?php
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=mpaa_report_".date('Ymd').".xls");

// Get report data
$data = getReportData($conn, $_GET['type'], $_GET['start_date'], $_GET['end_date']);

echo "Mpaa Distributers Report\n";
echo "Period: ".$_GET['start_date']." to ".$_GET['end_date']."\n\n";

// Output data as Excel
foreach($data as $row) {
    echo implode("\t", $row)."\n";
}
?>