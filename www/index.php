<?php
  include 'config.php';

  require_once 'Mobile_Detect.php';
  $detect = new Mobile_Detect();
  $isMobile = $detect->isMobile(); 
  $showMap=true;
  $showText=true;

  if ($isMobile) {
    if (array_key_exists('infos', $_GET)) {
      $showMap=false;
    } else {
      $showText=false;
    }
  }

  $language='en';
  $locale = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
  $lstLng = explode(',', $locale);
  foreach($lstLng as $lng) {
    $lng = substr(trim($lng), 0, 2);
    if ($lng == 'fr') {
      $language = 'fr';
      break;
    } else if ($lng == 'en') {
      $language = 'en';
      break;
    }
  }

  $initialIsDefault='true';

  if (array_key_exists('zoom', $_GET)) {
    $initialZoom = $_GET['zoom'];
    if (is_numeric($initialZoom) 
        && intval($initialZoom) >= 1 && intval($initialZoom) <= 18) {
      $initialIsDefault='false';
    } else {
      $initialZoom = DEFAULT_ZOOM;
    }
  } else {
    $initialZoom = DEFAULT_ZOOM;
  }

  if (array_key_exists('lat', $_GET) && array_key_exists('lon', $_GET)) {
    $initialLat = $_GET['lat'];
    $initialLon = $_GET['lon'];
    if ( is_numeric($initialLat) && is_numeric($initialLon)) { 
      $initialIsDefault='false';
    } else {
      $initialLat = DEFAULT_LAT;
      $initialLon = DEFAULT_LON;
    }
  } else {
    $initialLat = DEFAULT_LAT;
    $initialLon = DEFAULT_LON;
  }

  $initialLayer='';
  if (array_key_exists('layer', $_GET)) {
    $initialLayer = $_GET['layer'];
    if ($initialLayer != 'osm') {
      $initialLayer='';
    }
  }


  $debug = (array_key_exists('debug', $_GET) 
            && ($_GET['debug'] == 'yes' 
                || $_GET['debug'] == 'true')) ? 'yes' : 'no';
    
?>        
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8"/>
  <?php
    if ($language == "fr") { 
  ?>
    <title>La carte de la vidéosurveillance (extraite d'OpenStreetMap)</title>
  <?php
    } else {
  ?>
    <title>The CCTV map (extracted from OpenStreetMap)</title>
  <?php
    }
  ?>
  <?php
    /* Disable unwanted scaling */
    if ($isMobile && $showMap) {
  ?>
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <?php
    }
  ?>
</head>
<body>
<?php
  if ($showMap) {
?>
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.css" />
<!--[if lte IE 8]>
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.ie.css" />
<![endif]-->
<link rel="stylesheet" href="Icon.Label.css" />

<style>
body {
    padding: 0;
    margin: 0;
}
<?php
  if ($isMobile) {
    /* Set the map in fullscreen mode on mobile devices */
?>
html, body, #map {
    height: 100%;
}
<?php
  } else {
    /* Keep some space for the caption on other devices */
?>
html, body {
    height: 100%;
    width: 100%;
}
#map {
    height: 75%;
    width: 100%;
}
<?php
  }
?>
.sweet-deal-label {
  background-color: #FE57A1;
  background-color: rgba(254, 87, 161, 0.66);
  -moz-box-shadow: none;
  -webkit-box-shadow: none;
  box-shadow: none;
  color: #fff;
  font-weight: bold;
}

.popup-content {
  margin: 0px 0px 0px 2px;
}

</style>


<script language="javascript">
<?php
  echo "var initialLat=$initialLat;\n";
  echo "var initialLon=$initialLon;\n";
  echo "var initialZoom=$initialZoom;\n";
  echo "var initialIsDefault=$initialIsDefault;\n";
  echo "var debug='$debug'\n";
  if ($isMobile) {
    echo "var isMobile=true;\n";
  } else {
    echo "var isMobile=false;\n";
  }
  if ($initialLayer != '') {
    echo "var initialLayer='$initialLayer';\n";
  } else {
    echo "var initialLayer='mapquest';\n";
  }
?>
</script>
<script src="http://cdn.leafletjs.com/leaflet-0.4/leaflet.js"></script>
<script src="Icon.Label.js"></script>

<div id="map"></div>

<?php
if ($isMobile) {
?>
<div id="moreInfo" style="position:fixed;top:5px;right:5px">
<a href="index.php?infos=yes"/><img src="images/infos.png" width="35"/></a>
</div>
<?php
}
?>

<script src="leafletembed.js"></script>

<?php
}  /* if ($showMap) */

if ($showText) {
  if ($isMobile) {
?>
<div style="width:100%;text-align:center;background:SeaGreen"/>
<a href="index.php" style="color:white;font-size:200%">Retour à la carte / <i>Back to the map</i></a>
</div>
<?php    
} 
?>

<div style="width:100%;overflow:auto">
<?php
if ($language == "fr") { 
  include "text_fr.php";
} else {
  include "text_en.php";
}
?>
</div>
<?php
} /* if ($showText */
?>

</body>
</html>
<?php
header('Content-type: text/html; charset="UTF-8"');
?>
