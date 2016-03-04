<?
if (!isset($_REQUEST["format"]) || !isset($_REQUEST["stationid"])){
	echo "parameters missing!";
	exit();
}

require("db.php");
openDB();

$stationid = $_REQUEST["stationid"];
$foundStation = FALSE;

{
        $result = mysql_query("SELECT Name, Url FROM Station WHERE StationID=".$stationid);
        if (!$result) {
		echo mysql_error();
		exit();
        }

        while ($row = mysql_fetch_assoc($result))
        {
		$url = $row["Url"];
		$stationname = $row["Name"];
		$foundStation = TRUE;
        }
}


$format = $_REQUEST["format"];
if ($foundStation !== TRUE){
	http_response_code(404);
	exit();
}
$str_arr = split("\?",$url);
if (count($str_arr) > 1)
	$extension = strtolower(substr($str_arr[0],-4));
else
	$extension = strtolower(substr($url,-4));

// resolve playlists
if ($extension == ".m3u")
{
	$handle = fopen ($url, "r");
	if ($handle !== FALSE)
	{
		while (!feof($handle))
		{
			$buffer = fgets($handle, 4096);
			if (substr(trim($buffer),0,1) != "#")
			{
				$audiofile = trim($buffer);
				break;
			}
		}
		fclose ($handle);
	}
}else if ($extension == ".pls")
{
	$handle = fopen ($url, "r");
	if ($handle !== FALSE)
	{
		while (!feof($handle))
		{
			$buffer = fgets($handle, 4096);
			if (substr(trim($buffer),0,4) == "File")
			{
				$line_arr = split("=",$buffer);
				if (count($line_arr) == 2)
				$audiofile = trim($line_arr[1]);
				break;
			}
		}
		fclose ($handle);
	}
}else if ($extension == ".asx")
{
	$handle = fopen ($url, "r");
	if ($handle !== FALSE)
	{
		$contents = '';
		while (!feof($handle)) {
			$contents .= fread($handle, 8192);
		}
		fclose ($handle);

		$xml = simplexml_load_string(strtolower($contents));
		foreach ($xml->entry as $entry)
		{
			foreach ($entry->ref as $ref)
			{
				if (isset($ref["href"]))
				{
					$audiofile = $ref["href"];
				}
			}
		}
	}
}else
	$audiofile = $url;

//print("audiofile:".$audiofile);
if (isset($audiofile))
{
	$extension = strtolower(substr($audiofile,-4));

	// shoutcast handling
	if (substr($audiofile,-1) == "/")
		$audiofile .= ";stream.mp3";
	if (substr_count($audiofile,"/") == 2)
		$audiofile .= "/;stream.mp3";
	if ($format == "xml"){
		header('Content-Type: text/xml');
		echo "<?xml version=\"1.0\"?>";
		echo "<result><station id='".$stationid."' url='".$audiofile."'/></result>";
		clickedStationID($stationid);
	}else if ($format == "json"){
		header('Content-Type: application/json');
		echo "[{";
		echo "\"id\":\"$stationid\","; 
		echo "\"name\":\"$stationname\",";
		echo "\"url\":\"".$audiofile."\"";
		echo "}]";
		clickedStationID($stationid);
	}
	else if ($format == "pls"){
		header ("content-type: audio/x-scpls");
		print("[playlist]\n");

		print("File1=".$audiofile."\n");
		print("Title1=".$stationname);
		clickedStationID($stationid);
	} else {
		echo "unknown format";
	}
}else{
	http_response_code(404);
}
?>
