# TasmoBackupV1
Backup the configs of all your Tasmota devices

# Latest Changes
* tasmota 9.0 status change
* recording mac addresses
* tasmota 8.3 devicename support
* add dark mode
* fix special chars in username/password
* use timezone for displayed times in docker/HA installs
* cron to run backup-all in docker for HA

# Features
* Add single devices
* Discover devices
* Backup single devices
* Backup all devices
* Remove devices
* Download individual backups
* No duplicates (based on IP)

# Install via Hass.io aka HomeAssistant Supervisor
Go into home assisant, then the supervisor
Click on the Add-On Store
paste in http://github.com/danmed/TasmoBackupV1 into the Add new repository
via url box, and click add
Scroll down near the bottom and locate TasmoBackup

More info at: https://www.home-assistant.io/hassio/installing_third_party_addons/

# Install via Docker-compose
```yaml
version: '2'
services:
    tasmobackup:
        ports:
            - '8259:80'
        volumes:
            - ./data:/var/www/html/data
        environment:
            # MYSQL env's are not needed if you are using sqlite
            - MYSQL_SERVER=IPADDRESS
            - MYSQL_USERNAME=USERNAME
            - MYSQL_PASSWORD=PASSWORD
            # change below to mysql if you don't want to use sqlite
            # you will need to have a mysql server (set above) with a blank database already created.
            - DBTYPE=sqlite
            # if using Mysql remove the data/ from the below line
            # if using Sqlite the data/ is required!
            - DBNAME=data/tasmobackup
        container_name: TasmoBackup
        image: 'danmed/tasmobackupv1'
```
# Docker Run

SQLITE: 
```
docker run -d -p 8259:80 -v ./data:/var/www/html/data -e DBTYPE=sqlite -e DBNAME=data/tasmobackup --name TasmoBackup danmed/tasmobackupv1
```
Note : pay attention to the difference's between the sqlite and mysql database names.

MYSQL:
```
docker run -d -p 8259:80 -v ./data:/var/www/html/data -e DBTYPE=mysql -e MYSQL_SERVER=192.168.2.10 -e MYSQL_USERNAME=root MYSQL_PASSWORD=password -e DBNAME=tasmobackup --name TasmoBackup danmed/tasmobackupv1
```

# Install via Raw PHP
```
git clone https://github.com/danmed/TasmoBackupV1
cd TasmoBackupV1
mkdir data
chown www-data data
cp config.inc.php.example data/config.inc.php
```

Edit data/config.inc.php if you wish to change to using mysql database
instead of sqlite.
Make sure the data directory is owned by the user php runs as, or it will
not be able to save your backups or create/update the sqlite file

Run the upgrade.php script to initialize your new database, or to upgrade
your existing one when changing versions.

# Scheduled Backups
* backupall.php exists to do literally that.. Schedule this with your chosen means (nodered, curl, scheduled tasks etc)

# Screenshots

![Alt text](https://i.imgur.com/2swMzG9.png)
![Alt text](https://i.imgur.com/27Pm7lH.png)
![Alt text](https://i.imgur.com/QReTLxp.png)
![Alt text](https://i.imgur.com/e2ruv2t.png)



# To-Do
* Background scanning
* Handle device changes ip address
* Parse backup configs

# Support
[![ko-fi](https://www.ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/E1E21J93T)
