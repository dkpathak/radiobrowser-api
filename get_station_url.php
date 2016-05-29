<?php

if (!isset($_REQUEST['format']) || !isset($_REQUEST['stationid'])) {
    echo 'parameters missing!';
    exit();
}

require 'db.php';
$db = openDB();

$stationid = $_REQUEST['stationid'];
$foundStation = false;

{
    $stmt = $db->prepare('SELECT Name, Url FROM Station WHERE StationID=:stationid');
    $result = $stmt->execute(['stationid'=>$stationid]);
    if (!$result) {
        exit();
    }

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = $row['Url'];
        $stationname = $row['Name'];
        $foundStation = true;
        break;
    }
}

$format = $_REQUEST['format'];
if ($foundStation !== true) {
    http_response_code(404);
    exit();
}
$str_arr = explode("\?", $url);
if (count($str_arr) > 1) {
    $extension = strtolower(substr($str_arr[0], -4));
} else {
    $extension = strtolower(substr($url, -4));
}

$audiofile = checkStation($url,$bitrate,$codec,$log);

//print("audiofile:".$audiofile);
if ($audiofile !== false) {
    $extension = strtolower(substr($audiofile, -4));

    // shoutcast handling
    // if (substr($audiofile, -1) == '/') {
    //     $audiofile .= ';stream.mp3';
    // }
    // if (substr_count($audiofile, '/') == 2) {
    //     $audiofile .= '/;stream.mp3';
    // }
    if ($format == 'xml') {
        header('Content-Type: text/xml');
        echo '<?xml version="1.0"?>';
        echo "<result><station id='".$stationid."' url='".$audiofile."'/></result>";
        clickedStationID($db, $stationid);
    } elseif ($format == 'json') {
        header('Content-Type: application/json');
        echo '[{';
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
