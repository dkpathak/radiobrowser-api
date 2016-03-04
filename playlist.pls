<?
	header ("content-type: audio/x-scpls");

	require("db.php");

	openDB();
?>
[playlist]

<?
if (isset($_GET["id"]))
{
    $result = mysql_query("SELECT * FROM Station WHERE StationID=".$_GET["id"]);
	if (!$result) {
	    echo str(mysql_error());
	    exit;
	}

	while ($row = mysql_fetch_assoc($result))
	{
		print("File1=".$row["Url"]."\n");
		print("Title1=".$row["Name"]);
	}
}

?>
