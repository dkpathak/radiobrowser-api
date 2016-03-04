<?
$rows_by_page = 20;

function print_stations_last_click_data()
{
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
        $limit = isset($_GET["limit"])?$_GET["limit"]:"10";

	$result = mysql_query("SELECT * from StationClick LEFT Join Station ON StationClick.StationID=Station.StationID WHERE Station.Source IS NULL ORDER BY StationClick.ClickTimestamp DESC LIMIT ".$limit);
	if (!$result)
        {
            echo str(mysql_error());
        }
        else
        {
		print_output_header($format);
		if ($format == "xml")
                {
                        while ($row = mysql_fetch_assoc($result))
                        {
                                print_station_xml($row);
                        }
                }
                if ($format == "json")
                {
                        $i = 0;
                        while ($row = mysql_fetch_assoc($result))
                        {
                                if ($i > 0)
                                        print_output_item_arr_sep($format);

                                print_station_json($row);
                                $i++;
                        }
                }

		print_output_footer($format);
	}
}

function print_stations_top_click_data()
{
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
	$limit = isset($_GET["limit"])?$_GET["limit"]:"10";
	
	$result = mysql_query("SELECT st.*,COUNT(*) AS clickcount FROM Station st,StationClick stc WHERE st.StationID=stc.StationID AND st.Source IS NULL GROUP BY st.StationID ORDER BY clickcount DESC LIMIT ".$limit);
	if (!$result)
	{
	    echo str(mysql_error());
	}
	else
	{
		print_output_header($format);
	
		if ($format == "xml")
		{
			while ($row = mysql_fetch_assoc($result))
			{
				print_station_xml($row);
			}
		}
		if ($format == "json")
		{
			$i = 0;
			while ($row = mysql_fetch_assoc($result))
			{
				if ($i > 0)
					print_output_item_arr_sep($format);
					
				print_station_json($row);
				$i++;
			}
		}
	
		print_output_footer($format);
	}
}

function print_stations_top_vote_data()
{
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
	$limit = isset($_GET["limit"])?$_GET["limit"]:"10";
	
	$result = mysql_query("SELECT * FROM Station WHERE Source IS NULL ORDER BY Votes DESC,NegativeVotes ASC,Name LIMIT ".$limit);
	if (!$result)
	{
	    echo str(mysql_error());
	}
	else
	{
		print_output_header($format);
	
		if ($format == "xml")
		{
			while ($row = mysql_fetch_assoc($result))
			{
				print_station_xml($row);
			}
		}
		if ($format == "json")
		{
			$i = 0;
			while ($row = mysql_fetch_assoc($result))
			{
				if ($i > 0)
					print_output_item_arr_sep($format);
				print_station_json($row);
				$i++;
			}
		}
	
		print_output_footer($format);
	}
}

function get_station_count()
{
	$result=mysql_query("SELECT COUNT(*) FROM Station WHERE Source is NULL");
	if ($result)
	{
		$resArray=mysql_fetch_row($result);
		$numrows=$resArray[0];
		return $numrows;
	}
	return 0;
}

function get_tag_count()
{
	$result=mysql_query("SELECT COUNT(*) FROM TagCache");
	if ($result)
	{
		$resArray=mysql_fetch_row($result);
		$numrows=$resArray[0];
		return $numrows;
	}
	return 0;
}

function get_click_count_hours($hours)
{
	$result=mysql_query("SELECT COUNT(*) FROM StationClick stc, Station st WHERE stc.StationID=st.StationID AND Source IS NULL AND TIMEDIFF(NOW(),ClickTimestamp)<MAKETIME(".$hours.",0,0)");
	if ($result)
	{
		$resArray=mysql_fetch_row($result);
		$numrows=$resArray[0];
		return $numrows;
	}
	return 0;
}

function print_stats()
{
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
	print_output_header_dict($format);
	print_output_item_dict($format,"stations",get_station_count());
	print_output_item_dict_sep($format);
	print_output_item_dict($format,"tags",get_tag_count());
	print_output_item_dict_sep($format);
	print_output_item_dict($format,"clicks_last_hour",get_click_count_hours(1));
	print_output_item_dict_sep($format);
	print_output_item_dict($format,"clicks_last_day",get_click_count_hours(24));
	print_output_footer_dict($format);
}

function print_stations_list_data($column)
{
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
	if (isset($_GET["term"]))
		$result = mysql_query("SELECT * FROM Station WHERE Source is NULL AND ".$column." LIKE '%".$_GET["term"]."%'");
	else
		$result = mysql_query("SELECT * FROM Station WHERE Source is NULL");
	if (!$result)
	{
	    echo str(mysql_error());
	    exit;
	}

	print_output_header($format);
	
	if ($format == "xml")
	{
		while ($row = mysql_fetch_assoc($result))
		{
			print_station_xml($row);
		}
	}
	if ($format == "json")
	{
		$i = 0;
		while ($row = mysql_fetch_assoc($result))
		{
			if ($i > 0)
					print_output_item_arr_sep($format);
			print_station_json($row);
			$i++;
		}
	}
	
	print_output_footer($format);
}

function print_stations_list_data_exact($column)
{
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
	if (isset($_GET["term"]))
		$result = mysql_query("SELECT * FROM Station WHERE Source is NULL AND ".$column."='".$_GET["term"]."'");
	else
		$result = mysql_query("SELECT * FROM Station WHERE Source is NULL");
	if (!$result)
	{
	    echo str(mysql_error());
	    exit;
	}

	print_output_header($format);
	
	if ($format == "xml")
	{
		while ($row = mysql_fetch_assoc($result))
		{
			print_station_xml($row);
		}
	}
	if ($format == "json")
	{
		$i = 0;
		while ($row = mysql_fetch_assoc($result))
		{
			if ($i > 0)
					print_output_item_arr_sep($format);
			print_station_json($row);
			$i++;
		}
	}
	
	print_output_footer($format);
}

function print_output_item_dict_sep($format)
{
	if ($format == "xml")
	{
	}
	if ($format == "json")
	{
		echo ",";
	}
}

function print_output_item_arr_sep($format)
{
	if ($format == "xml")
	{
	}
	if ($format == "json")
	{
		echo ",";
	}
}

function print_output_header_dict($format)
{
	if ($format == "xml")
	{
		header ("content-type: text/xml");
		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo "<result>";
	}
	if ($format == "json")
	{
		echo "{";
	}
}

function print_output_footer_dict($format)
{
	if ($format == "xml")
	{
		echo "</result>";
	}
	if ($format == "json")
	{
		echo "}";
	}
}

function print_output_item_dict($format,$key,$value)
{
	if ($format == "xml")
	{
		echo "<".$key.">".$value."</".$key.">";
	}
	if ($format == "json")
	{
		echo "\"".$key."\":\"".$value."\"";
	}
}

function print_output_xml_header()
{
	header ("content-type: text/xml");
	/*echo '<?xml version="1.0" encoding="UTF-8"?>';*/
	echo "<result>";
}

function print_output_xml_footer()
{
	echo "</result>";
}

function print_output_json_header()
{
	header ("content-type: application/json");
	echo "[";
}

function print_output_json_footer()
{
	echo "]";
}

function print_output_header($format)
{
	if ($format == "xml")
	{
		print_output_xml_header();
	}
	if ($format == "json")
	{
		print_output_json_header();
	}
}

function print_output_footer($format)
{
	if ($format == "xml")
	{
		print_output_xml_footer();
	}
	if ($format == "json")
	{
		print_output_json_footer();
	}
}

function print_station_xml($row)
{
	echo "<station id='".$row["StationID"]."'";
	echo " name='".htmlspecialchars($row["Name"],ENT_QUOTES)."'";
	echo " url='".htmlspecialchars($row["Url"],ENT_QUOTES)."'";
	echo " homepage='".htmlspecialchars($row["Homepage"],ENT_QUOTES)."'";
	echo " favicon='".htmlspecialchars($row["Favicon"],ENT_QUOTES)."'";
	echo " tags='".htmlspecialchars($row["Tags"],ENT_QUOTES)."'";
	echo " country='".htmlspecialchars($row["Country"],ENT_QUOTES)."'";
	echo " subcountry='".htmlspecialchars($row["Subcountry"],ENT_QUOTES)."'";
	echo " language='".htmlspecialchars($row["Language"],ENT_QUOTES)."'";
	echo " votes='".$row["Votes"]."'";
	echo " negativevotes='".$row["NegativeVotes"]."'";
	if (isset($row["ClickID"]))
                echo " clickid='".htmlspecialchars($row["ClickID"],ENT_QUOTES)."'";
        if (isset($row["ClickTimestamp"]))
                echo " clicktimestamp='".htmlspecialchars($row["ClickTimestamp"],ENT_QUOTES)."'";
        if (isset($row["clickcount"]))
                echo " clickcount='".htmlspecialchars($row["clickcount"],ENT_QUOTES)."'";
	echo "/>";
}

function print_station_json($row)
{
	echo "{";
	echo "\"id\":".$row["StationID"].",";
	echo "\"name\":\"".htmlspecialchars($row["Name"],ENT_COMPAT)."\",";
	echo "\"url\":\"".htmlspecialchars($row["Url"],ENT_COMPAT)."\",";
	echo "\"homepage\":\"".htmlspecialchars($row["Homepage"],ENT_COMPAT)."\",";
	echo "\"favicon\":\"".htmlspecialchars($row["Favicon"],ENT_COMPAT)."\",";
	echo "\"tags\":\"".htmlspecialchars($row["Tags"],ENT_COMPAT)."\",";
	echo "\"country\":\"".htmlspecialchars($row["Country"],ENT_COMPAT)."\",";
	echo "\"subcountry\":\"".htmlspecialchars($row["Subcountry"],ENT_COMPAT)."\",";
	echo "\"language\":\"".htmlspecialchars($row["Language"],ENT_COMPAT)."\",";
	echo "\"votes\":".htmlspecialchars($row["Votes"],ENT_COMPAT).",";
	echo "\"negativevotes\":".htmlspecialchars($row["NegativeVotes"],ENT_COMPAT);
	if (isset($row["ClickID"]))
		echo ",\"clickid\":".htmlspecialchars($row["ClickID"],ENT_QUOTES);
	if (isset($row["ClickTimestamp"]))
		echo ",\"clicktimestamp\":\"".htmlspecialchars($row["ClickTimestamp"],ENT_QUOTES)."\"";
	if (isset($row["clickcount"]))
		echo ",\"clickcount\":".htmlspecialchars($row["clickcount"],ENT_QUOTES);
	echo "}";
}

function escape_string($str)
{
	global $db;
	if (get_magic_quotes_gpc() == 1)
		return mysql_real_escape_string(stripslashes($str),$db);
	else
		return mysql_real_escape_string($str,$db);
}

function openDB()
{
	global $db;
	$db = mysql_connect("localhost","root","") or die("could not connect");
	mysql_select_db("radio") or die("could not change to database");
	if( !mysql_num_rows( mysql_query("SHOW TABLES LIKE 'Station'")))
	{
		mysql_query("CREATE TABLE Station(StationID INT NOT NULL AUTO_INCREMENT,Primary Key (StationID),Name TEXT,Url TEXT,Homepage TEXT,Favicon TEXT,Creation TIMESTAMP,Country VARCHAR(50),Subcountry VARCHAR(50),Language VARCHAR(50),Tags TEXT,Votes INT DEFAULT 0,NegativeVotes INT DEFAULT 0,Source VARCHAR(20))") or die("could not create table");
	}
	if( !mysql_num_rows( mysql_query("SHOW TABLES LIKE 'StationHistory'")))
	{
		mysql_query("CREATE TABLE StationHistory(StationChangeID INT NOT NULL AUTO_INCREMENT, Primary Key (StationChangeID),StationID INT NOT NULL,Name TEXT,Url TEXT,Homepage TEXT,Favicon TEXT,Creation TIMESTAMP,Country VARCHAR(50),Subcountry VARCHAR(50),Language VARCHAR(50),Tags TEXT,Votes INT DEFAULT 0,NegativeVotes INT DEFAULT 0)") or die("could not create table");
	}
	if( !mysql_num_rows( mysql_query("SHOW TABLES LIKE 'IPVoteCheck'")))
	{
		mysql_query("CREATE TABLE IPVoteCheck(CheckID INT NOT NULL AUTO_INCREMENT,Primary Key (CheckID),IP VARCHAR(15) NOT NULL,StationID INT NOT NULL, VoteTimestamp TIMESTAMP)") or die("could not create table");
	}
	if( !mysql_num_rows( mysql_query("SHOW TABLES LIKE 'StationClick'")))
	{
		mysql_query("CREATE TABLE StationClick(ClickID INT NOT NULL AUTO_INCREMENT,Primary Key (ClickID),StationID INT, ClickTimestamp TIMESTAMP)") or die("could not create table");
	}
	if( !mysql_num_rows( mysql_query("SHOW TABLES LIKE 'TagCache'")))
	{
		mysql_query("CREATE TABLE TagCache(TagName VARCHAR(100) NOT NULL,Primary Key (TagName))") or die("could not create table");
	}
}

function backupStation($stationid)
{
// backup old content
	mysql_query("INSERT INTO StationHistory(StationID,Name,Url,Homepage,Favicon,Country,Language,Tags,Votes,NegativeVotes,Creation) SELECT StationID,Name,Url,Homepage,Favicon,Country,Language,Tags,Votes,NegativeVotes,Creation FROM Station WHERE StationID=".escape_string($stationid));
}

function addStation($name,$url,$homepage,$favicon,$country,$language,$tags,$subcountry)
{
	mysql_query("DELETE FROM Station WHERE Url='".escape_string($url)."'");
	mysql_query("INSERT INTO Station(Name,Url,Homepage,Favicon,Country,Language,Tags,Subcountry) VALUES('".escape_string($name)."','".escape_string($url)."','".escape_string($homepage)."','".escape_string($favicon)."','".escape_string($country)."','".escape_string($language)."','".escape_string($tags)."','".escape_string($subcountry)."')");
}

function editStation($stationid,$name,$url,$homepage,$favicon,$country,$language,$tags,$subcountry)
{
	backupStation($stationid);
	// update values
	mysql_query("UPDATE Station SET Name='".escape_string($name)."',Url='".escape_string($url)."',Homepage='".escape_string($homepage)."',Favicon='".escape_string($favicon)."',Country='".escape_string($country)."',Language='".escape_string($language)."',Tags='".escape_string($tags)."',Subcountry='".escape_string($subcountry)."',Creation=Now() WHERE StationID=".escape_string($stationid));
}

function deleteStation($stationid)
{
	if (trim($stationid) != "")
	{
		backupStation($stationid);
		mysql_query("DELETE FROM Station WHERE StationID=".escape_string($stationid));
	}
}

function IPVoteChecker($id)
{
	$ip = $_SERVER["REMOTE_ADDR"];

	// delete ipcheck entries after 10 minutes
	mysql_query("DELETE FROM IPVoteCheck WHERE TIME_TO_SEC(TIMEDIFF(Now(),VoteTimestamp))>10*60");

	// was there a vote from the ip in the last 10 minutes?
	if (! mysql_num_rows( mysql_query("SELECT * FROM IPVoteCheck WHERE StationID=".$id." AND IP='".$ip."'")))
	{
		// if no, then add new entry
		mysql_query("INSERT INTO IPVoteCheck(IP,StationID) VALUES('".$ip."',".$id.")");
		return true;
	}
	return false;
}

function SongAddedChecker($singerid,$song)
{
	$result = mysql_query("SELECT MusicID FROM Music WHERE SingerID=".$singerid." AND Title='".escape_string($song)."'");
	if (!mysql_num_rows($result))
	{
		mysql_query("INSERT INTO Music(SingerID,Title) VALUES(".$singerid.",'".escape_string($song)."')");
		return mysql_insert_id();
	}else
	{
		$row = mysql_fetch_row($result);
		return $row[0];
	}
}

function SingerAddedChecker($singer)
{
	$result = mysql_query("SELECT SingerID FROM Singer WHERE Name='".escape_string($singer)."'");
	if (!mysql_num_rows($result))
	{
		mysql_query("INSERT INTO Singer(Name) VALUES('".escape_string($singer)."')");
		return mysql_insert_id();
	}else
	{
		$row = mysql_fetch_row($result);
		return $row[0];
	}
}

function MusicAddedChecker($title)
{
	$arr = split("-",$title);
	if (count($arr) == 2)
	{
		$singer = $str = mb_convert_case(trim($arr[0]), MB_CASE_TITLE, "UTF-8");
		$song = $str = mb_convert_case(trim($arr[1]), MB_CASE_TITLE, "UTF-8");
		$singerid = SingerAddedChecker($singer);
		if ($singerid != -1)
		{
			$songid = SongAddedChecker($singerid,$song);
			return $songid;
		}
	}
	return -1;
}

function clickedStationID($id)
{
	mysql_query("INSERT INTO StationClick(StationID) VALUES(".$id.")");
	return 1;
}

function voteForStation($id)
{
	if (!IPVoteChecker($id)) return FALSE;
	mysql_query("UPDATE Station SET Votes=Votes+1 WHERE StationID=".$id);
	print_station_by_id($id);

	return TRUE;
}


function negativeVoteForStation($id)
{
	if (!IPVoteChecker($id)) return FALSE;
	mysql_query("UPDATE Station SET NegativeVotes=NegativeVotes+1 WHERE StationID=".$id);
	print_station_by_id($id);
	//mysql_query("DELETE FROM Station WHERE NegativeVotes>5");
	return TRUE;
}

function print_station_by_id($id){
	$format = isset($_GET["format"])?$_GET["format"]:"xml";
        $result = mysql_query("SELECT * from Station WHERE Station.StationID=".$id);
        if (!$result)
        {
            echo str(mysql_error());
        }
        else
        {
                print_output_header($format);
                if ($format == "xml")
                {
                        while ($row = mysql_fetch_assoc($result))
                        {
                                print_station_xml($row);
                        }
                }
                if ($format == "json")
                {
                        $i = 0;
                        while ($row = mysql_fetch_assoc($result))
                        {
                                if ($i > 0)
                                        print_output_item_arr_sep($format);

                                print_station_json($row);
                                $i++;
                        }
                }

                print_output_footer($format);
        }
}
?>

