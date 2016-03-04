[
<?php
	require("../db.php");

	openDB();

	$result = mysql_query("SELECT TagName FROM TagCache WHERE TagName LIKE '%".escape_string($_REQUEST["term"])."%' ORDER BY TagName");
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
		echo '{ "value":"'.$row["TagName"].'"}';
	}
?>
]
