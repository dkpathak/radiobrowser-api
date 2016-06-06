<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

require 'db.php';
$db = openDB();

try {
    updateCaches($db);
} catch (PDOException $ex) {
    echo 'An Error occured!'.$ex->getMessage();
}

function updateCaches($db)
{
  ini_set("user_agent","VLC/2.2.2 LibVLC/2.2.2");
    dbCleanup($db);

    correctCountries($db);
    updateCacheTags($db);

    updateWebpages($db);

    byStationCleanup($db, 50);

    // SLOW methods, for whole database
    // updateStationClickAll($db);
    // checkStationUrlsAll($db);
    // updateFaviconAll($db);
}

function byStationCleanup($db, $limit)
{
    ini_set('default_socket_timeout', 10);

    $select_stmt = $db->query('SELECT StationID, Name, Url FROM Station ORDER BY LastCheckTime ASC, Votes DESC LIMIT '.$limit);
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }
    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $stationid = $row["StationID"];
        $url = trim($row['Url']);

        echo 'Checking: '.$row['Name'].' -> '.$row['Url']."..\n";
        $log = array();
        checkStationConnectionById($db, $stationid, $url, $bitrate, $codec, $log);
        updateStationClickById($db, $stationid);
        print_r($log);
        echo "\n";
    }
}

function dbCleanup($db){
    // delete clicks older than 30 days
    $db->query('DELETE FROM StationClick WHERE TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))>60*60*24*30;');
    // delete clicks older than 30 days
    $db->query('DELETE FROM StationHistory WHERE TIME_TO_SEC(TIMEDIFF(Now(),Creation))>60*60*24*30;');
}

function isIconLoadable($url){
    if ($url === null)
        return false;
    if (!hasCorrectScheme($url)){
        return false;
    }

    @$headers = get_headers($url);
    if ($headers === false){
        return false;
    }

    foreach ($headers as $header)
    {
        if (strpos($header,"Content-Type: image",0) === 0)
        {
          $content = file_get_contents($url);
          if ($content !== false)
          {
              $img = @imagecreatefromstring($content);
              if ($img !== false)
              {
                  imagedestroy($img);
                  return true;
              }
          }
        }
    }

    return false;
}

function checkUrlHtmlContent($url){
    if (!@$fp = fopen($url, 'r')) {
        return false;
    }

    $meta = stream_get_meta_data($fp);
    if (isset($meta['wrapper_data']))
    {
      $data = $meta['wrapper_data'];
      foreach ($data as $item) {
        if (strpos($item,"Content-Type: text/html") === 0)
        {
          fclose($fp);
          return true;
        }
      }
    }

    fclose($fp);
    return false;
}

function updateFaviconAll($db)
{
    // generate new list of tags
    $select_stmt = $db->query('SELECT StationID, Name, Homepage, Favicon FROM Station WHERE Favicon="" ORDER BY Creation ASC');
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }
    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $hp = trim($row['Homepage']);
        $icon = trim($row['Favicon']);
        $icon = fixFavicon($icon, $hp);

        if ($icon !== $row['Favicon']) {
            echo 'fix favicon ('.$row['StationID'].' - '.$row['Name'].'):'.$row['Favicon'].' -> '.$icon." <br/>\n";
            $stmt = $db->prepare('UPDATE Station SET Favicon=:favicon WHERE StationID='.$row['StationID']);
            $stmt->execute(['favicon' => $icon]);
        }
    }
}

function fixFavicon($icon, $hp) {
    echo "|";
    // echo "-----------------------<br/>\n";
    // echo "checking : ".$row["Name"]." HP=".$hp." ICO=".$icon."<br/>\n";

    // if icon link not ok, remove it
    if ($icon !== '') {
        if (!isIconLoadable($icon)) {
            $icon = '';
            echo "a";
        }
    }

    // if icon link empty, try to fix it
    if ($icon === '') {
        if (hasCorrectScheme($hp)){
            // try default favicon pathinfo
            $base = getBaseUrl($hp);
            $icon = $base.'/favicon.ico';
            if ($icon != null){
                if (!isIconLoadable($icon)) {
                    $icon = '';
                    echo "b";
                }
            }else{
                $icon = '';
                echo "c";
            }

            if ($icon === ''){
                // get hp
                if (checkUrlHtmlContent($hp)){
                    $hpContent = getLinkContent($hp);
                    if ($hpContent !== null){
                        $images = extractIconLink($hpContent,$base, $logExtractIcon);
                        if (count($images) > 0){
                            $icon = $images[0];
                        }else{
                            $icon = "";
                        }
                        // echo "extracted:".$icon."\n";
                        // if (!isIconLoadable($icon)) {
                        //     $icon = '';
                        //     echo "d";
                        // }
                    }
                }
            }
        }
    }

    if ($icon !== ""){
      echo "+";
    }
    return $icon;
}

function updateWebpages($db)
{
    // generate new list of tags
    $select_stmt = $db->query('SELECT StationID, Name, Homepage FROM Station');
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }

    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $url = trim($row['Homepage']);
        if ($url !== '') {
            $url = FixUrl($url);
            if ($url !== $row['Homepage']) {
                echo 'fix homepage ('.$row['StationID'].' - '.$row['Name'].'):'.$row['Homepage'].' -> '.$url.'<br/>';
                $stmt = $db->prepare('UPDATE Station SET Homepage=:homepage WHERE StationID='.$row['StationID']);
                $stmt->execute(['homepage' => $url]);
            }
        }
    }
}

function updateStationClickById($db, $stationid)
{
    // update stationclick count (last day, and the day before, then diff them)
    $db->query('UPDATE Station AS st SET clickcount=(SELECT COUNT(StationClick.StationID) FROM StationClick WHERE StationClick.StationID='.$stationid.' AND TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))<60*60*24*1) WHERE st.StationID='.$stationid.';');
    $db->query('UPDATE Station AS st SET clicktrend=clickcount-(SELECT count(StationClick.StationID) FROM StationClick WHERE StationClick.StationID='.$stationid.' AND TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))>60*60*24*1 AND TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))<60*60*24*2) WHERE st.StationID='.$stationid.';');

    // update last click TIMESTAMP
    $db->query('UPDATE Station AS st SET ClickTimestamp=(SELECT MAX(StationClick.ClickTimestamp) FROM StationClick WHERE StationClick.StationID='.$stationid.') WHERE st.StationID='.$stationid.';');
}

function updateStationClickAll($db)
{
    // update stationclick count (last day, and the day before, then diff them)
    $db->query('UPDATE Station AS st SET clickcount=(SELECT COUNT(StationClick.StationID) FROM StationClick WHERE StationClick.StationID=st.StationID AND TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))<60*60*24*1);');
    $db->query('UPDATE Station AS st SET clicktrend=clickcount-(SELECT count(StationClick.StationID) FROM StationClick WHERE StationClick.StationID=st.StationID AND TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))>60*60*24*1 AND TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))<60*60*24*2);');

    // update last click TIMESTAMP
    $db->query('UPDATE Station AS st SET ClickTimestamp=(SELECT MAX(StationClick.ClickTimestamp) FROM StationClick WHERE StationClick.StationID=st.StationID);');
}

function updateCacheTags($db)
{
    // generate new list of tags
    $select_stmt = $db->query('SELECT Name, Url, Tags, StationID, LastCheckOK FROM Station');
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }

    $tags_new = array();
    $tags_new_working = array();
    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = str_replace("\t", ' ', trim($row['Name']));
        if ($name !== $row['Name']) {
            echo "fixed name:'".$name."' from '".$row['Name']."'<br/>";
            $stmt = $db->prepare('UPDATE Station SET Name=:name WHERE StationID='.$row['StationID']);
            $stmt->execute(['name' => $name]);
        }

        $url = str_replace("\t", ' ', trim($row['Url']));
        if ($url !== $row['Url']) {
            echo "fixed url:'".$url."' from '".$row['Url']."'<br/>";
            $stmt = $db->prepare('UPDATE Station SET Url=:url WHERE StationID='.$row['StationID']);
            $stmt->execute(['url' => $url]);
        }

        $tag_string = strtolower($row['Tags']);
        $tag_array = explode(',', $tag_string);
        $tag_array_corrected = array();
        foreach ($tag_array as $tag) {
            // correct the tag
            $tag_corrected = correctTag(trim($tag));
            $tag_corrected = str_replace('/', ',', $tag_corrected);
            array_push($tag_array_corrected, $tag_corrected);

            // count tag occurences
            if ($tag_corrected !== '') {
                if (!array_key_exists($tag_corrected, $tags_new)) {
                    $tags_new[$tag_corrected] = (int) 0;
                    $tags_new_working[$tag_corrected] = (int) 0;
                }
                $tags_new[$tag_corrected] = (int) ($tags_new[$tag_corrected] + 1);
                if ($row["LastCheckOK"] === "1"){
                    $tags_new_working[$tag_corrected] = (int) ($tags_new_working[$tag_corrected] + 1);
                }
            }
        }

        $tag_string_corrected = implode(',', $tag_array_corrected);
        if (strcmp($tag_string_corrected, $tag_string) !== 0) {
            echo "Try correcting tags:'".$tag_string."' -> '".$tag_string_corrected."'<br/>";
            $stmt = $db->prepare('UPDATE Station SET Tags=:tags WHERE StationID='.$row['StationID']);
            $stmt->execute(['tags' => $tag_string_corrected]);
        }
    }

    // generate old list of tags
    $result = $db->query('SELECT TagName, StationCount FROM TagCache');
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    $tags_old = array();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $tags_old[$row['TagName']] = (int) $row['StationCount'];
    }

    // compare the arrays and update TagCache
    // remove unused tags
    foreach ($tags_old as $tag => $count) {
        if (!array_key_exists($tag, $tags_new)) {
            echo 'removed old:'.$tag.'<br/>';
            $stmt = $db->prepare('DELETE FROM TagCache WHERE TagName=:tag');
            $stmt->execute(['tag' => $tag]);
        }
    }
    // add new tags
    foreach ($tags_new as $tag => $count) {
        if (!array_key_exists($tag, $tags_old)) {
            echo 'added new:'.$tag.'<br/>';
            $stmt = $db->prepare('INSERT INTO TagCache (TagName,StationCount, StationCountWorking) VALUES (:tag,:count, :countworking)');
            $stmt->execute(['tag' => $tag, 'count' => $count, 'countworking' => $tags_new_working[$tag]]);
        } else {
            if ($count !== $tags_old[$tag]) {
                echo 'updated:'.$tag.' from '.$tags_old[$tag].' to '.$count.'<br/>';
                $stmt = $db->prepare('UPDATE TagCache SET StationCount=:count, StationCountWorking=:countworking WHERE TagName=:tag');
                $stmt->execute(['tag' => $tag, 'count' => $count, 'countworking' => $tags_new_working[$tag]]);
            }
        }
    }
}

function correctCountries($db){
  $db->query('UPDATE Station SET Country="United States of America" where Country="United States";');
  $db->query('UPDATE Station SET Country="United States of America" where Country="USA";');
}

function correctTag($tag)
{
    if ($tag === 'sports') {
        return 'sport';
    }
    if ($tag === 'worldmusic' || $tag === 'world') {
        return 'world music';
    }
    if ($tag === 'hip-hop' || $tag === 'hip hop') {
        return 'hiphop';
    }
    if ($tag === 'top40' || $tag === 'top-40') {
        return 'top 40';
    }
    if ($tag === 'top10' || $tag === 'top-10') {
        return 'top 10';
    }
    if ($tag === 'top100' || $tag === 'top-100') {
        return 'top 100';
    }
    if ($tag === 'catolic') {
        return 'catholic';
    }
    if ($tag === 'religous' || $tag === 'religious') {
        return 'religion';
    }
    if ($tag === 'pop music') {
        return 'pop';
    }
    if ($tag === 'classical music') {
        return 'classical';
    }
    if ($tag === 'active hits') {
        return 'hits';
    }
    if ($tag === 'newage') {
        return 'new age';
    }
    if ($tag === 'local service') {
        return 'local programming';
    }
    if ($tag === 'various') {
        return 'variety';
    }
    if ($tag === 'musik') {
        return 'music';
    }
    if ($tag === 'nachrichten') {
        return 'news';
    }

    return $tag;
}
