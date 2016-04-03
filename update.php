<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

updateCaches();

function updateCaches()
{
    updateCacheTags();
}

function updateCacheTags()
{
    // This is for updating caches
    require 'db.php';
    openDB();

    // generate new list of tags
    $result = mysql_query('SELECT Tags, StationID FROM Station');
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    $tags_new = array();
    while ($row = mysql_fetch_assoc($result)) {
        $url = trim($row['Url']);
        $tag_string = $row['Tags'];
        // $tag_string = str_replace(',', ' ', $tag_string);
        // $tag_string = str_replace(';', ' ', $tag_string);

        $tag_array = explode(',', $tag_string);
        $tag_array_corrected = array();
        foreach ($tag_array as $tag) {
            $tag_clean = strtolower(trim($tag));

            $tag_corrected = trim($tag);
            if ($tag_clean === 'sports') {
                $tag_corrected = 'sport';
            }
            if ($tag_clean === 'worldmusic' || $tag_clean === 'world') {
                $tag_corrected = 'world music';
            }
            if ($tag_clean === 'hip-hop' || $tag_clean === 'hip hop') {
                $tag_corrected = 'hiphop';
            }
            if ($tag_clean === 'top40' || $tag_clean === 'top-40') {
                $tag_corrected = 'top 40';
            }
            if ($tag_clean === 'top10' || $tag_clean === 'top-10') {
                $tag_corrected = 'top 10';
            }
            if ($tag_clean === 'top100' || $tag_clean === 'top-100') {
                $tag_corrected = 'top 100';
            }
            if ($tag_clean === 'catolic') {
                $tag_corrected = 'catholic';
            }
            if ($tag_clean === 'religous' || $tag_clean === 'religious') {
                $tag_corrected = 'religion';
            }
            if ($tag_clean === 'pop music') {
                $tag_corrected = 'pop';
            }
            if ($tag_clean === 'active hits') {
                $tag_corrected = 'hits';
            }
            if ($tag_clean === 'newage') {
                $tag_corrected = 'new age';
            }
            if ($tag_clean === 'local service') {
                $tag_corrected = 'local programming';
            }
            if ($tag_clean === 'various') {
                $tag_corrected = 'variety';
            }
            if ($tag_clean === 'musik') {
                $tag_corrected = 'music';
            }
            if ($tag_clean === 'nachrichten') {
                $tag_corrected = 'news';
            }

            $tag_corrected = str_replace('/', ',', $tag_corrected);

            array_push($tag_array_corrected, $tag_corrected);
            if ($tag_clean !== '') {
                if (!array_key_exists($tag_clean, $tags_new)) {
                    $tags_new[$tag_clean] = (int) 1;
                } else {
                    $tags_new[$tag_clean] = (int) ($tags_new[$tag_clean] + 1);
                }
            }
        }

        if ($url !== $row['Url']) {
            echo "changed url:'".escape_string($url)."' from '".$row['Url']."'<br/>";
            mysql_query("UPDATE Station SET Url='".escape_string($url)."' WHERE StationID=".$row['StationID']);
        }

        $tag_string_corrected = implode(',', $tag_array_corrected);
        if (strcmp($tag_string_corrected, $tag_string) !== 0) {
            echo "Try correcting tags:'".$tag_string."' -> '".$tag_string_corrected."'<br/>";
            mysql_query("UPDATE Station SET Tags='".escape_string($tag_string_corrected)."' WHERE StationID=".$row['StationID']);
        }
    }
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

    mysql_query("DELETE FROM Station WHERE Name=''");
}
