# TasmoBackup
Backup the configs of all your Tasmota devices

# Features
* Add single devices
* Discover devices
* Backup single devices
* Backup all devices
* Remove devices
* Download individual backups
* No duplicates (based on IP)

# Requirements

* PHP
* Mysql / MariaDB

# Install

* Clone this repo
* Create a folder called data
* Create a folder called data/backups
* Chmod 777 data/backups
* Copy config.inc.php.example to data/config.inc.php (This will prevent it being overwitten on new pulls)
* Copy settings.inc.php to data/settings.inc.php(This will prevent it being overwitten on new pulls)
* Edit config.inc.php to reflect your MYSQL server, username and password
* Navigate to http://ipaddress:8259/createdb.php to create the database
* Navigate to http://ipaddress:8259

# Docker-compose

* Clone this repo
* Edit docker-compose.yml to define your MySQL information and Volume location.
* ```docker-compose up -d```
* Navigate to http://ipaddress:8259/createdb.php to create the database or import the tasmobackup.sql template in this repo.
* Navigate to http://ipaddress:8259
* Note : If you get your MySQL details wrong at this stage, you can change them by editing config.inc.php in your data folder.

# Scheduled Backups
* backupall.php exists to do literally that.. Schedule this with your chosen means (nodered, curl, scheduled tasks etc)

# Screenshots

![Alt text](https://i.imgur.com/2swMzG9.png)
![Alt text](https://i.imgur.com/27Pm7lH.png)
![Alt text](https://i.imgur.com/QReTLxp.png)
![Alt text](https://i.imgur.com/e2ruv2t.png)



# To-Do

* ~~Create install.php~~
* ~~Schedule for all backups~~
* ~~Auto Discover devices - In Progress~~
* ~~Prevent duplicates~~
* Delete backups when device removed (Make sure it is accurate!)
* ~~Make backup location customisable~~
* ~~Edit function to change name of devices~~
* Retention (Number of backups)
