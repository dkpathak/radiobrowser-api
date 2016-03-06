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
    $result = mysql_query('SELECT Tags FROM Station');
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    $tags_new = array();
    while ($row = mysql_fetch_assoc($result)) {
        $tag_string = $row['Tags'];
        // $tag_string = str_replace(',', ' ', $tag_string);
        // $tag_string = str_replace(';', ' ', $tag_string);

        $tag_array = explode(',', $tag_string);
        foreach ($tag_array as $tag) {
            $tag_clean = strtolower(trim($tag));
            if ($tag_clean !== '') {
                if (!in_array($tag_clean, $tags_new)) {
                    array_push($tags_new, $tag_clean);
                }
            }
        }
    }
    // generate old list of tags
    $result = mysql_query('SELECT TagName FROM TagCache');
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    $tags_old = array();
    while ($row = mysql_fetch_row($result)) {
        array_push($tags_old, $row[0]);
    }

    // compare the arrays and update TagCache
    // remove unused tags
    foreach ($tags_old as $tag) {
        if (!in_array($tag, $tags_new)) {
            echo 'removed old:'.$tag.'<br/>';
            mysql_query("DELETE FROM TagCache WHERE TagName='".escape_string($tag)."'");
        }
    }
    // add new tags
    foreach ($tags_new as $tag) {
        if (!in_array($tag, $tags_old)) {
            echo 'added new:'.$tag.'<br/>';
            mysql_query("INSERT INTO TagCache (TagName) VALUES ('".escape_string($tag)."')");
        }
    }
}
