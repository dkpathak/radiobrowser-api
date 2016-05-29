<?php

if (!isset($_REQUEST['format']) || !isset($_REQUEST['stationid'])) {
    echo 'parameters missing!';
    exit();
}

require 'db.php';
$db = openDB();

$stationid = $_REQUEST['stationid'];
$format = $_REQUEST['format'];

$stmt = $db->prepare('SELECT Name, Url FROM Station WHERE StationID=:stationid');
$stmt->execute(['stationid'=>$stationid]);
if ($stmt->rowCount() !== 1) {
    http_response_code(404);
    exit();
}

$row = $stmt->fetch(PDO::FETCH_ASSOC);
$url = $row['Url'];
$stationname = $row['Name'];

$audiofile = checkStation($url,$bitrate,$codec,$log);

if ($audiofile !== false) {
    if ($format == 'xml') {
        header('Content-Type: text/xml');
        echo '<?xml version="1.0"?>';
        echo "<result><station id='".$stationid."' url='".$audiofile."'/></result>";
        clickedStationID($db, $stationid);
    } elseif ($format == 'json') {
        header('Content-Type: application/json');
        echo '[{';
        echo "\"ok\":\"true\",";
        echo "\"message\":\"retrieved station url successfully\",";
        echo "\"id\":\"$stationid\",";
        echo "\"name\":\"$stationname\",";
        echo '"url":"'.$audiofile.'"';
        echo '}]';
        clickedStationID($db, $stationid);
    } elseif ($format == 'pls') {
        //header('content-type: audio/x-scpls');
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=radio.pls');
        header('Content-Transfer-Encoding: chunked'); //changed to chunked
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');

        echo "[playlist]\n";

        echo 'File1='.$audiofile."\n";
        echo 'Title1='.$stationname;
        clickedStationID($db, $stationid);
    } else {
        echo 'unknown format';
    }
} else {
    http_response_code(404);
}
