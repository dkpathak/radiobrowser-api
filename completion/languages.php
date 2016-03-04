[
<?php
	require("../db.php");

	openDB();

	$result = mysql_query("SELECT Language, COUNT(*) AS StationCount FROM Station WHERE Language LIKE '%".escape_string($_REQUEST["term"])."%' AND Language<>'' GROUP BY Language ORDER BY Language");
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
		echo '{ "value":"'.$row["Language"].'", "stationcount":"'.$row["StationCount"].'"}';
	}
?>
]
