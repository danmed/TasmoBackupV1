# TasmoBackup
Backup the configs of all your Tasmota devices

# Features
* Backup single devices
* Backup all devices
* Remove devices
* Download individual backups

# Requirements

* PHP
* Mysql / MariaDB
* Writeable directory named "backups" in the same folder as the index.php 

# Install

* Clone this repo
* Edit config.inc.php to reflect your MYSQL server, username and password
* Navigate to install.php
* When you get the message that the database was created successfully, navigate to index.php

# Screenshots

https://imgur.com/a/yfHvw0i

# To-Do

* Create install.php - DONE
* Schedule for all backups
* Auto Discover devices
* Prevent duplicates
* Delete backups when device removed (Make sure it is accurate!)
* Make backup location customisable
* Edit function to change name of devices
