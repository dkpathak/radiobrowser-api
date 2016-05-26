<?php
require 'db.php';

function getParameter($paramName, $defaultValue){
    if (isset($_GET[$paramName])) {
        return $_GET[$paramName];
    }
    if (isset($_POST[$paramName])) {
        return $_POST[$paramName];
    }
    return $defaultValue;
}

if (isset($_GET['action'])) {
    // open database
    $db = openDB();
    // check parameters, set default values
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $term = getParameter('term','');
    $offset = getParameter('offset', 0);
    $limit = getParameter('limit', 100000);
    $reverse = getParameter('reverse',"false");
    $order = getParameter('order',"");
    $stationid = isset($_GET['stationid']) ? $_GET['stationid'] : null;
    $action = $_GET['action'];

    $country = isset($_REQUEST['country']) ? $_REQUEST['country'] : '';
    $state = isset($_REQUEST['state']) ? $_REQUEST['state'] : '';
    $language = isset($_REQUEST['language']) ? $_REQUEST['language'] : '';
    $tags = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : '';

    if ($action == 'tags') {
        print_tags($db, $format, $term);
    }elseif ($action == 'countries') {
        print_1_n($db, $format, 'Country', 'country', $term);
    }elseif ($action == 'codecs') {
        print_1_n($db, $format, 'Codec', 'codec', $term);
    }elseif ($action == 'states') {
        print_states($db, $format, $term, $country);
    }elseif ($action == 'languages') {
        print_1_n($db, $format, 'Language', 'language', $term);
    }elseif ($action == 'stats') {
        print_stats($db, $format);
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
        print_stations_list_data_exact($db, $format, 'Language', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bytag') {
        print_stations_list_data($db, $format, 'Tags', $term, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_bytag_exact') {
        print_stations_list_data_exact($db, $format, 'Tags', $term, true, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_byid') {
        print_stations_list_data_exact($db, $format, 'StationID', $term, false, $order, $reverse, $offset, $limit);
    }elseif ($action == 'data_search_broken') {
        print_stations_list_broken($db, $format, $limit);
    }elseif ($action == 'data_search_improvable') {
        print_stations_list_improvable($db, $format, $limit);
    }elseif ($action == 'data_stations_all') {
        print_stations_list_data_all($db, $format, $order, $reverse, $offset, $limit);
    }elseif ($action == 'add') {
        addStation($db, $_REQUEST['name'], $_REQUEST['url'], $_REQUEST['homepage'], $_REQUEST['favicon'], $country, $language, $tags, $state);
    }elseif ($action == 'edit') {
        editStation($db, $_REQUEST['stationid'], $_REQUEST['name'], $_REQUEST['url'], $_REQUEST['homepage'], $_REQUEST['favicon'], $country, $language, $tags, $state);
    }elseif ($action == 'delete') {
        deleteStation($db, $stationid);
    }elseif ($action == 'vote') {
        voteForStation($db, $format, $stationid);
    }elseif ($action == 'negativevote') {
        negativeVoteForStation($db, $format, $stationid);
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
