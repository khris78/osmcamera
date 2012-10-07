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


   $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
   if($mysqli->connect_errno) {
     $result='{"error":"Error while connecting to db : ' . $mysqli->error . '"}';
     echo $result;
     exit;
   }

   $sql="SELECT  k, count(*)
           FROM  tag
       GROUP BY  k
       ORDER BY  2 desc, k
          LIMIT  ?,?";
       
   if ($stmt = $mysqli->prepare($sql)) {
     $maxElt = $pageSize + 1;
     $stmt->bind_param("ii", $startPage, $maxElt);

     $stmt->execute();

     $stmt->bind_result($k, $count);

     $result='<!DOCTYPE html>
<html>
<head>  
  <meta charset="UTF-8"/>
</head>
<body>
<table>
<tr><th>Key</th><th>Count</th></tr>';

     $eltCount=0;
     while ($eltCount < $pageSize && $stmt->fetch()) {
       $eltCount++;

       $k=htmlentities($k);
       $result = $result . '<tr><td><a href="tag_detail.php?tag=' . $k . '">' . $k 
                         . '</a></td><td>' . $count . '</td></tr>';
     }

     $result  =$result . '</table><p>';

     if ($startPage > 0) {
       $prevPage = $startPage - $pageSize;
       if ($prevPage < 0) {
         $prevPage = 0;
       }
       $result = $result . '<a href="tags.php?start=' . $prevPage 
                         . '">&lt&lt Previous</a>&nbsp;&nbsp;';
     }

     if ($stmt->fetch()) {
       $result = $result . '<a href="tags.php?start=' 
                         . ($startPage + $pageSize) . '">Next &gt;&gt;</a>';
     }


     $result = $result . '</body></html>';
     
     $stmt->close();

   } else {
     $mysqli->close();
     $result='{"erreur":"Erreur de préparation de la requête : ' . $mysqli->error . '"}';
     echo $result;
     exit;
   }

   $mysqli->close();

   echo $result;
?>
