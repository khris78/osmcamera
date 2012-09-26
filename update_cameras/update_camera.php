<?php

$elementTypes=array();
$count = 0;
$countDelete = 0;
$countModify = 0;
$countCreate = 0;
$mode = '';
$curNodeAttrs = null;
$curNodeTags = null;
$id = 0;
$latitude=0;
$longitude=0;

import "config.php"

$mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
if($mysqli->connect_errno) {
  echo "Error while connecting to db : $mysqli->error \n" ;
  exit(1);
}

$mysqli->autocommit(FALSE);

if (! ($deleteStmt = $mysqli->prepare("DELETE FROM position WHERE id=?"))) {
  echo 'Error while preparing delete statement : ' . $mysqli->error ;
  exit(1);
}

$deleteStmt->bind_param('i', $id);

if (! ($deleteTagStmt = $mysqli->prepare("DELETE FROM tag WHERE id=?"))) {
  echo 'Error while preparing delete tag statement : ' . $mysqli->error ;
  exit(1);
}

$deleteTagStmt->bind_param('i', $id);

if (! ($insertStmt = $mysqli->prepare("INSERT INTO position (id, latitude, longitude) VALUES (?, ?, ?)"))) {
  echo 'Error while preparing insert position statement : ' . $mysqli->error ;
  exit(1);
}

$insertStmt->bind_param('iii', $id, $latitude, $longitude);

if (! ($insertTagStmt = $mysqli->prepare("INSERT INTO tag (id, k, v) VALUES (?, ?, ?)"))) {
  echo 'Error while preparing insert tag statement : ' . $mysqli->error ;
  exit(1);
}

$insertTagStmt->bind_param('iss', $id, $k, $v);

function printDebug() {
  global $elementTypes, $count, $countDelete, $countModify, $countCreate;

  echo "== $count ==============================\n";
  foreach($elementTypes as $k => $v) {
    echo "$k : $v\n";
  }
  echo "++++++ Surveillance nodes : ++++++\n";
  echo "$countDelete deletions\n";
  echo "$countModify modifications\n";
  echo "$countCreate creations\n";
}

function printDebugCurNode() {
  global $curNodeAttrs, $curNodeTags, $mode;

  echo "=============\n";
  echo "$mode : " . $curNodeAttrs['id'] ." (". $curNodeAttrs['lat'] . " x " . $curNodeAttrs['lon'] . ") :  " . $curNodeAttrs['user'] . "\n";
  if (! empty($curNodeAttrs)) {
    echo "  => Attributs :\n";
    foreach($curNodeAttrs as $k => $v) {
      echo "   $k : $v\n";
    }
  } else {
     echo "  => Pas d'attributs\n";
  }
  if (! empty($curNodeTags)) {
    echo "  => Tags :\n";
    foreach($curNodeTags as $k => $v) {
      echo "   $k : $v\n";
    }
  } else {
     echo "  => Pas de tags\n";
  }
}

function startElement ($parser, $name, $attrs) {
  global $elementTypes, $count, $mode, $curNodeAttrs, $curNodeTags;
  $count++;

/*
  echo "---- $name -----\n";
  foreach($attrs as $k => $v) {
    echo "$k => $v\n";
  }
*/


  if (array_key_exists($name, $elementTypes)) {
    $elementTypes[$name] += 1;
  } else {
    $elementTypes[$name] = 1; 
  }

/*
  if ($count % 50000 == 0) {
    printDebug();
  }
*/

  if ($name == 'modify' || $name == 'delete' or $name == 'create') {
    $mode = $name;
  
  } else if ($name == 'node') {
    $curNodeAttrs = $attrs;
    $curNodeTags = array();

  } else if ($name == 'tag') {
    $curNodeTags[$attrs['k']] = $attrs['v'];
  }
}

function endElement ($parser, $name) {
  global $mode, $deleteStmt, $deleteTagStmt, $insertStmt, $insertTagStmt, $id, $latitude, $longitude, $lat, $lon, $k, $v, $curNodeAttrs, $curNodeTags, $mysqli, $countDelete, $countModify, $countCreate;

  if ($name == 'modify' || $name == 'delete' or $name == 'create') {
    $mode = '';

  } else if ($name == 'node') {
    if ($mode == 'delete') {
      $id = $curNodeAttrs['id'];
      if (! $deleteTagStmt->execute()) {
        echo "***** Erreur : Supression tags $id : ". $deleteStmt->error . "\n";
      }
      if (! $deleteStmt->execute()) {
        echo "***** Erreur : Supression $id : ". $deleteStmt->error . "\n";
      }
      if ($deleteStmt->affected_rows > 0) {
        $countDelete++;
        $mysqli->commit();
        printDebugCurNode();
      }     
 
    } else if ($mode == 'modify' || $mode == 'create') {
      if (! empty($curNodeTags) 
          && array_key_exists('man_made', $curNodeTags)
          && $curNodeTags['man_made'] == 'surveillance') {
      
        printDebugCurNode();

        $id = $curNodeAttrs['id'];

        if ($mode == 'modify') {
          $countModify++;
          if (! $deleteTagStmt->execute()) {
            echo "***** Erreur : Suppression tags $id pour modification : ". $deleteStmt->error . "\n";
          }      
          if (! $deleteStmt->execute()) {
            echo "***** Erreur : Suppression $id pour modification : ". $deleteStmt->error . "\n";
          }      
        } else {
          $countCreate++;
        }

        $latitude = (int) ($curNodeAttrs['lat'] * 10000000);
        $longitude = (int) ($curNodeAttrs['lon'] * 10000000);

        if (! $insertStmt->execute()) {
          echo "***** Erreur : insertion $id ($latitude x $longitude) : ". $insertStmt->error . "\n";
        }

        $k='lat';
        $v=$curNodeAttrs['lat'];
        if (! $insertTagStmt->execute()) {
          echo "***** Erreur : insertion latitude $v pour $id : ". $insertTagStmt->error . "\n";
        }

        $k='lon';
        $v=$curNodeAttrs['lon'];
        if (! $insertTagStmt->execute()) {
          echo "***** Erreur : insertion longitude $v pour $id : ". $insertTagStmt->error . "\n";
        }

        $k='userid';
        $v=$curNodeAttrs['user'];
        if (! $insertTagStmt->execute()) {
          echo "***** Erreur : insertion user $v pour $id : ". $insertTagStmt->error . "\n";
        }

        $k='version';
        $v=$curNodeAttrs['version'];
        if (! $insertTagStmt->execute()) {
          echo "***** Erreur : insertion user $v pour $id : ". $insertTagStmt->error . "\n";
        }

        $k='timestamp';
        $v=$curNodeAttrs['timestamp'];
        if (! $insertTagStmt->execute()) {
          echo "***** Erreur : insertion user $v pour $id : ". $insertTagStmt->error . "\n";
        }

        foreach($curNodeTags as $k => $v) {
          if (! $insertTagStmt->execute()) {
            echo "***** Erreur : insertion tag $k => $v pour $id : ". $insertTagStmt->error . "\n";
          }
        }
       
        $mysqli->commit();
      }
    }
    
    $curNodeAttrs = null;
    $curNodeTags = null;
  }
}

$file="change_file.osc";

$xml_parser = xml_parser_create();
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
xml_set_element_handler($xml_parser, "startElement", "endElement");

if (!($fp = fopen($file, "r"))) {
  die("could not open XML input");
}

while ($data = fread($fp, 4096)) {
  if (!xml_parse($xml_parser, $data, feof($fp))) {
    die(sprintf("XML error: %s at line %d",
                xml_error_string(xml_get_error_code($xml_parser)),
                xml_get_current_line_number($xml_parser)));
  }
}

printDebug();

?>
