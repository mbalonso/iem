<?php

dl("php_mapscript.so");
include('../../../include/mlib.php');
include('../../../include/currentOb.php');
include('../../../include/allLoc.php');

$myStations = Array("DSM", "ALO", "DVN", "DEH", "DBQ", "BRL", "AMW"
  ,"SPW", "BRL", "OTM", "LWD", "CID", "MCW", "DNS", "SUX", "CBF");

$lats = Array();
$lons = Array();
$height = 350;
$width = 450;

foreach($myStations as $key => $value){
  $lats[$value] = $cities[$value]["lat"];
  $lons[$value] = $cities[$value]["lon"];
}

$lat0 = min($lats);
$lat1 = max($lats);
$lon0 = min($lons);
$lon1 = max($lons);


$map = ms_newMapObj("fancybase.map");

$pad = 0.2;
$lpad = 1;

//$map->setextent(-83760, -2587, 478797, 433934);
$map->setextent($lon0 - $lpad, $lat0 - $pad, $lon1 + $lpad, $lat1 + $pad);

$green = $map->addColor(0, 255, 0);
$blue = $map->addColor(0, 0, 255);
$black = $map->addColor(0, 0, 0);
$white = $map->addColor(255, 255, 255);

$currents = $map->getlayerbyname("currents");
$currents->set("status", MS_ON);

$counties = $map->getlayerbyname("counties");
$counties->set("status", MS_ON);

$sname = $map->getlayerbyname("sname");
$sname->set("status", MS_ON);

$stlayer = $map->getlayerbyname("states");
$stlayer->set("status", 1);

$st_cl = ms_newclassobj($stlayer);
$st_cl->set("outlinecolor", $green);
$st_cl->set("status", MS_ON);

$img = $map->prepareImage();

$ly = ms_newlayerobj($map);
$ly->set("status", MS_ON);
$ly->set("type", MS_LAYER_POINT);
$ly->setProjection("proj=latlong");

$cl = ms_newclassobj($ly);
$cl->set("status", MS_ON);
$cl->set("name", "ccc");
$cl->set("symbol", 1);
$cl->set("size", 10);
$cl->set("color", $green);
$cl->set("backgroundcolor", $green);

$lbl = $cl->label;
$lbl->set("type", MS_TRUETYPE);
$lbl->set("antialias", MS_OFF);
$lbl->set("font", "arial");
$lbl->set("size", 18);
$lbl->set("color", $black);
$lbl->set("position", MS_AUTO);
$lbl->set("force", MS_ON);

$ly2 = $map->getLayerByName("pointonly");
$ly2->set("status", MS_ON);
$ly2->setProjection("proj=latlong");

$now = time();
foreach($myStations as $key => $value){
  $bzz = currentOb($value);
  if (($now - $bzz["ts"]) < 3600){ 
   $pt = ms_newPointObj();
   $pt->setXY($cities[$value]["lon"], $cities[$value]["lat"], 0);
   $pt->draw($map, $currents, $img, 0, round($bzz["tmpf"],0) );
   $pt->free();

   $pt = ms_newPointObj();
   $pt->setXY($cities[$value]["lon"], $cities[$value]["lat"], 0);
   $pt->draw($map, $sname, $img, 0, $cities[$value]["city"] );
   $pt->free();
  }
}

  $ts = strftime("%I %p");

$stlayer->draw( $img);
$currents->draw($img);
$sname->draw($img);
$counties->draw($img);


//$pt = ms_newPointObj();
//$pt->setXY(-94, 42, 0);
//$pt->draw($map, $ly2, $img, 0, "");
//$pt->free();

//$layer2->draw($img);
//$ly->draw( $img);

//$img = $map->draw();
$map->drawLabelCache($img);

$url = $img->saveWebImage(MS_PNG, 0,0,-1);

$im = @imagecreatefrompng("/mesonet/www/html/". $url );

$Font = "arialblk.ttf";

// Figure out how big to draw the bottom box!
 $size = imagettfbbox(18, 0, $Font, $ts ." Current Temperatures");
 $dx = abs($size[2] - $size[0]);
 $dy = abs($size[5]);
 $ypad = 10;
 $xpad = 30;
 imagefilledrectangle ( $im, $width - $dx - $xpad, $height - $ypad - $dy, 
   $width, $height - $ypad, $blue);

 ImageTTFText($im, 18, 0, $width - $dx - $xpad + 5, $height - $ypad, 
   $white, $Font, $ts ." Current Temperatures");

ImagePng($im , "/mesonet/www/html/". $url );
ImageDestroy($im);


echo "<img src=\"$url\">";


?>


