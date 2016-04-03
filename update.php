<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

$db = new PDO('mysql:host=localhost;dbname=radio', 'root', '');

try {
    updateCaches($db);
} catch (PDOException $ex) {
    echo 'An Error occured!'.$ex->getMessage();
}

function updateCaches($db)
{
    updateCacheTags($db);
    updateStationClick($db);
}

function updateStationClick($db)
{
    // delete clicks older than 30 days
    $db->query('DELETE FROM StationClick WHERE TIME_TO_SEC(TIMEDIFF(Now(),ClickTimeStamp))>60*60*24*30;');
}

function updateCacheTags($db)
{
    // Delete empty stations
    $db->query("DELETE FROM Station WHERE Name=''");

    // generate new list of tags
    $select_stmt = $db->query('SELECT Name, Url, Tags, StationID FROM Station');
    if (!$select_stmt) {
        echo str(mysql_error());
        exit;
    }

    $tags_new = array();
    while ($row = $select_stmt->fetch(PDO::FETCH_ASSOC)) {
        $name = str_replace("\t", ' ', trim($row['Name']));
        if ($name !== $row['Name']) {
            echo "fixed name:'".escape_string($name)."' from '".$row['Name']."'<br/>";
            $stmt = $db->prepare('UPDATE Station SET Name=:name WHERE StationID='.$row['StationID']);
            $stmt->execute(['name' => $name]);
        }

        $url = str_replace("\t", ' ', trim($row['Url']));
        if ($url !== $row['Url']) {
            echo "fixed url:'".escape_string($url)."' from '".$row['Url']."'<br/>";
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
            // if ($tag_corrected !== '') {
            //     if (!array_key_exists($tag_corrected, $tags_new)) {
            //         $tags_new[$tag_corrected] = (int) 1;
            //     } else {
            //         $tags_new[$tag_corrected] = (int) ($tags_new[$tag_corrected] + 1);
            //     }
            // }
        }

        $tag_string_corrected = implode(',', $tag_array_corrected);
        if (strcmp($tag_string_corrected, $tag_string) !== 0) {
            echo "Try correcting tags:'".$tag_string."' -> '".$tag_string_corrected."'<br/>";
            $stmt = $db->prepare('UPDATE Station SET Tags=:tags WHERE StationID='.$row['StationID']);
            $stmt->execute(['tags' => $tag_string_corrected]);
        }
    }

    return;

    // generate old list of tags
    $result = mysql_query('SELECT TagName, StationCount FROM TagCache');
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    $tags_old = array();
    while ($row = mysql_fetch_row($result)) {
        $tags_old[$row[0]] = (int) $row[1];
    }

    // compare the arrays and update TagCache
    // remove unused tags
    foreach ($tags_old as $tag => $count) {
        if (!array_key_exists($tag, $tags_new)) {
            echo 'removed old:'.$tag.'<br/>';
            mysql_query("DELETE FROM TagCache WHERE TagName='".escape_string($tag)."'");
        }
    }
    // add new tags
    foreach ($tags_new as $tag => $count) {
        if (!array_key_exists($tag, $tags_old)) {
            echo 'added new:'.$tag.'<br/>';
            mysql_query("INSERT INTO TagCache (TagName,StationCount) VALUES ('".escape_string($tag)."',".$count.')');
        } else {
            if ($count !== $tags_old[$tag]) {
                echo 'updated:'.$tag.' from '.$tags_old[$tag].' to '.$count.'<br/>';
                mysql_query('UPDATE TagCache SET StationCount='.$count." WHERE TagName='".escape_string($tag)."'");
            }
        }
    }
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
