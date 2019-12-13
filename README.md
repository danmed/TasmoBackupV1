:80# TasmoBackup
Backup the configs of all your Tasmota devices

# Features
* Backup single devices
* Backup all devices
* Remove devices
* Download individual backups
* No duplicates (based on IP)

# Requirements

* PHP
* Mysql / MariaDB
* Writeable directory named "backups" in the same folder as the index.php 

# Install

* Clone this repo
* Edit config.inc.php to reflect your MYSQL server, username and password
* Navigate to install.php
* When you get the message that the database was created successfully, navigate to index.php

# Docker

* Clone this repo
* edit config.inc.php to reflect your MySQL username / pass / ip
* ```docker build -t tasmobackup:latest .```
* ```docker run -d -p 8259:80 --name TasmoBackup tasmobackup:latest```
* Navigate to install.php to create the database, or create it yourself by importing the .sql file included in this repo.

# Screenshots

![Alt text](https://i.imgur.com/dDvz5xA.png)
![Alt text](https://i.imgur.com/qM6drXz.png)
![Alt text](https://i.imgur.com/o79yMXB.png)



# To-Do

* ~~Create install.php~~
* Schedule for all backups
* Auto Discover devices
* ~~Prevent duplicates~~
* Delete backups when device removed (Make sure it is accurate!)
* Make backup location customisable
* Edit function to change name of devices
* Retention (Number of backups)
