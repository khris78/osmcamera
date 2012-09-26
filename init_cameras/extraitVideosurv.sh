#!/bin/bash

DIR_OSMUPDATE=$(cd `dirname "$0"`; pwd)
XML_FILE=planet.osm.bz2
XML_RESULT_FILE=surveillance.osm
SQL_FILE=rqt.sql

cd $DIR_OSMUPDATE

echo `date "+%d.%m.%Y %H:%M:%S"`" - Lancement de l'analyse"
if [ "$1" != "-n" ]
then
  echo `date "+%d.%m.%Y %H:%M:%S"`" - Extraction des caméras"
  bzcat "$XML_FILE" | osmosis --rx file=/dev/stdin --tf accept-nodes man_made=surveillance --tf reject-relations --tf reject-ways --write-xml "$XML_RESULT_FILE"
fi

echo `date "+%d.%m.%Y %H:%M:%S"`" - Génération du script"
echo "DELETE FROM position; DELETE FROM tag; COMMIT;" > "$SQL_FILE"

IFS='
'

countPoints=0;
nextCommit=100;
for ligne in `cat "$XML_RESULT_FILE" | grep -E "<node|</node|<tag" | sed 's/^[ ]*//'`
do

  tag=`echo "$ligne" | cut -c1-5`
  if [ "$tag" = "<node" ]
  then
    nodeId=`echo "$ligne" | sed 's/.*node id="\([^"]*\)".*/\1/'`
    lat=`echo "$ligne" | sed 's/.*lat="\([^"]*\)".*/\1/'`
    lon=`echo "$ligne" | sed 's/.*lon="\([^"]*\)".*/\1/'`
    userid=`echo "$ligne" | sed -e 's/.*user="\([^"]*\)".*/\1/' -e "s/'/''/g"`
    version=`echo "$ligne" | sed 's/.*version="\([^"]*\)".*/\1/'`
    timestamp=`echo "$ligne" | sed 's/.*timestamp="\([^"]*\)".*/\1/'`

    echo "INSERT INTO position (id, latitude, longitude) VALUES ($nodeId, "`echo "$lat" '*' 10000000 | bc | cut -d'.' -f1`","`echo "$lon" '*' 10000000 | bc | cut -d'.' -f1`");" >> "$SQL_FILE"
    echo "INSERT INTO tag (id, k, v) VALUES ($nodeId, 'lat', '$lat');" >> "$SQL_FILE"
    echo "INSERT INTO tag (id, k, v) VALUES ($nodeId, 'lon', '$lon');" >> "$SQL_FILE"
    echo "INSERT INTO tag (id, k, v) VALUES ($nodeId, 'userid', '$userid');" >> "$SQL_FILE"
    echo "INSERT INTO tag (id, k, v) VALUES ($nodeId, 'version', '$version');" >> "$SQL_FILE"
    echo "INSERT INTO tag (id, k, v) VALUES ($nodeId, 'timestamp', '$timestamp');" >> "$SQL_FILE"


  elif [ "$tag" = "<tag " ]
  then
    key=`echo "$ligne" | sed -e 's/.*k="\([^"]*\)".*/\1/' -e "s/'/''/g"`
    val=`echo "$ligne" | sed -e 's/.*v="\([^"]*\)".*/\1/' -e "s/'/''/g"`
    
    echo "INSERT INTO tag (id, k, v) VALUES ($nodeId, '$key', '$val');" >> "$SQL_FILE"

  elif [ "$tag" = "</nod" ] 
  then
    countPoints=$(( $countPoints + 1 ))
    if [ $countPoints -eq $nextCommit ]
    then
      echo "COMMIT;" >> "$SQL_FILE"
      nextCommit=$(( $nextCommit + 100 ))
    fi
  fi
done

echo "COMMIT;" >> "$SQL_FILE"

echo `date "+%d.%m.%Y %H:%M:%S"`" - Fin de la mise à jour ($countPoints caméras)"
