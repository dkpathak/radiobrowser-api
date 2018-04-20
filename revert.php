<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require 'db.php';
$db = openDB();

try {
    cleanAllStations($db);
} catch (PDOException $ex) {
    echo 'An Error occured!'.$ex->getMessage();
}

function cleanAllStations($db)
{
    ini_set('default_socket_timeout', 10);
    $needle ="Bull headed German%";

    $select_stmt = $db->query('SELECT StationID, Name, Url FROM Station WHERE Name LIKE "'.$needle.'"');
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }
    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stationid = $row["StationID"];
        $url = trim($row['Url']);

        echo 'Checking: '.$row['Url']."..\n";
        echo 'Name: '.$row['Name']."\n";
        revertSingleStation($db, $stationid,$needle);
        echo "\n\n";
    }
}

function revertSingleStation($db,$stationid, $needle){
    $select_stmt = $db->query('SELECT StationID, Name, Url, Creation,Favicon,Homepage,StationChangeID,ChangeUUID,Country,Subcountry,Language,Tags FROM StationHistory WHERE StationID='.$stationid.' AND Name NOT LIKE "'.$needle.'" ORDER BY Creation DESC LIMIT 1');
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }
    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stationid = $row["StationID"];

        echo "revert to..\n";
        echo "Name: ".$row["Name"]."\n";
        echo "Homepage: ".$row["Homepage"]."\n";
        echo "Favicon: ".$row["Favicon"]."\n";
        echo "Creation: ".$row["Creation"]."\n";

        $stmt = $db->prepare('UPDATE Station SET Homepage=:homepage,Favicon=:favicon,Name=:name,ChangeUUID=:changeid,Country=:country,Subcountry=:state,Language=:language,Tags=:tags WHERE StationID=:id');
        $result = $stmt->execute(['id'=>$stationid,'homepage'=>$row["Homepage"],'name'=>$row["Name"],'favicon'=>$row["Favicon"],'changeid'=>$row["ChangeUUID"],
        'country'=>$row["Country"],'state'=>$row["Subcountry"],'language'=>$row["Language"],'tags'=>$row["Tags"]]);
        if ($result){
            echo "OK\n";
        }
        $stmt = $db->prepare('DELETE FROM StationHistory WHERE StationID=:id AND Creation>:creation');
        $result = $stmt->execute(['id'=>$stationid,'creation'=>$row["Creation"]]);
        if ($result){
            echo "OK\n";
        }
    }
}