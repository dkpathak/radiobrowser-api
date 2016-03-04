[
<?php
	require("../db.php");

	openDB();

	$term = "";
	if (isset($_REQUEST["term"]))
		$term = escape_string($_REQUEST["term"]);
	$result = mysql_query("SELECT Country, COUNT(*) AS StationCount FROM Station WHERE Country LIKE '%".$term."%' AND Country<>'' GROUP BY Country ORDER BY Country");
	if (!$result) {
	    echo str(mysql_error());
	    exit;
	}

	$i = 0;
	while ($row = mysql_fetch_assoc($result))
	{
		if ($i > 0){
			echo ",";
		}
		$i=$i+1;
		echo '{ "value":"'.$row["Country"].'", "stationcount":"'.$row["StationCount"].'" }';
	}
?>
]
