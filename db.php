<?php

require 'utils.php';

function openDB()
{
    $db = new PDO('mysql:host=localhost;dbname=radio', 'radiouser', '');
    // use exceptions for error handling
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // create needed tables if they do not exist
    if (!tableExists($db, 'Station')) {
        $db->query('CREATE TABLE Station(
          StationID INT NOT NULL AUTO_INCREMENT,
          Primary Key (StationID),
          Name TEXT,
          Url TEXT,
          Homepage TEXT,
          Favicon TEXT,
          Creation TIMESTAMP,
          Country VARCHAR(50),
          Subcountry VARCHAR(50),
          Language VARCHAR(50),
          Tags TEXT,
          Votes INT DEFAULT 0,
          NegativeVotes INT DEFAULT 0,
          Source VARCHAR(20),
          clickcount INT DEFAULT 0,
          ClickTrend INT DEFAULT 0,
          ClickTimestamp TIMESTAMP NOT NULL)
          ');
    }
    if (!tableExists($db, 'StationHistory')) {
        $db->query('CREATE TABLE StationHistory(
          StationChangeID INT NOT NULL AUTO_INCREMENT,
          Primary Key (StationChangeID),
          StationID INT NOT NULL,
          Name TEXT,
          Url TEXT,
          Homepage TEXT,
          Favicon TEXT,
          Creation TIMESTAMP,
          Country VARCHAR(50),
          Subcountry VARCHAR(50),
          Language VARCHAR(50),
          Tags TEXT,
          Votes INT DEFAULT 0,
          NegativeVotes INT DEFAULT 0)
          ');
    }
    if (!tableExists($db, 'IPVoteCheck')) {
        $db->query('CREATE TABLE IPVoteCheck(CheckID INT NOT NULL AUTO_INCREMENT,
          Primary Key (CheckID),
          IP VARCHAR(15) NOT NULL,
          StationID INT NOT NULL,
          VoteTimestamp TIMESTAMP)
          ');
    }
    if (!tableExists($db, 'StationClick')) {
        $db->query('CREATE TABLE StationClick(ClickID INT NOT NULL AUTO_INCREMENT,
          Primary Key (ClickID),
          StationID INT,
          ClickTimestamp TIMESTAMP)
          ');
    }
    if (!tableExists($db, 'TagCache')) {
        $db->query('CREATE TABLE TagCache(
          TagName VARCHAR(100) NOT NULL,
          Primary Key (TagName),
          StationCount INT DEFAULT 0)
          ');
    }

    return $db;
}

function tableExists($db, $tableName)
{
    if ($result = $db->query("SHOW TABLES LIKE '".$tableName."'")) {
        return $result->rowCount() > 0;
    }

    return false;
}

function print_object($row, $format, $columns, $itemname)
{
    print_output_item_start($format, $itemname);
    $j = 0;
    foreach ($columns as $outputName => $dbColumn) {
        if ($j > 0) {
            print_output_item_dict_sep($format);
        }
        if (isset($row[$dbColumn])) {
            print_output_item_content($format, $outputName, $row[$dbColumn]);
        }else{
            print_output_item_content($format, $outputName, '');
        }
        ++$j;
    }
    print_output_item_end($format);
}

function print_list($stmt, $format, $columns, $itemname)
{
    print_output_header($format);
    print_output_arr_start($format);
    $i = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($i > 0) {
            print_output_item_arr_sep($format);
        }
        print_object($row, $format, $columns, $itemname);
        ++$i;
    }
    print_output_arr_end($format);
    print_output_footer($format);
}

/*
Print a result set with many stations in the given format
*/
function print_result_stations($stmt, $format)
{
    print_list($stmt, $format, [
        'id' => 'StationID',
        'name' => 'Name',
        'url' => 'Url',
        'homepage' => 'Homepage',
        'favicon' => 'Favicon',
        'tags' => 'Tags',
        'country' => 'Country',
        'state' => 'Subcountry',
        'language' => 'Language',
        'votes' => 'Votes',
        'negativevotes' => 'NegativeVotes',
        'clicktimestamp' => 'ClickTimestamp',
        'clickcount' => 'clickcount',
        'clicktrend' => 'ClickTrend'
    ], 'station');
}

function print_tags($db, $format, $search_term)
{
    $stmt = $db->prepare('SELECT TagName,StationCount FROM TagCache WHERE TagName LIKE :search ORDER BY StationCount DESC,TagName ASC');
    $result = $stmt->execute(['search' => '%'.$search_term.'%']);
    if ($result) {
        print_list($stmt, $format, ['name' => 'TagName', 'stationcount' => 'StationCount'], 'tag');
    }
}

function print_1_n($db, $format, $column, $outputItemName, $search_term)
{
    $stmt = $db->prepare('SELECT '.$column.', COUNT(*) AS StationCount FROM Station WHERE '.$column.' LIKE :search AND '.$column."<>'' GROUP BY ".$column.' ORDER BY '.$column);
    $result = $stmt->execute(['search' => '%'.$search_term.'%']);
    if ($result) {
        print_list($stmt, $format, ['name' => $column, 'stationcount' => 'StationCount'], $outputItemName);
    }
}

function print_states($db, $format, $search_term, $country)
{
    if ($country !== '') {
        $stmt = $db->prepare("SELECT Country, Subcountry, COUNT(*) AS StationCount FROM Station WHERE Country=:country AND Subcountry LIKE :search AND Country<>'' AND Subcountry<>'' GROUP BY Country, Subcountry ORDER BY Subcountry");
        $result = $stmt->execute(['search' => '%'.$search_term.'%', 'country' => $country]);
    } else {
        $stmt = $db->prepare("SELECT Country, Subcountry, COUNT(*) AS StationCount FROM Station WHERE Subcountry LIKE :search AND Country<>'' AND Subcountry<>'' GROUP BY Country, Subcountry ORDER BY Subcountry");
        $result = $stmt->execute(['search' => '%'.$search_term.'%']);
    }

    if ($result) {
        print_list($stmt, $format, ['name' => 'Subcountry', 'country' => 'Country', 'stationcount' => 'StationCount'], 'state');
    }
}

function print_stations_last_click_data($db, $format, $limit)
{
    $result = $db->query('SELECT * FROM Station WHERE Station.Source IS NULL ORDER BY ClickTimestamp DESC LIMIT '.$limit);
    if ($result) {
        print_result_stations($result, $format);
    }
}

function print_stations_last_change_data($db, $format, $limit)
{
    $result = $db->query('SELECT * FROM Station WHERE Station.Source IS NULL ORDER BY Creation DESC LIMIT '.$limit);
    if ($result) {
        print_result_stations($result, $format);
    }
}

function print_stations_top_click_data($db, $format, $limit)
{
    $result = $db->query('SELECT * FROM Station WHERE Station.Source IS NULL ORDER BY clickcount DESC LIMIT '.$limit);
    if ($result) {
        print_result_stations($result, $format);
    }
}

function print_stations_top_vote_data($db, $format, $limit)
{
    $result = $db->query('SELECT * FROM Station WHERE Source IS NULL ORDER BY Votes DESC,NegativeVotes ASC,Name LIMIT '.$limit);
    if ($result) {
        print_result_stations($result, $format);
    }
}

function get_station_count($db)
{
    $result = $db->query('SELECT COUNT(*) FROM Station WHERE Source is NULL');
    if ($result) {
        return $result->fetchColumn(0);
    }

    return 0;
}

function get_tag_count($db)
{
    $result = $db->query('SELECT COUNT(*) FROM TagCache');
    if ($result) {
        return $result->fetchColumn(0);
    }

    return 0;
}

function get_click_count_hours($db, $hours)
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM StationClick WHERE TIMEDIFF(NOW(),ClickTimestamp)<MAKETIME(:hours,0,0)');
    $result = $stmt->execute(['hours' => $hours]);
    if ($result) {
        return $stmt->fetchColumn(0);
    }

    return 0;
}

function get_languages_count($db)
{
    $result = $db->query('SELECT COUNT(DISTINCT Language) FROM Station');
    if ($result) {
        return $result->fetchColumn(0);
    }

    return 0;
}

function get_countries_count($db)
{
    $result = $db->query('SELECT COUNT(DISTINCT Country) FROM Station');
    if ($result) {
        return $result->fetchColumn(0);
    }

    return 0;
}

function print_stats($db, $format)
{
    print_output_header($format);
    print_output_item_start($format, 'stats');
    print_output_item_content($format, 'stations', get_station_count($db));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'tags', get_tag_count($db));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'clicks_last_hour', get_click_count_hours($db, 1));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'clicks_last_day', get_click_count_hours($db, 24));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'languages', get_languages_count($db));
    print_output_item_dict_sep($format);
    print_output_item_content($format, 'countries', get_countries_count($db));
    print_output_item_end($format);
    print_output_footer($format);
}

function print_stations_list_data($db, $format, $column, $search_term)
{
    if ($search_term != '') {
        $stmt = $db->prepare('SELECT * FROM Station WHERE Source IS NULL AND '.$column.' LIKE :search');
        $result = $stmt->execute(['search' => '%'.$search_term.'%']);
    } else {
        $stmt = $db->prepare('SELECT * FROM Station WHERE Source IS NULL');
        $result = $stmt->execute();
    }
    if ($result) {
        print_result_stations($stmt, $format);
    }
}

function print_stations_list_data_exact($db, $format, $column, $search_term, $multivalue)
{
    $result = false;
    if ($search_term != '') {
        if ($multivalue === true) {
            $stmt = $db->prepare('SELECT * FROM Station WHERE Source IS NULL AND ('.$column.'=:searchSingle OR '.$column.' LIKE :searchRight OR '.$column.' LIKE :searchLeft OR '.$column.' LIKE :searchMiddle)');
            $result = $stmt->execute(['searchSingle' => $search_term, 'searchLeft' => '%,'.$search_term, 'searchRight' => $search_term.',%', 'searchMiddle' => '%,'.$search_term.',%']);
        } else {
            $stmt = $db->prepare('SELECT * FROM Station WHERE Source IS NULL AND '.$column.'=:search');
            $result = $stmt->execute(['search' => $search_term]);
        }
    }
    if ($result) {
        print_result_stations($stmt, $format);
    }
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

function backupStation($db, $stationid)
{
    // backup old content
    $stmt = $db->prepare('INSERT INTO StationHistory(StationID,Name,Url,Homepage,Favicon,Country,Language,Tags,Votes,NegativeVotes,Creation) SELECT StationID,Name,Url,Homepage,Favicon,Country,Language,Tags,Votes,NegativeVotes,Creation FROM Station WHERE StationID=:id');
    $result = $stmt->execute(['id' => $stationid]);
}

function addStation($db, $name, $url, $homepage, $favicon, $country, $language, $tags, $state)
{
    $stmt = $db->prepare('DELETE FROM Station WHERE Url=:url');
    $stmt->execute(['url' => $url]);

    $stmt = $db->prepare('INSERT INTO Station(Name,Url,Homepage,Favicon,Country,Language,Tags,Subcountry,Creation) VALUES(:name,:url,:homepage,:favicon,:country,:language,:tags,:state, NOW())');
    $result = $stmt->execute([
      'name' => $name,
      'url' => $url,
      'homepage' => $homepage,
      'favicon' => $favicon,
      'country' => $country,
      'language' => $language,
      'tags' => $tags,
      'state' => $state,
    ]);

    if ($result && $homepage !== null && ($favicon === "" || $favicon === null || !isset($favicon))){
        $stationid = $db->lastInsertId();
        echo "stationid:".$stationid;
        echo "extract from url:".$homepage;
        $favicon = extractFaviconFromUrl($homepage);
        if ($favicon !== null){
            echo "extract ok:".$favicon;
            $stmt = $db->prepare('UPDATE Station SET Favicon=:favicon WHERE StationID=:id');
            $result = $stmt->execute(['id'=>$stationid,'favicon'=>$favicon]);
            if ($result){
                echo "update ok";
            }
        }
    }
}

function editStation($db, $stationid, $name, $url, $homepage, $favicon, $country, $language, $tags, $state)
{
    backupStation($db, $stationid);
    // update values
    $stmt = $db->prepare('UPDATE Station SET Name=:name,Url=:url,Homepage=:homepage,Favicon=:favicon,Country=:country,Language=:language,Tags=:tags,Subcountry=:state,Creation=NOW() WHERE StationID=:id');
    $result = $stmt->execute([
      'name' => $name,
      'url' => $url,
      'homepage' => $homepage,
      'favicon' => $favicon,
      'country' => $country,
      'language' => $language,
      'tags' => $tags,
      'state' => $state,
      'id' => $stationid,
    ]);

    // Delete empty stations
    $db->query("DELETE FROM Station WHERE Url=''");

    if ($result && $homepage !== null && ($favicon === "" || $favicon === null || !isset($favicon))){
        echo "extract from url:".$homepage;
        $favicon = extractFaviconFromUrl($homepage);
        if ($favicon !== null){
            echo "extract ok:".$favicon;
            $stmt = $db->prepare('UPDATE Station SET Favicon=:favicon WHERE StationID=:id');
            $result = $stmt->execute(['id'=>$stationid,'favicon'=>$favicon]);
            if ($result){
                echo "update ok";
            }
        }
    }
}

function deleteStation($db, $stationid)
{
    if (trim($stationid) != '') {
        backupStation($db, $stationid);
        $stmt = $db->prepare('DELETE FROM Station WHERE StationID=:id');
        $stmt->execute(['id' => $stationid]);
    }
}

function IPVoteChecker($db, $id)
{
    $ip = $_SERVER['REMOTE_ADDR'];

    // delete ipcheck entries after 10 minutes
    $db->query('DELETE FROM IPVoteCheck WHERE TIME_TO_SEC(TIMEDIFF(Now(),VoteTimestamp))>10*60');

    // was there a vote from the ip in the last 10 minutes?
    $stmt = $db->prepare('SELECT COUNT(*) FROM IPVoteCheck WHERE StationID=:id AND IP=:ip');
    $result = $stmt->execute(['id' => $id, 'ip' => $ip]);
    if ($result) {
        // if no, then add new entry
        $ccc = $stmt->fetchColumn(0);
        if ($ccc == 0) {
            $stmt = $db->prepare('INSERT INTO IPVoteCheck(IP,StationID) VALUES(:ip,:id)');
            $result = $stmt->execute(['id' => $id, 'ip' => $ip]);
            if ($result) {
                return true;
            }
        }
    }

    return false;
}

function clickedStationID($db, $id)
{
    $stmt = $db->prepare('INSERT INTO StationClick(StationID) VALUES(:id)');
    $result = $stmt->execute(['id' => $id]);

    $stmt = $db->prepare('UPDATE Station SET ClickTimestamp=NOW() WHERE StationID=:id');
    $result2 = $stmt->execute(['id' => $id]);

    if ($result && $result2) {
        return true;
    }

    return false;
}

function voteForStation($db, $format, $id)
{
    if (!IPVoteChecker($db, $id)) {
        print_station_by_id($db, $format, $id);

        return false;
    }
    $stmt = $db->prepare('UPDATE Station SET Votes=Votes+1 WHERE StationID=:id');
    $result = $stmt->execute(['id' => $id]);
    print_station_by_id($db, $format, $id);
    if ($result) {
        return true;
    }

    return false;
}

function negativeVoteForStation($db, $format, $id)
{
    if (!IPVoteChecker($db, $id)) {
        print_station_by_id($db, $format, $id);

        return false;
    }
    $stmt = $db->prepare('UPDATE Station SET NegativeVotes=NegativeVotes+1 WHERE StationID=:id');
    $result = $stmt->execute(['id' => $id]);
    print_station_by_id($db, $format, $id);
    if ($result) {
        //$db->query("DELETE FROM Station WHERE NegativeVotes>5");
        return true;
    }

    return false;
}

function print_station_by_id($db, $format, $id)
{
    $stmt = $db->prepare('SELECT * from Station WHERE Station.StationID=:id');
    $result = $stmt->execute(['id' => $id]);
    if ($result) {
        print_result_stations($stmt, $format);
    }
}
