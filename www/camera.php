 <?php 

   include 'config.php';

   define(MAX_POINTS_FOR_QUICKHULL, 3000);

   class OsmPoint {
     var $id;
     var $lat;
     var $lon;

     function __construct($valId, $valLat, $valLon) {
       $this->id = $valId;
       $this->lat = $valLat;
       $this->lon = $valLon;
     }
   }

/**
 * Generic write to file function.
 * 
 * @param string $file
 * @param string $content 
 */
function writeToFile($file, $content){
  
    if (!$handle = fopen($file, 'a')) {
            echo "Cannot open file ($file)";
            exit;
    }

    if (fwrite($handle, $content) === FALSE) {
            echo "Cannot write to file ($file)";
            exit;
    }

    fclose($handle);

}

   function mergeNeighbor(&$clusterGrid, $posCell, $pos) {
     global $divDiag2;

     if (! array_key_exists($pos, $clusterGrid)) {
       /* the neighbor is empty */
       return;
     }

     $neighbor = $clusterGrid[$pos];
     if ($neighbor['count'] == 0) {
       /* The neighbor is merged to an other cell, yet */
       return;
     }

     $cell = $clusterGrid[$posCell];

     /* Calculate the (square of) distance from the cell to its neighbor */
     $lonDist = ($cell['longitude']/$cell['count']) - ($neighbor['longitude']/$neighbor['count']);
     $latDist = ($cell['latitude']/$cell['count'])  - ($neighbor['latitude']/$neighbor['count']);
     $dist = ($lonDist * $lonDist) + ($latDist * $latDist);
     
     if ($dist < $divDiag2 / 10) {
       $count = $cell['count'] + $neighbor['count'];
       $clusterGrid[$posCell]['latitude'] = $cell['latitude'] + $neighbor['latitude'];
       $clusterGrid[$posCell]['longitude'] = $cell['longitude'] + $neighbor['longitude'];
       $clusterGrid[$posCell]['count'] = $count;
       $clusterGrid[$posCell]['points'] = array_merge($cell['points'], $neighbor['points']);
       /* Invalidate the merged neighbor */
       $clusterGrid[$pos]['count'] = 0;
     }
   }

   function mergeNeighborHood(&$clusterGrid, $pos) {
     global $divWCount, $divHCount;

     if ($clusterGrid[$pos]['count'] == 0) {
       /* cell merged yet */
       return;
     }

     $latLon=explode(',', $clusterGrid[$pos]['grid'] );
     if ($latLon[0] > 0 && $latLon[1] < $divHCount - 1) {
       if ($latLon[0] > 0) {
         $posNeigh = ($latLon[0] - 1) . ',' . ($latLon[1] + 1);
         mergeNeighbor($clusterGrid, $pos, $posNeigh);
       }

       $posNeigh = $latLon[0] . ',' . ($latLon[1] + 1);
       mergeNeighbor($clusterGrid, $pos, $posNeigh);

       if ($latLon[0] < $divWCount - 1) {
         $posNeigh = ($latLon[0] + 1) . ',' . ($latLon[1] + 1);
         mergeNeighbor($clusterGrid, $pos, $posNeigh);
       }
     } 

     if ($latLon[0] < $divWCount - 1) {
       $posNeigh = ($latLon[0] + 1) . ',' . $latLon[1];
       mergeNeighbor($clusterGrid, $pos, $posNeigh);
     } 
   }

   /* Recursive calculation of the quick hull algo 
      $isBottom : 1 if bottom point is searched, -1 if top point is searched */
   function quickHullCalc(&$pointList, $count, $minPoint, $maxPoint, $isBottom) {

     $msg= "Quick count=".$count.", min=(".$minPoint->id.",".$minPoint->lat.",".$minPoint->lon."), max=(".$minPoint->id.",".$minPoint->lat.",".$minPoint->lon."), isBottom=".$isBottom."\n";

     $farthestPoint=null;
     $farthestDist=0;
     $outsideList = array();
     $outsideListCount = 0;

     if ($maxPoint->lon != $minPoint->lon) {
       /* Get the line equation as y = mx + p */
       $m =   ($maxPoint->lat - $minPoint->lat) 
            / ($maxPoint->lon - $minPoint->lon);
       $p = ($maxPoint->lat * $minPoint->lon
              - $minPoint->lat * $maxPoint->lon)
            / ($minPoint->lon - $maxPoint->lon);
       
     } else {
       /* The line equation is y = p */
       $m = null;
       $p = $minPoint->lat;
       $coef = (($minPoint->lon > $maxPoint->lon) ? 1 : -1);
     }

     /* For each point, check whether : 
        - it is on the right side of the line
        - it is the farthest from the line
      */
     foreach ($pointList as $point) {
       if (isset($m)) {
         $dist = $isBottom * ($m * $point->lon - $point->lat + $p);
       } else {
         $dist = $coef * ($point->lon - $p);
       }
       if ($dist > 0
           && $point->id != $minPoint->id
           && $point->id != $maxPoint->id) {

         array_push($outsideList, $point);
         $outsideListCount++;

         if ($dist > $farthestDist) {
           $farthestPoint = $point;
           $farthestDist = $dist;
         }
       }
     }

     if ($outsideListCount == 0) {
       return array($minPoint);

     } else if ($outsideListCount == 1) {
       return array($minPoint, $outsideList[0]);

     } else {
       return array_merge(
                quickHullCalc($outsideList, $outsideListCount, $minPoint, $farthestPoint, $isBottom),
                quickHullCalc($outsideList, $outsideListCount, $farthestPoint, $maxPoint, $isBottom));
     }
   }

   /* This function receives a list of points [lat, lon] and returns a list of points [lat, lon] 
      representing the minimal convex polygon containing the points */
   function quickHull(&$pointList, $count) {
     
     $msg= "Quick count=".$count."\n";

     if ($count == 0) {
       return array();
     } else if ($count == 1) {
       return array($pointList[0]);
     } else if ($count == 2) {
       return array($pointList[0], $pointList[1]);
     }
 
     /* retrieves the min and max points on the x axe */
     $minPoint = $pointList[0];
     $maxPoint = $pointList[0];
     
     foreach ($pointList as $point) {
       if ($point->lon < $minPoint->lon
           || ($point->lon == $minPoint->lon
               && $point->lat < $minPoint->lat)) {
         $minPoint = $point;
       }
       if ($point->lon > $maxPoint->lon
           || ($point->lon == $maxPoint->lon
               && $point->lat > $maxPoint->lat)) {
         $maxPoint = $point;
       }
     }

     $bottomPoints = quickHullCalc($pointList, $count, $minPoint, $maxPoint, 1);
     $topPoints    = quickHullCalc($pointList, $count, $maxPoint, $minPoint, -1);
     return array_merge($bottomPoints, $topPoints);
   }
  
   $GRID_MAX_ZOOM=16;
   $GRID_CELL_PIXEL=90;

   /* Check the parameters */

   $debug='no';
   if (array_key_exists('debug', $_GET)) {
     $debug = $_GET['debug'];
     if ($debug != 'no' && $debug != 'yes' && $debug != '') {
       $debug='no';
     }
   }

   if (! array_key_exists('zoom', $_GET) || ! array_key_exists('bbox', $_GET)
       || ! array_key_exists('width', $_GET) || ! array_key_exists('height', $_GET)) {
     header('Content-type: application/json');
     $result='{"error":"bbox, zoom, width and height parameters are required. '
                       .(array_key_exists('zoom', $_GET) ? '' : 'zoom is empty. ')
                       .(array_key_exists('bbox', $_GET) ? '' : 'bbox is empty. ')
                       .(array_key_exists('width', $_GET) ? '' : 'width is empty. ')
                       .(array_key_exists('height', $_GET) ? '' : 'height is empty. ')
                       .'"}';
     echo $result;
     exit;
   }

   $zoom = $_GET['zoom'];
   if (! is_numeric($zoom) || intval($zoom) < 1 || intval($zoom) > 18) {
     header('Content-type: application/json');
     $result='{"error":"Unexpected zoom value : '
                       .htmlentities($zoom)
                       .'"}';
     echo $result;
     exit;
   }

   
   $bbox = explode(',', $_GET['bbox']); 
   if (   !is_numeric($bbox[0]) || $bbox[0] > 180
       || !is_numeric($bbox[2]) || $bbox[2] < -180
       || !is_numeric($bbox[1]) || $bbox[1] < -90  || $bbox[1] > 90
       || !is_numeric($bbox[3]) || $bbox[3] < -90  || $bbox[3] > 90) {
     header('Content-type: application/json');
     $result='{"error":"Unexpected bbox longitude and latitude values : '
                       .'lat ['.htmlentities($bbox[1]).', '.htmlentities($bbox[3]).'], '
                       .'lon ['.htmlentities($bbox[0]).', '.htmlentities($bbox[2]).']'
                       .'"}';
     echo $result;
     exit;
   }

   if ($bbox[0] >= $bbox[2]) {
     header('Content-type: application/json');
     $result='{"error":"min longitude greater than max longitude : '
                       .'lon ['.htmlentities($bbox[0]).', '.htmlentities($bbox[2]).']'
                       .'"}';
     echo $result;
     exit;
   }

   if ($bbox[1] >= $bbox[3]) {
     header('Content-type: application/json');
     $result='{"error":"min latitude greater than max latitude : '
                       .'lat ['.htmlentities($bbox[1]).', '.htmlentities($bbox[3]).']'
                       .'"}';
     echo $result;
     exit;
   }

   $lonMin = $bbox[0];
   $lonMax = $bbox[2];

   $latMin = $bbox[1];
   $latMax = $bbox[3];
  
   /* Indicates whether the map displays the -180/180° longitude */
   $pixelWidth = (int) $_GET['width'];
   $pixelHeight = (int) $_GET['height'];

   if ($pixelWidth == 0 || $pixelHeight == 0) {
     header('Content-type: application/json');
     $result='{"error":"Width or Height is null : '
                       .htmlentities($pixelWidth).'x'.htmlentities($pixelHeight)
                       .'"}';
     echo $result;
     exit;
   }

   $lonWidth = $lonMax - $lonMin;
   $latHeight= $latMax - $latMin;

   $divWCount = ((int) ($pixelWidth / $GRID_CELL_PIXEL)) + 1;
   $divHCount = ((int) ($pixelHeight / $GRID_CELL_PIXEL)) + 1;

   $divWidth= $lonWidth / $divWCount;
   $divHeight= $latHeight / $divHCount;
   $divDiag2=($divWidth * $divWidth) + ($divHeight * $divHeight);

   $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
   if($mysqli->connect_errno) {
     header('Content-type: application/json');
     $result='{"error":"Error while connecting to db : ' . $mysqli->error . '"}';
     echo $result;
     exit;
   }

   /* Select the nodes to be returned, and cluster them on a grid if necessary */
   $rqtLonMin = $lonMin;
   $rqtLonMax = $lonMax;

   if ($rqtLonMax - $rqtLonMin > 360) {
     $rqtLonMin = -180;
     $rqtLonMax = 180;
   }

   if ($rqtLonMin >= -180 && $lonMax <= 180) {
     $sql="SELECT  id, latitude, longitude
             FROM  position
            WHERE  latitude  between ? and ?
              AND  longitude between ? and ?";

   } else {
     $sql="SELECT  id, latitude, longitude
             FROM  position
            WHERE  latitude  between ? and ?
              AND  (longitude between ? and 1800000000 OR longitude between -1800000000 and ?)";

     while ($rqtLonMin < -180) {
       $rqtLonMin += 360;
     }
     while ($rqtLonMax > 180) {
       $rqtLonMax -= 360;
     }
   }

   $rqtLonMin = bcmul($rqtLonMin, 10000000, 0);
   $rqtLonMax = bcmul($rqtLonMax, 10000000, 0);
   $rqtLatMin = bcmul($latMin, 10000000, 0);
   $rqtLatMax = bcmul($latMax, 10000000, 0);

   $resArray = array();
   $nbFetch=0;

   if ($stmt = $mysqli->prepare($sql)) {
     $stmt->bind_param('iiii', $rqtLatMin, $rqtLatMax, $rqtLonMin, $rqtLonMax);

     $stmt->execute();

     $stmt->bind_result($id, $latitude, $longitude);

     while ($stmt->fetch()) {
       $nbFetch++;

       $latitude = bcdiv($latitude, 10000000, 7);
       $longitude = bcdiv($longitude, 10000000, 7);

       while ($longitude < $lonMin) {
         $longitude += 360;
       }
       while ($longitude > $lonMax) {
         $longitude -= 360;
       }

       /* initialisation du point courant */
       if ($zoom >= $GRID_MAX_ZOOM) {
         array_push($resArray, array('points' => array(new OsmPoint($id, $latitude, $longitude)), 
                                     'latitude' => $latitude, 
                                     'longitude' => $longitude, 
                                     'count' => 1 ));

       } else {
         $posLat = (int) (($latitude - $latMin) / $divHeight);
         $posLon = (int) (($longitude - $lonMin) / $divWidth);
         $pos = $posLon . ',' . $posLat;

         if (! array_key_exists($pos, $resArray)) {
           $resArray[$pos] = array('points' => array(), 'count' => 0,
                                   'latitude' => 0, 'longitude' => 0, 
                                   'grid' => $pos);
         } 

         $elt = new OsmPoint($id, $latitude, $longitude);
         array_push($resArray[$pos]['points'], $elt);

         /* Incrément du nombre de points */
         $resArray[$pos]['count']++;
         $resArray[$pos]['latitude'] += $latitude;
         $resArray[$pos]['longitude'] += $longitude;
       }
     }
     
     $stmt->close();

   } else {
     $mysqli->close();
     header('Content-type: application/json');
     $resultat='{"erreur":"Erreur de préparation de la requête : ' . $mysqli->error . '"}';
     echo $resultat;
     exit;
   }
     

   /* Unify some clusters if nodes center are near */  
   $pointsCount = 0;
   if ($zoom < $GRID_MAX_ZOOM) {

     foreach($resArray as $val) {
       mergeNeighborhood($resArray, $val['grid']);
       $pointsCount += $val['count'];
     }
   }

   $resultat='['; 
   $separateur='';

   /* Ecriture des rectangles de regroupement */
   if ($debug == 'yes') {
     $resultat = $resultat.'{"nbFetch":"' . $nbFetch . '",' 
                          . '"bbox":"[' . $bbox[0] . ', ' . $bbox[1] . ', ' 
                                        . $bbox[2] . ', ' . $bbox[3] . ']",'
                          . '"zoom":"' . $zoom . '",'
                          . '"latMin":"' . $latMin . '",'
                          . '"latMax":"' . $latMax . '",'
                          . '"lonMin":"' . $lonMin . '",'
                          . '"lonMax":"' . $lonMax . '",'
                          . '"rqtLonMin":"' . $rqtLonMin . '",'
                          . '"rqtLonMax":"' . $rqtLonMax . '",'
                          . '"lonWidth":"' . $lonWidth . '",'
                          . '"latHeight":"' . $latHeight . '",'
                          . '"divWidth":"' . $divWidth . '",'
                          . '"divHeight":"' . $divHeight . '"'
                          .'}';
     $separateur = ',';

     if ($zoom < $GRID_MAX_ZOOM) {
       for ($i=0 ; $i < $divWCount; $i++) {
         for ($j=0 ; $j < $divHCount ; $j++) {
           $rectLonMin= $lonMin + $i * $divWidth;
           $rectLonMax= $lonMin + ($i + 1) * $divWidth;
           
           $resultat=$resultat.$separateur.'{"rectangle":"yes",'
                     .'"lonMin":"'. $rectLonMin . '",'
                     .'"lonMax":"'. $rectLonMax . '",'
                     .'"latMin":"'. ($latMin + $j * $divHeight) . '",'
                     .'"latMax":"'. ($latMin + ($j + 1) * $divHeight) . '"}';
         }
       }
     }
   }

   /* Ecriture des points sélectionnés */
   $sql="SELECT  k, v 
           FROM  tag
          WHERE  id = ?
            AND  k NOT IN ('lat', 'lon')";

   $stmt = $mysqli->prepare($sql);
   $stmt->bind_param("d", $id);
   $stmt->bind_result($k, $v);
 
   /* Ecriture des points regroupés ou non */
   foreach($resArray as $val) {
     if ($val['count'] > 0) {
       $resultat=$resultat.$separateur
                .'{"lat":"' . ($val['latitude']  / $val['count']) . '"'
                .',"lon":"' . ($val['longitude'] / $val['count']) . '"';

       if ($val['count'] == 1) {
      
         $id = $val['points'][0]->id;
         $resultat=$resultat
                  .',"id":"'.$id.'"';

         $stmt->execute();

         while($stmt->fetch()) {
           $resultat=$resultat
                    .',"'.htmlentities($k).'":"'.htmlentities($v, ENT_COMPAT, 'UTF-8').'"';
         }

       } else {
         $resultat=$resultat
                  .',"count":"'.$val['count'].'"'
                  .',"multi":"yes"'
                  .',"poly":[';
         
         if ($pointsCount < MAX_POINTS_FOR_QUICKHULL) {
           $convexPoly = quickHull($val['points'], $val['count']);
           $sepPoly='';
           foreach($convexPoly as $point) {
             $resultat=$resultat.$sepPoly.'{'
                      .'"lat":"'.$point->lat.'"'
                      .',"id":"'.$point->id.'"'
                      .',"lon":"'.$point->lon.'"}';
             $sepPoly=',';
           }
         }
         $resultat=$resultat.']';

       }
       $resultat = $resultat.'}';
       $separateur=',';
     }
   }
   $stmt->close();

   $resultat = $resultat . ']';

   $mysqli->close();

   header('Content-type: application/json; Charset : utf-8');
   echo $resultat;
?>
