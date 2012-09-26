#!/bin/bash

REPLICATE_URL="http://download.openstreetmap.fr/replication/planet/minute"

if [ -e "/var/lock/update_camera" ]
then
  # Maybe an other update is running
  otherPid=$(cat "/var/lock/update_camera")
  count=$(ps $otherPid | grep -c `basename "$0"`)

  if [ $count -gt 0 ]
  then
    echo "$0 is running yet. Exiting." >&2
    exit 1
  fi
fi

# OK. We can update the database...
echo $$ >  "/var/lock/update_camera"

exeDir=$(cd `dirname "$0"`; pwd);
cd "$exeDir"

if [ -e "state.txt" ]
then
  rm "state.txt"
fi

# Read the last update timestamp

lastTimestamp=$(grep "^timestamp=" "lastState.txt" | cut -d'=' -f2-)
lastSeqNum=$(grep "^sequenceNumber=" "lastState.txt" | cut -d'=' -f2-)

wget -nv "$REPLICATE_URL/state.txt" 

newTimestamp=$(grep "^timestamp=" "state.txt" | cut -d'=' -f2-)
newSeqNum=$(grep "^sequenceNumber=" "state.txt" | cut -d'=' -f2-)

if [ $newSeqNum -eq $lastSeqNum ]
then 
  echo "Aucun nouveau fichier à traiter"
  exit 1
fi


curSeqNum=$lastSeqNum
while [ $curSeqNum -lt $newSeqNum ]
do
  curSeqNum=$(( $curSeqNum + 1 ))

  logFileName="logs/log.$curSeqNum"
  
  if [ -e "change_file.osc" ]
  then
    rm "change_file.osc"
  fi

  if [ -e "change_file.osc.gz" ]
  then
    rm "change_file.osc.gz"
  fi

  echo `date "+%d/%m/%Y %H:%M"`" - Début du traitement du numéro de séquence $curSeqNum" > "$logFileName"

  targetDirName=`echo "000000000$curSeqNum" | sed 's#.*\(...\)\(...\)\(...\)$#\1/\2/\3.osc.gz#'` 
  targetDirName="$REPLICATE_URL/$targetDirName"

  wget -nv "$targetDirName" -O change_file.osc.gz 2>&1 | tee -a $logFileName
  if [ $? -gt 0 ]
  then
    echo "Erreur lors de la récupération de $targetDirName" | tee -a $logFileName
    exit 1
  fi

  gunzip change_file.osc.gz 2>&1 | tee -a $logFileName
  if [ $? -gt 0 ]
  then
    echo "Erreur lors de la décompression de $targetDirName" | tee -a $logFileName
    exit 1
  fi
 
  php update_camera.php 2>&1 | tee -a $logFileName 

  targetDirName=`echo "$targetDirName" | sed 's/.osc.gz$/.state.txt/'`
  wget -nv "$targetDirName" -O lastState.txt 2>&1 | tee -a $logFileName

  echo `date "+%d/%m/%Y %H:%M"`" - Fin du traitement du numéro de séquence $curSeqNum" | tee -a "$logFileName"
done

rm "/var/lock/update_camera"

