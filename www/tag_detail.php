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

   $mysqli = new mysqli(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWD, MYSQL_DB);
   if($mysqli->connect_errno) {
     $result='{"error":"Error while connecting to db : ' . $mysqli->error . '"}';
     echo $result;
     exit;
   }

   $sql="SELECT  v, count(*)
           FROM  tag
          WHERE  k=?
       GROUP BY  v
       ORDER BY  2 desc, v
          LIMIT  ?,?";
       
   if ($stmt = $mysqli->prepare($sql)) {
     $maxElt = $pageSize + 1;
     $stmt->bind_param("sii", $tagKey, $startPage, $maxElt);

     $stmt->execute();

     $stmt->bind_result($v, $count);

     $tagKey = htmlentities($tagKey);

     $result='<!DOCTYPE html>
<html>
<head>  
  <meta charset="UTF-8"/>
</head>
<body>
<h1>Tag ' . $tagKey . '</h1>
<table>
<tr><th>Value</th><th>Count</th></tr>';

     $eltCount=0;
     while ($eltCount < $pageSize && $stmt->fetch()) {
       $eltCount++;

       $v=htmlentities($v);
       $result = $result . '<tr><td><a href="tag_value.php?tag=' . $tagKey. '&val=' . $v . '">' . $v 
                         . '</a></td><td>' . $count . '</td></tr>';
     }

     $result  =$result . '</table><p>';

     if ($startPage > 0) {
       $prevPage = $startPage - $pageSize;
       if ($prevPage < 0) {
         $prevPage = 0;
       }
       $result = $result . '<a href="tag_detail.php?tag=' . $tagKey 
                         . '&start=' . $prevPage 
                         . '">&lt&lt Previous</a>&nbsp;&nbsp;';
     }

     if ($stmt->fetch()) {
       $result = $result . '<a href="tag_detail.php?tag=' . $tagKey 
                         . '&start=' . ($startPage + $pageSize) 
                         . '">Next &gt;&gt;</a>';
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
