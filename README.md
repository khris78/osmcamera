osmcamera
=========

This project contains the source code for the map initially displayed at http://osm-camera.tk. This site is now closed.

This code has been reused and improved by at least 2 people who operate the following sites:
* http://osmcamera.dihe.de/
* https://kamba4.crux.uberspace.de/


Installation
============

In this paragraph, you are supposed to have yet configured a LAMP server on which the application will be installed.

1. Setting up the database

* Enter the init_cameras/db directory.
* modify the password that will permit to modify the camera database in createDb.sql.
* execute the following command :  
`mysql -h localhost -u root --password=[mysql root passwd] < createDb.sql`

2. Initializing the cameras in the database

* You may just execute the rqt.sql script. It will initialize the database with the data from the first Odbl planet file (09/14/2012).  
`mysql camera -h localhost -u camera --password=[camera user passwd] < rqt.sql`
* As an alternative, you may download your own planet or an extract, and get the cameras from it : 
  - download the file or an extract and put it in the init_cameras directory. You may download extracts, like Monaco (which is easier to perform tests) at http://www.geofabrik.de/data/download.html. Or you may download a full planet file from the torrent found at ftp://ftp.spline.de/pub/openstreetmap/torrents/.
  - execute the extraitVideosurv.sh script (that script requires osmosis : see http://wiki.openstreetmap.org/wiki/Osmosis/Installation). It will create a surveillance.osm file and a rqt.sql file. This may be long.
  - then execute the following command :  
`mysql camera -h localhost -u camera --password=[camera user passwd] < rqt.sql`

3. Set up the automatic update of the cameras

* Enter the update_cameras repository.
* The automatic update is based on the sequenceNumber comparison between the current state.txt from the replicate server, and the locally stored lastState.txt. So if you downloaded a planet file, you should modify the sequenceNumber in the lastState.txt file accordingly.
* Rename config.php.example file, and edit it to enter the database password you configured for the database user "camera".  
* Create directory update_cameras/logs
* Add the update script to your crontab. Execute :  
  `crontab -e`  
  and enter the following line :  
  `* * * * * [path to your update_cameras directory]/update_camera.sh > /dev/null 2>&1`
* In one minute, you'll see that the update script creates log files in the update_cameras/logs directory.

4. Set up the application on the apache server.

* enter the www directory, and copy the config.php.example file to config.php. Edit it to set up the values you wish.
* get the Jacob Toye's IconLabel plugin for Leaflet. You can download Icon.Label.css and Icon.Label.js from https://github.com/jacobtoye/Leaflet.iconlabel/tree/master/src (Note : I don't distribute it, as I didn't find under which licence it is released). 
* If no other application is installed on your Apache server, you may simply copy the www directory content to your /var/www directory (and remove the pre-existing index.html from that directory). In any other case you may declare a virtual host. Here is the virtual host definition I use :  
`<VirtualHost *:80>`
`	ServerAdmin contact@osm-camera.tk`  
`        ServerName www.osm-camera.tk`  
`        ServerAlias osm-camera.tk *.osm-camera.tk`  
` `  
`	DocumentRoot /var/www/camera`  
`	<Directory />`  
`		Options FollowSymLinks`  
`		AllowOverride None`  
`	</Directory>`  
`	<Directory /var/www/camera>`  
`		Options Indexes FollowSymLinks MultiViews`  
`		AllowOverride None`  
`		Order allow,deny`  
`		allow from all`  
`	</Directory>`  
` `  
`	ErrorLog ${APACHE_LOG_DIR}/error.log`  
` `  
`	# Possible values include: debug, info, notice, warn, error, crit,`  
`	# alert, emerg.`  
`	LogLevel warn`  
` `  
`	CustomLog ${APACHE_LOG_DIR}/access.log combined`  
`</VirtualHost>`  


Credits
=======

* OpenStreetMap and contributors : all data displayed by osm-camera.tk are from OSM : the tiles are from Mapnik (license CC-by-sa), and the camera info is extracted directly from OSM (license Odbl). 
* Switch2osm : you may recognize some little parts of their code, as I initialised the leaflet stuff from this page : http://switch2osm.org/using-tiles/getting-started-with-leaflet/  
  License : CC-by-sa (so I'm bound to license the leafletembed.js file under that licence)
* Leaflet, by cloudmade : the map is based on the leaflet API (linked from the index.php. I don't care the licence as I don't redistribute it...)
* IconLabel plugin for Leaflet, by Jacob Toye : this plugin is used to display the clusters at low zoom levels. The style used by the map is from an example in that plugin sources.  
  License : ???  
  https://github.com/jacobtoye/Leaflet.iconlabel  
  I just saw that there now exists (since Sept 23) a Leaflet.Label plugin that should be better for what we are doing...
* Mobile_detect, by Serban Ghita and Victor Stanciu  
  Licence : MIT  
  http://code.google.com/p/php-mobile-detect  
* Markercluster plugin for leaflet, by Dave Leaver : by looking in their code, I discovered the Quick Hull algorithm. Thanks to him.  
  https://github.com/danzel/Leaflet.markercluster
* JOSM : for the tag presets, modified to add the surveillance extended tags.  
  License : GNU GPL v3

