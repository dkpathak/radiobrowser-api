<?php

function print_tags($search_term)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';

    $result = mysql_query("SELECT TagName,StationCount FROM TagCache WHERE TagName LIKE '%".escape_string($search_term)."%' ORDER BY StationCount DESC,TagName ASC");
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_output_header($format);
        print_output_arr_start($format);
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {
            if ($i > 0) {
                print_output_item_arr_sep($format);
            }
            print_output_item_start($format, 'tag');
            print_output_item_content($format, 'name', $row['TagName']);
            print_output_item_dict_sep($format);
            print_output_item_content($format, 'stationcount', $row['StationCount']);
            print_output_item_end($format);
            ++$i;
        }
        print_output_arr_end($format);
        print_output_footer($format);
    }
}

function print_countries($search_term)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';

    $result = mysql_query("SELECT Country, COUNT(*) AS StationCount FROM Station WHERE Country LIKE '%".$search_term."%' AND Country<>'' GROUP BY Country ORDER BY Country");
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_output_header($format);
        print_output_arr_start($format);
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {
            if ($i > 0) {
                print_output_item_arr_sep($format);
            }
            print_output_item_start($format, 'country');
            print_output_item_content($format, 'name', $row['Country']);
            print_output_item_dict_sep($format);
            print_output_item_content($format, 'stationcount', $row['StationCount']);
            print_output_item_end($format);
            ++$i;
        }
        print_output_arr_end($format);
        print_output_footer($format);
    }
}

function print_languages($search_term)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';

    $result = mysql_query("SELECT Language, COUNT(*) AS StationCount FROM Station WHERE Language LIKE '%".escape_string($search_term)."%' AND Language<>'' GROUP BY Language ORDER BY Language");
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_output_header($format);
        print_output_arr_start($format);
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {
            if ($i > 0) {
                print_output_item_arr_sep($format);
            }
            print_output_item_start($format, 'country');
            print_output_item_content($format, 'name', $row['Language']);
            print_output_item_dict_sep($format);
            print_output_item_content($format, 'stationcount', $row['StationCount']);
            print_output_item_end($format);
            ++$i;
        }
        print_output_arr_end($format);
        print_output_footer($format);
    }
}

function print_states($search_term)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';

    if (isset($_REQUEST['country'])) {
        $result = mysql_query("SELECT Country, Subcountry, COUNT(*) AS StationCount FROM Station WHERE Country='".escape_string($_REQUEST['country'])."' AND Subcountry LIKE '%".escape_string($search_term)."%' AND Country<>'' AND Subcountry<>'' GROUP BY Country, Subcountry ORDER BY Subcountry");
    } else {
        $result = mysql_query("SELECT Country, Subcountry, COUNT(*) AS StationCount FROM Station WHERE Subcountry LIKE '%".escape_string($search_term)."%' AND Country<>'' AND Subcountry<>'' GROUP BY Country, Subcountry ORDER BY Subcountry");
    }
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_output_header($format);
        print_output_arr_start($format);
        $i = 0;
        while ($row = mysql_fetch_assoc($result)) {
            if ($i > 0) {
                print_output_item_arr_sep($format);
            }
            print_output_item_start($format, 'state');
            print_output_item_content($format, 'name', $row['Subcountry']);
            print_output_item_dict_sep($format);
            print_output_item_content($format, 'country', $row['Country']);
            print_output_item_dict_sep($format);
            print_output_item_content($format, 'stationcount', $row['StationCount']);
            print_output_item_end($format);
            ++$i;
        }
        print_output_arr_end($format);
        print_output_footer($format);
    }
}

function print_stations_last_click_data()
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $limit = isset($_GET['limit']) ? $_GET['limit'] : '10';

    $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) as clickcount, MAX(StationClick.ClickTimestamp) AS ClickTimestamp FROM Station INNER JOIN StationClick ON StationClick.StationID=Station.StationID WHERE Station.Source IS NULL GROUP BY Station.StationID ORDER BY MAX(StationClick.ClickTimestamp) DESC LIMIT '.$limit);
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_result_stations($format, $result);
    }
}

function print_stations_last_change_data()
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $limit = isset($_GET['limit']) ? $_GET['limit'] : '10';

    $result = mysql_query('SELECT Station.*, COUNT(StationClick.StationID) as clickcount FROM Station LEFT JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Station.Source IS NULL GROUP BY Station.StationID ORDER BY Creation DESC LIMIT '.$limit);
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_result_stations($format, $result);
    }
}

function print_stations_top_click_data()
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $limit = isset($_GET['limit']) ? $_GET['limit'] : '10';

    $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) AS clickcount FROM Station INNER JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Station.Source IS NULL GROUP BY Station.StationID ORDER BY clickcount DESC LIMIT '.$limit);
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_result_stations($format, $result);
    }
}

function print_stations_top_vote_data()
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $limit = isset($_GET['limit']) ? $_GET['limit'] : '10';

    $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) AS clickcount FROM Station LEFT JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Source IS NULL GROUP BY Station.StationID ORDER BY Votes DESC,NegativeVotes ASC,Name LIMIT '.$limit);
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_result_stations($format, $result);
    }
}

function get_station_count()
{
    $result = mysql_query('SELECT COUNT(*) FROM Station WHERE Source is NULL');
    if ($result) {
        $resArray = mysql_fetch_row($result);
        $numrows = $resArray[0];

        return $numrows;
    }

    return 0;
}

function get_tag_count()
{
    $result = mysql_query('SELECT COUNT(*) FROM TagCache');
    if ($result) {
        $resArray = mysql_fetch_row($result);
        $numrows = $resArray[0];

        return $numrows;
    }

    return 0;
}

function get_click_count_hours($hours)
{
    $result = mysql_query('SELECT COUNT(*) FROM StationClick stc, Station st WHERE stc.StationID=st.StationID AND Source IS NULL AND TIMEDIFF(NOW(),ClickTimestamp)<MAKETIME('.$hours.',0,0)');
    if ($result) {
        $resArray = mysql_fetch_row($result);
        $numrows = $resArray[0];

        return $numrows;
    }

    return 0;
}

function get_languages_count()
{
    $result = mysql_query('SELECT COUNT(DISTINCT Language) FROM Station');
    if ($result) {
        $resArray = mysql_fetch_row($result);
        $numrows = $resArray[0];

        return $numrows;
    }

    return 0;
}

function get_countries_count()
{
    $result = mysql_query('SELECT COUNT(DISTINCT Country) FROM Station');
    if ($result) {
        $resArray = mysql_fetch_row($result);
        $numrows = $resArray[0];

        return $numrows;
    }

    return 0;
}

function print_stats()
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    print_output_header($format);
    print_output_item_start($format, 'stats');
    print_output_item_content($format, 'stations', get_station_count());
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'tags', get_tag_count());
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'clicks_last_hour', get_click_count_hours(1));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'clicks_last_day', get_click_count_hours(24));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'languages', get_languages_count());
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'countries', get_countries_count());
    print_output_item_end($format);
    print_output_footer($format);
}

function print_stations_list_data($column)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    if (isset($_GET['term'])) {
        $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) as clickcount FROM Station LEFT JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Source is NULL AND Station.'.$column." LIKE '%".$_GET['term']."%' GROUP BY Station.StationID");
    } else {
        $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) as clickcount FROM Station LEFT JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Source is NULL GROUP BY Station.StationID');
    }
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    print_result_stations($format, $result);
}

/*
Print a result set with many stations in the given format
*/
function print_result_stations($format, $result)
{
    print_output_header($format);
    print_output_arr_start($format);

    $i = 0;
    while ($row = mysql_fetch_assoc($result)) {
        if ($i > 0) {
            print_output_item_arr_sep($format);
        }
        print_station($format, $row);
        ++$i;
    }

    print_output_arr_end($format);
    print_output_footer($format);
}

function print_stations_list_data_exact($column, $multivalue)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';

    if (isset($_GET['term'])) {
        $value = escape_string($_GET['term']);
        if ($multivalue === true) {
            $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) as clickcount FROM Station LEFT JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Source is NULL AND (Station.'.$column."='".$value."' OR Station.".$column." LIKE '".$value.",%' OR Station.".$column." LIKE '%,".$value."' OR Station.".$column." LIKE '%,".$value.",%') GROUP BY Station.StationID");
        } else {
            $result = mysql_query('SELECT Station.*,COUNT(StationClick.StationID) as clickcount FROM Station LEFT JOIN StationClick ON Station.StationID=StationClick.StationID WHERE Source is NULL AND Station.'.$column."='".$value."' GROUP BY Station.StationID");
        }
    } else {
      exit;
    }
    if (!$result) {
        echo str(mysql_error());
        exit;
    }

    print_result_stations($format, $result);
}

function print_output_item_dict_sep($format)
{
    if ($format == 'xml') {
        echo ' ';
    }
    if ($format == 'json') {
        echo ',';
    }
}

function print_output_item_arr_sep($format)
{
    if ($format == 'xml') {
    }
    if ($format == 'json') {
        echo ',';
    }
}

function print_output_arr_start($format)
{
    if ($format == 'xml') {
    }
    if ($format == 'json') {
        echo '[';
    }
}

function print_output_arr_end($format)
{
    if ($format == 'xml') {
    }
    if ($format == 'json') {
        echo ']';
    }
}

function print_output_header($format)
{
    if ($format == 'xml') {
        header('content-type: text/xml');
        echo '<result>';
    }
    if ($format == 'json') {
        header('content-type: application/json');
    }
}

function print_output_footer($format)
{
    if ($format == 'xml') {
        echo '</result>';
    }
    if ($format == 'json') {
    }
}

function print_output_item_start($format, $itemname)
{
    if ($format == 'xml') {
        echo '<'.$itemname.' ';
    }
    if ($format == 'json') {
        echo '{';
    }
}

function print_output_item_end($format)
{
    if ($format == 'xml') {
        echo '/>';
    }
    if ($format == 'json') {
        echo '}';
    }
}

function print_output_item_content($format, $key, $value)
{
    if ($format == 'xml') {
        echo $key.'="'.htmlspecialchars($value, ENT_QUOTES).'"';
    }
    if ($format == 'json') {
        echo '"'.$key.'":"'.addcslashes(str_replace('\\', '', $value), '"').'"';
    }
}

function print_station($format, $row)
{
    print_output_item_start($format, 'station');
    print_output_item_content($format, 'id', $row['StationID']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'name', $row['Name']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'url', $row['Url']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'homepage', $row['Homepage']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'favicon', $row['Favicon']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'tags', $row['Tags']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'country', $row['Country']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'state', $row['Subcountry']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'language', $row['Language']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'votes', $row['Votes']);
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'negativevotes', $row['NegativeVotes']);

    if (isset($row['ClickID'])) {
        print_output_item_dict_sep($format);
        print_output_item_content($format, 'clickid', $row['ClickID']);
    }
    if (isset($row['ClickTimestamp'])) {
        print_output_item_dict_sep($format);
        print_output_item_content($format, 'clicktimestamp', $row['ClickTimestamp']);
    }
    if (isset($row['clickcount'])) {
        print_output_item_dict_sep($format);
        print_output_item_content($format, 'clickcount', $row['clickcount']);
    }
    print_output_item_end($format);
}

function escape_string($str)
{
    global $db;
    if (get_magic_quotes_gpc() == 1) {
        return mysql_real_escape_string(stripslashes($str), $db);
    } else {
        return mysql_real_escape_string($str, $db);
    }
}

function openDB()
{
    global $db;
    $db = mysql_connect('localhost', 'root', '') or die('could not connect');
    mysql_select_db('radio') or die('could not change to database');
    if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'Station'"))) {
        mysql_query('CREATE TABLE Station(StationID INT NOT NULL AUTO_INCREMENT,Primary Key (StationID),Name TEXT,Url TEXT,Homepage TEXT,Favicon TEXT,Creation TIMESTAMP,Country VARCHAR(50),Subcountry VARCHAR(50),Language VARCHAR(50),Tags TEXT,Votes INT DEFAULT 0,NegativeVotes INT DEFAULT 0,Source VARCHAR(20))') or die('could not create table');
    }
    if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'StationHistory'"))) {
        mysql_query('CREATE TABLE StationHistory(StationChangeID INT NOT NULL AUTO_INCREMENT, Primary Key (StationChangeID),StationID INT NOT NULL,Name TEXT,Url TEXT,Homepage TEXT,Favicon TEXT,Creation TIMESTAMP,Country VARCHAR(50),Subcountry VARCHAR(50),Language VARCHAR(50),Tags TEXT,Votes INT DEFAULT 0,NegativeVotes INT DEFAULT 0)') or die('could not create table');
    }
    if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'IPVoteCheck'"))) {
        mysql_query('CREATE TABLE IPVoteCheck(CheckID INT NOT NULL AUTO_INCREMENT,Primary Key (CheckID),IP VARCHAR(15) NOT NULL,StationID INT NOT NULL, VoteTimestamp TIMESTAMP)') or die('could not create table');
    }
    if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'StationClick'"))) {
        mysql_query('CREATE TABLE StationClick(ClickID INT NOT NULL AUTO_INCREMENT,Primary Key (ClickID),StationID INT, ClickTimestamp TIMESTAMP)') or die('could not create table');
    }
    if (!mysql_num_rows(mysql_query("SHOW TABLES LIKE 'TagCache'"))) {
        mysql_query('CREATE TABLE TagCache(TagName VARCHAR(100) NOT NULL,Primary Key (TagName), StationCount INT DEFAULT 0)') or die('could not create table');
    }
}

function backupStation($stationid)
{
    // backup old content
    mysql_query('INSERT INTO StationHistory(StationID,Name,Url,Homepage,Favicon,Country,Language,Tags,Votes,NegativeVotes,Creation) SELECT StationID,Name,Url,Homepage,Favicon,Country,Language,Tags,Votes,NegativeVotes,Creation FROM Station WHERE StationID='.escape_string($stationid));
}

function addStation($name, $url, $homepage, $favicon, $country, $language, $tags, $subcountry)
{
    mysql_query("DELETE FROM Station WHERE Url='".escape_string($url)."'");
    mysql_query("INSERT INTO Station(Name,Url,Homepage,Favicon,Country,Language,Tags,Subcountry) VALUES('".escape_string($name)."','".escape_string($url)."','".escape_string($homepage)."','".escape_string($favicon)."','".escape_string($country)."','".escape_string($language)."','".escape_string($tags)."','".escape_string($subcountry)."')");
}

function editStation($stationid, $name, $url, $homepage, $favicon, $country, $language, $tags, $subcountry)
{
    backupStation($stationid);
    // update values
    mysql_query("UPDATE Station SET Name='".escape_string($name)."',Url='".escape_string($url)."',Homepage='".escape_string($homepage)."',Favicon='".escape_string($favicon)."',Country='".escape_string($country)."',Language='".escape_string($language)."',Tags='".escape_string($tags)."',Subcountry='".escape_string($subcountry)."',Creation=Now() WHERE StationID=".escape_string($stationid));
}

function deleteStation($stationid)
{
    if (trim($stationid) != '') {
        backupStation($stationid);
        mysql_query('DELETE FROM Station WHERE StationID='.escape_string($stationid));
    }
}

function IPVoteChecker($id)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    // delete ipcheck entries after 10 minutes
    mysql_query('DELETE FROM IPVoteCheck WHERE TIME_TO_SEC(TIMEDIFF(Now(),VoteTimestamp))>10*60');

    // was there a vote from the ip in the last 10 minutes?
    if (!mysql_num_rows(mysql_query('SELECT * FROM IPVoteCheck WHERE StationID='.$id." AND IP='".$ip."'"))) {
        // if no, then add new entry
        mysql_query("INSERT INTO IPVoteCheck(IP,StationID) VALUES('".$ip."',".$id.')');

        return true;
    }

    return false;
}

function clickedStationID($id)
{
    mysql_query('INSERT INTO StationClick(StationID) VALUES('.$id.')');

    return 1;
}

function voteForStation($id)
{
    if (!IPVoteChecker($id)) {
        return false;
    }
    mysql_query('UPDATE Station SET Votes=Votes+1 WHERE StationID='.$id);
    print_station_by_id($id);

    return true;
}

function negativeVoteForStation($id)
{
    if (!IPVoteChecker($id)) {
        return false;
    }
    mysql_query('UPDATE Station SET NegativeVotes=NegativeVotes+1 WHERE StationID='.$id);
    print_station_by_id($id);
    //mysql_query("DELETE FROM Station WHERE NegativeVotes>5");
    return true;
}

function print_station_by_id($id)
{
    $format = isset($_GET['format']) ? $_GET['format'] : 'xml';
    $result = mysql_query('SELECT * from Station WHERE Station.StationID='.$id);
    if (!$result) {
        echo str(mysql_error());
    } else {
        print_result_stations($format, $result);
    }
}

?>
