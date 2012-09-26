<?php
  include 'config.php';

  require_once 'Mobile_Detect.php';
  $detect = new Mobile_Detect();
  $isMobile = $detect->isMobile(); 

  if (array_key_exists('zoom', $_GET)) {
    $initialZoom = $_GET['zoom'];
  } else {
    $initialZoom = DEFAULT_ZOOM;
  }

  if (array_key_exists('lat', $_GET) && array_key_exists('lon', $_GET)) {
    $initialLat = $_GET['lat'];
    $initialLon = $_GET['lon'];
  } else {
    $initialLat = DEFAULT_LAT;
    $initialLon = DEFAULT_LON;
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
    /* Disable unwanted scaling */
    if ($isMobile) {
  ?>
      <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <?php
    }
  ?>
</head>
<body>
<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.css" />
<!--[if lte IE 8]>
    <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.4/leaflet.ie.css" />
<![endif]-->
<link rel="stylesheet" href="Icon.Label.css" />

<style>
<?php
  /* Set the map in fullscreen mode on mobile devices */
  if ($isMobile) {
?>
body {
    padding: 0;
    margin: 0;
}
html, body, #map {
    height: 100%;
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
  echo "var initialLat=$initialLat\n";
  echo "var initialLon=$initialLon\n";
  echo "var initialZoom=$initialZoom\n";
  echo "var debug='$debug'\n";
  if ($isMobile) {
    echo "var isMobile=true;\n";
  } else {
    echo "var isMobile=false;\n";
  }
?>
</script>
<script src="http://cdn.leafletjs.com/leaflet-0.4/leaflet.js"></script>
<script src="Icon.Label.js"></script>

<div id="map" style="height:500px"></div>

<script src="leafletembed.js"></script>
</body>
</html>
<?php
header('Content-type: text/html; charset="UTF-8"');
?>
