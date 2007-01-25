<div style="float: left; width: 200px; background: #eee; margin: 10px; padding: 5px; ">
<h3><?php echo $metadata["city"]; ?> Links:</h3>
<?php
$o = Array(
  "base" => Array("name" => "Information", "uri" => "site.php?"),
  "current" => Array("name" => "Last Ob", "uri" => "current.php?"),
  "pics" => Array("name" => "Photographs", "uri" => "pics.php?"),
  "cal" => Array("name" => "Calibration", "uri" => "cal.phtml?"),
  "loc" => Array("name" => "Location Maps", "uri" => "mapping.php?"),
  "neighbors" => Array("name" => "Neighbors", "uri" => "neighbors.php?"),
  "7dayhilo" => Array("name" => "7 Day High/Low Plot", "uri" => "plot.php?prod=0&"),
  "monthhilo" => Array("name" => "Month High/Low Plot", "uri" => "plot.php?prod=1&"),
  "windrose" => Array("name" => "Wind Roses", "uri" => "windrose.phtml?"),
);

while (list($key,$val) = each($o))
{
  $uri = sprintf("%sstation=%s&network=%s", $val["uri"], $station, $network);
  echo "<div style=\"";
  if ($current == $key) echo "-moz-border-radius: 1em; border: 1px solid #c00 ;";
  echo "width: 100%; padding-left: 10px;\"><a href=\"$uri\">". $val["name"] ."</a></div>";
}
?>

<p>Other Sites in network:
<form method="GET">
<input type="hidden" name="network" value="<?php echo $network; ?>">
<?php
 include_once("$rootpath/include/selectWidget.php");
 $sw2 = new selectWidget("$rooturl/sites/site.php", "$rooturl/sites/site.php?", $network);
 echo $sw2->siteSelectInterface($station);

?><br /> <a href="locate.php?network=<?php echo $network; ?>">select from map</a>
</form>


</div>
