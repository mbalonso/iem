<?php
 include_once("../../config/settings.inc.php");
 include_once("$rootpath/include/database.inc.php");
 include_once("$rootpath/include/station.php");
 /* Make sure all is well! */
 $station = isset($_GET["station"]) ? substr($_GET["station"],0,12) : "";
 $network = isset($_GET["network"]) ? $_GET["network"] : "";

$st = new StationData($station, $network);
$cities = $st->table;

 if (strlen($station) == 0)
 {
    header("Location: locate.php");
    die();
 }
 if (! array_key_exists($station, $cities))
 {
    header("Location: locate.php");
    die();
 }

 if (! isset($_GET["network"]) )
 {
    $network = $cities[$station]["network"];
 }
 $metadata = $cities[$station];
?>
