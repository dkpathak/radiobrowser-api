[
<?php
	require("../db.php");

	openDB();

	if (isset($_REQUEST["country"])){
		$result = mysql_query("SELECT Country, Subcountry, COUNT(*) AS StationCount FROM Station WHERE Country='".escape_string($_REQUEST["country"])."' AND Subcountry LIKE '%".escape_string($_REQUEST["term"])."%' AND Country<>'' AND Subcountry<>'' GROUP BY Country, Subcountry ORDER BY Subcountry");
	} else {
		$result = mysql_query("SELECT Country, Subcountry, COUNT(*) AS StationCount FROM Station WHERE Subcountry LIKE '%".escape_string($_REQUEST["term"])."%' AND Country<>'' AND Subcountry<>'' GROUP BY Country, Subcountry ORDER BY Subcountry");
	}
		
	if (!$result) {
	    echo str(mysql_error());
	    exit;
	}

	$i=0;
	while ($row = mysql_fetch_assoc($result))
	{
		if ($i > 0){
			echo ",";
		}
		$i=$i+1;
		echo '{ "value":"'.$row["Subcountry"].'", "country":"'.$row["Country"].'", "stationcount":"'.$row["StationCount"].'"}';
	}
?>
]
