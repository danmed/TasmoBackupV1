# IF YOU ARE COMING HERE FROM THE OLD VERSION - YOU WILL NEED TO START AGAIN DUE TO BREAKING CHANGES. ALL DEVICES WILL NEED TO BE ADDED AGAIN.

# TasmoBackupV1
Backup the configs of all your Tasmota devices

# Latest Changes
* Delete individual backups - 11.02.2020
* Delete backups when device deleted - 11.02.2020
* MQTT search for devices by topic

# Features
* Add single devices
* Discover devices
* Backup single devices
* Backup all devices
* Remove devices
* Download individual backups
* No duplicates (based on IP)

# Docker-compose
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

# Scheduled Backups
* backupall.php exists to do literally that.. Schedule this with your chosen means (nodered, curl, scheduled tasks etc)

# Screenshots

![Alt text](https://i.imgur.com/2swMzG9.png)
![Alt text](https://i.imgur.com/27Pm7lH.png)
![Alt text](https://i.imgur.com/QReTLxp.png)
![Alt text](https://i.imgur.com/e2ruv2t.png)



# To-Do

* Add confirm option before deleting device or individual backups
