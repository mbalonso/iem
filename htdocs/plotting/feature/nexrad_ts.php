<?php
include("../../../config/settings.inc.php");
include("$rootpath/include/database.inc.php");
$dbconn = iemdb('access');
include("$rootpath/include/mlib.php");

$rs = pg_query($dbconn, "SELECT valid, avg(tmpf) as t from current_log 
      WHERE network = 'KCCI' and tmpf BETWEEN 40 and 100 and 
      valid BETWEEN '2009-08-20 11:00' and '2009-08-20 22:00' 
      GROUP by valid ORDER by valid ASC");
$tmpf = Array();
$times = Array();
for ($i=0;$row=@pg_fetch_array($rs,$i);$i++){
  $tmpf[] = $row["t"];
  $times[] = strtotime( $row["valid"] );
}

$radtimes = Array();
$n0r = Array();
$fc = file('ames.txt');
while (list ($line_num, $line) = each ($fc)) {
  $tokens = split (" ", $line);
  $radtimes[] = strtotime( $tokens[0] ." ". $tokens[1] ) - (5*3600);
  $n0r[] = floatval($tokens[2]);
}

include ("$rootpath/include/jpgraph/jpgraph.php");
include ("$rootpath/include/jpgraph/jpgraph_line.php");
include ("$rootpath/include/jpgraph/jpgraph_date.php");


// Create the graph. These two calls are always required
$graph = new Graph(620,480,"example1");
$graph->SetScale("datlin");
$graph->SetY2Scale("lin");
$graph->img->SetMargin(47,45,30,60);
$graph->SetColor("white");
$graph->SetMarginColor("white");
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetLabelFormatString("h A", true);
//$graph->xaxis->scale->SetDateFormat("M d h A");
$graph->xaxis->SetPos("min");

$graph->yaxis->SetColor("red");
$graph->yaxis->title->SetColor("red");
$graph->y2axis->SetColor("blue");
$graph->y2axis->title->SetColor("blue");
$graph->xaxis->SetTitleMargin(30);
$graph->yaxis->SetTitleMargin(32);

$graph->yaxis->title->SetFont(FF_FONT2,FS_BOLD,16);
$graph->y2axis->title->SetFont(FF_FONT2,FS_BOLD,16);
$graph->xaxis->title->SetFont(FF_FONT2,FS_BOLD,16);
$graph->xaxis->SetTitle("20 August 2009");
$graph->y2axis->SetTitle("Iowa Areal Coverage [%]");
$graph->yaxis->SetTitle("Mean Temperature [F]");
$graph->title->Set("Iowa Areal Mean Temperature\n + Area of > 5 dB Reflectivity");

//  $graph->tabtitle->SetFont(FF_FONT1,FS_BOLD,16);

  $graph->legend->SetLayout(LEGEND_HOR);
  $graph->legend->SetPos(0.01,0.07, 'right', 'top');
//  $graph->legend->SetLineSpacing(3);

  $graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCEE@0.5');
  $graph->ygrid->Show();
  $graph->xgrid->Show();

$l = new LinePlot($tmpf, $times);
$l->SetColor("red");
$graph->Add($l);

// Create the linear plot
$lineplot2=new LinePlot($n0r,$radtimes);
//$lineplot2->SetLegend("NEXRAD");
$lineplot2->SetFillColor("blue");
$lineplot2->SetWeight(3);
$graph->AddY2($lineplot2);


// Display the graph
$graph->Stroke();
?>
