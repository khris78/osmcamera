<?php

   include "config.php";

   $startPage=0;
   $pageSize=30;

   if (array_key_exists('start', $_GET)) {

     $startPage = $_GET['start'];
     if (! is_numeric($startPage) || intval($startPage) < 0) {
       $result='{"error":"Unexpected start value : '
                         .htmlentities($startPage)
                         .'"}';
       echo $result;
       exit;
     }
   }

   if (! array_key_exists('tag', $_GET)) {
       $result='{"error":"tag parameter is mandatory"}';
       echo $result;
       exit;
   }

   $tagKey=$_GET['tag'];

   if (! array_key_exists('val', $_GET)) {
       $result='{"error":"val parameter is mandatory"}';
       echo $result;
       exit;
   }

   $tagVal=$_GET['val'];

   $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
   if($mysqli->connect_errno) {
     $result='{"error":"Error while connecting to db : ' . $mysqli->error . '"}';
     echo $result;
     exit;
   }

   $sql="SELECT  id
           FROM  tag
          WHERE  k=? AND v=?
       ORDER BY  id
          LIMIT  ?,?";

   $sqlOther="SELECT  k, v
                FROM  tag
               WHERE  id=?";

   $sqlLatLon="SELECT  latitude, longitude
                 FROM  position
                WHERE  id=?";

   $id=0;
   $lat=0;
   $lon=0;
   $k='';
   $v='';

   if (($stmt = $mysqli->prepare($sql))
       && ($stmtOther = $mysqli->prepare($sqlOther))
       && ($stmtLatLon = $mysqli->prepare($sqlLatLon))) {

     $maxElt = $pageSize + 1;
     $stmt->bind_param("ssii", $tagKey, $tagVal, $startPage, $maxElt);
     $stmtOther->bind_param("i", $id);
     $stmtLatLon->bind_param("i", $id);

     $stmt->bind_result($id);
     $stmtOther->bind_result($k, $v);
     $stmtLatLon->bind_result($lat, $lon);

     $stmt->execute();
     $stmt->store_result();

     $tagKey = htmlentities($tagKey);
     $tagVal = htmlentities($tagVal);

     $result='<!DOCTYPE html>
<html>
<head>  
  <meta charset="UTF-8"/>
</head>
<body>
<h1>Tag ' . $tagKey . ' = ' . $tagVal . '</h1>
<table>
<tr><th>id</th><th>Tag</th><th>Value</th></tr>';

     $eltCount=0;
     while ($eltCount < $pageSize && $stmt->fetch()) {
       $eltCount++;

       if (! $stmtLatLon->execute()) {
         $result = $result . '<tr><td col_span="3">stmtLatLon : ' . $mysqli->error . '</td></tr>';
       }
       $stmtLatLon->store_result();
       if (! $stmtOther->execute()) {
         $result = $result . '<tr><td col_span="3">stmtOther : ' . $mysqli->error . '</td></tr>';
       }
       if (! $stmtLatLon->fetch()) {
         $result = $result . '<tr><td col_span="3">fetch lat/lon : ' . $mysqli->error . '</td></tr>';
       }
       $result = $result . '<tr><td>'
                         . '<a href="index.php?zoom=18&lat='. (((double) $lat) / 10000000.0) 
                         . '&lon=' . (((double) $lon) /10000000.0) . '">' . $id . '</a></td>';

       $sepTr='';
       while($stmtOther->fetch()) {
         $result = $result . $sepTr . '<td>' . htmlentities($k) 
                                    . '</td><td>' . htmlentities($v) 
                                    . '</td></tr>';
         $sepTr='<tr><td/>';
       }
     }

     $result = $result . '</table><p>';

     if ($startPage > 0) {
       $prevPage = $startPage - $pageSize;
       if ($prevPage < 0) {
         $prevPage = 0;
       }
       $result = $result . '<a href="tag_value.php?tag=' . $tagKey . '&val=' . $tagVal
                         . '&start=' . $prevPage 
                         . '">&lt&lt Previous</a>&nbsp;&nbsp;';
     }

     if ($stmt->fetch()) {
       $result = $result . '<a href="tag_value.php?tag=' . $tagKey . '&val=' . $tagVal
                         . '&start=' . ($startPage + $pageSize) 
                         . '">Next &gt;&gt;</a>';
     }


     $result = $result . '</body></html>';
     
     $stmt->close();

   } else {
     $result='{"erreur":"Erreur de préparation de la requête : ' . $mysqli->error . '"}';
     echo $result;
     $mysqli->close();
     exit;
   }

   $mysqli->close();

   echo $result;
?>
