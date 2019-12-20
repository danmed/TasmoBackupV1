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
* Edit config.inc.php to reflect your MYSQL server, username and password
* Navigate to http://ipaddress:8259/createdb.php to create the database
* Navigate to http://ipaddress:8259

# Docker-compose

* Clone this repo
* ```docker-compose up -d```
* edit config.inc.php in the data mount and give it your mysql user / pass and IP.
* Navigate to http://ipaddress:8259/createdb.php to create the database or import the tasmobackup.sql template in this repo.
* Navigate to http://ipaddress:8259

# Screenshots

![Alt text](https://i.imgur.com/dDvz5xA.png)
![Alt text](https://i.imgur.com/qM6drXz.png)
![Alt text](https://i.imgur.com/o79yMXB.png)



# To-Do

* ~~Create install.php~~
* Schedule for all backups
* Auto Discover devices - In Progress (Beta)
* ~~Prevent duplicates~~
* Delete backups when device removed (Make sure it is accurate!)
* Make backup location customisable
* ~~~~Edit function to change name of devices~~~~
* Retention (Number of backups)
