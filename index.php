<?php
require 'db.php';
if (isset($_SERVER["CONTENT_TYPE"])){
    if (strpos($_SERVER["CONTENT_TYPE"],"application/json") === 0)
    {
        $rawData = file_get_contents('php://input');
        $JSON = json_decode($rawData, true);
    }
}

function getParameter($paramName, $defaultValue){
    global $JSON;
    if (isset($JSON[$paramName])) {
        return $JSON[$paramName];
    }
    if (isset($_GET[$paramName])) {
        return $_GET[$paramName];
    }
    if (isset($_POST[$paramName])) {
        return $_POST[$paramName];
    }
    return $defaultValue;
}

function convertToBool($value){
    if ($value === true || $value === 'true' || $value === '1' || $value === 1){
        return true;
    }
    return false;
}

if (isset($_GET['action'])) {
    // open database
    $db = openDB();
    // check parameters, set default values
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $term = getParameter('term','');
    $offset = intval(getParameter('offset', '0'));
    $limit = intval(getParameter('limit', '100000'));
    $reverse = getParameter('reverse',"false");
    $order = getParameter('order',"");
    $stationid = isset($_GET['stationid']) ? $_GET['stationid'] : null;
    $stationchangeid = isset($_GET['stationchangeid']) ? $_GET['stationchangeid'] : null;
    $action = $_GET['action'];
    $hideBroken = convertToBool(getParameter('hidebroken', 'false'));
    $stationuuid = getParameter('stationuuid','');
    $lastchangeuuid = getParameter('lastchangeuuid',null);
    $lastcheckuuid = getParameter('lastcheckuuid',null);
    $bitrateMin = intval(getParameter('bitrateMin','0'));
    $bitrateMax = intval(getParameter('bitrateMax','1000000'));
    $seconds    = intval(getParameter('seconds','0'));

    $name = getParameter('name', null);
    $nameExact = convertToBool(getParameter('nameExact', 'false'));
    $url = getParameter('url', null);
    $homepage = getParameter('homepage', null);
    $favicon = getParameter('favicon', null);
    $country = getParameter('country', null);
    $countrycode = getParameter('countrycode', null);
    $countryExact = convertToBool(getParameter('countryExact', 'false'));
    $state = getParameter('state', null);
    $stateExact = convertToBool(getParameter('stateExact', 'false'));
    $language = getParameter('language', null);
    $languageExact = convertToBool(getParameter('languageExact', 'false'));
    $tags = getParameter('tags', null);
    $tag = getParameter('tag', null);
    $tagList = getParameter('tagList', null);
    $tagExact = convertToBool(getParameter('tagExact', 'false'));

    if ($action == 'tags') {
        print_n_to_m($db, $format, 'TagCache', 'TagName', 'tag', $term, $order, $reverse, $hideBroken);
    }elseif ($action == 'countries') {
        print_1_n($db, $format, 'Country', 'country', $term, $order, $reverse, $hideBroken);
    }elseif ($action == 'codecs') {
        print_1_n($db, $format, 'Codec', 'codec', $term, $order, $reverse, $hideBroken);
    }elseif ($action == 'states') {
        print_states($db, $format, $term, $country, $order, $reverse, $hideBroken);
    }elseif ($action == 'languages') {
        //print_1_n($db, $format, 'Language', 'language', $term, $order, $reverse, $hideBroken);
        print_n_to_m($db, $format, 'LanguageCache', 'LanguageName', 'language', $term, $order, $reverse, $hideBroken);
    }elseif ($action == 'stats') {
        print_stats($db, $format);
    }elseif ($action == 'data_search_advanced') {
        print_stations_list_data_advanced($db, $format, $name, $nameExact, $country, $countryExact, $state, $stateExact, $language, $languageExact, $tag, $tagExact, $tagList, $bitrateMin, $bitrateMax, $order, $reverse, $hideBroken, $offset, $limit);
    }elseif ($action == 'data_search_topvote') {
        print_stations_list_data_all($db, $format, "votes", "true", $offset, $limit);
    }elseif ($action == 'data_search_topclick') {
        print_stations_list_data_all($db, $format, "clickcount", "true", $offset, $limit);
    }elseif ($action == 'data_search_lastclick') {
        print_stations_list_data_all($db, $format, "clicktimestamp", "true", $offset, $limit);
    }elseif ($action == 'data_search_lastchange') {
        print_stations_list_data_all($db, $format, "lastchangetime", "true", $offset, $limit);
    }elseif ($action == 'data_search_name') {
        print_stations_list_data($db, $format, 'Name', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_name_exact') {
        print_stations_list_data_exact($db, $format, 'Name', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bycodec') {
        print_stations_list_data($db, $format, 'Codec', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bycodec_exact') {
        print_stations_list_data_exact($db, $format, 'Codec', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bycountry') {
        print_stations_list_data($db, $format, 'Country', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bycountry_exact') {
        print_stations_list_data_exact($db, $format, 'Country', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bystate') {
        print_stations_list_data($db, $format, 'Subcountry', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bystate_exact') {
        print_stations_list_data_exact($db, $format, 'Subcountry', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bylanguage') {
        print_stations_list_data($db, $format, 'Language', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bylanguage_exact') {
        print_stations_list_data_exact($db, $format, 'Language', $term, true, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bytag') {
        print_stations_list_data($db, $format, 'Tags', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bytag_exact') {
        print_stations_list_data_exact($db, $format, 'Tags', $term, true, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_byid') {
        print_stations_list_data_exact($db, $format, 'StationID', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_byuuid') {
        print_stations_list_data_exact($db, $format, 'StationUuid', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_byurl') {
        print_stations_list_data_url($db, $format, $url);
    }elseif ($action == 'data_search_broken') {
        print_stations_list_broken($db, $format, $limit);
    }elseif ($action == 'data_search_improvable') {
        print_stations_list_improvable($db, $format, $limit);
    }elseif ($action == 'data_stations_all') {
        print_stations_list_data_all($db, $format, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_deleted') {
        print_stations_list_deleted($db, $format, $stationid);
    }elseif ($action == 'data_search_deleted_all') {
        print_stations_list_deleted_all($db, $format);
    }elseif ($action == 'data_search_changed') {
        print_stations_list_changed($db, $format, $stationid, $lastchangeuuid);
    }elseif ($action == 'data_search_changed_all') {
        print_stations_list_changed_all($db, $format, $lastchangeuuid);
    }elseif ($action == 'add') {
        addStation($db, $format, $name, $url, $homepage, $favicon, $country, $countrycode, $language, $tags, $state);
    }elseif ($action == 'edit') {
        editStation($db, $format, $stationid, $name, $url, $homepage, $favicon, $country, $countrycode, $language, $tags, $state);
    }elseif ($action == 'delete') {
        deleteStation($db, $format, $stationid);
    }elseif ($action == 'undelete') {
        undeleteStation($db, $format, $stationid);
    }elseif ($action == 'revert') {
        revertStation($db, $format, $stationid, $stationchangeid);
    }elseif ($action == 'vote') {
        voteForStation($db, $format, $stationid);
    }elseif ($action == 'urlv2') {
        print_station_real_url($db, $format, $stationid);
    }elseif ($action == 'negativevote') {
        negativeVoteForStation($db, $format, $stationid);
    }elseif ($action == 'extract_images') {
        listExtractedImages($format, $url);
    }elseif ($action == 'data_checks_all') {
        listChecks($db, $format, $seconds, NULL, $lastcheckuuid);
    }elseif ($action == 'data_checks') {
        listChecks($db, $format, $seconds, $stationuuid, $lastcheckuuid);
    }
} else {
    ?>
<!doctype html>
<html>
	<head>
        <meta http-equiv="refresh" content="0; URL=http://www.radio-browser.info/gui/">
		<meta charset="utf-8">
		<title>Community Radio Station Board</title>
	</head>
	<body>
	</body>
</html>
<?php

}
?>
