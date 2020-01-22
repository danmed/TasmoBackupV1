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
* PHP CURL - sudo apt-get install curl
* Mysql / MariaDB

# Install

* Clone this repo
* Create a folder called data
* Create a folder called data/backups
* Chmod 777 data/backups
* chmod 777 data
* Copy config.inc.php.example to data/config.inc.php (This will prevent it being overwitten on new pulls)
* Copy settings.inc.php to data/settings.inc.php(This will prevent it being overwitten on new pulls)
* chmod 777 data/settings.inc.php
* Edit config.inc.php to reflect your MYSQL server, username and password
* Navigate to http://ipaddress:8259/createdb.php to create the database
* Navigate to http://ipaddress:8259

# Docker-compose

MYSQL
* Clone this repo
* Edit docker-compose.yml to define your MySQL information and Volume location.
* If using MySQL you will need to have a blank database already present on your MYSQl server.
* ```docker-compose up -d```
* Navigate to http://ipaddress:8259
* Note : If you get your MySQL details wrong at this stage, you can change them by changing the env variables and restarting the container.

SQLITE
* Clone this repo
* Edit docker-compose.yml to change your data folder location.
* ```docker-compose up -d```
* Navigate to http://ipaddress:8259

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
