#!/bin/bash

# Number of days of retention
retain=7
# Where to store the backups
backup_location="backups/"

files="$backup_location/*"
for f in $files
do

  echo "Processing Existing Backups : $f"
  g=${f::-4}
  date_check=${g: -10}
  date_check=`date -d "$date_check" "+%s"`
  today=`date +%s`
  echo $date_check
  echo $today
  diff=$(($today-$date_check))
  days=$(($diff/(60*60*24)))
  echo $days
  if (($days > $retain))
  then
   echo "Cleaning up old Backup : $f"
   rm "$f"
  fi
done

while IFS="" read -r p || [ -n "$p" ]
do
ip=$(echo "$p" | cut -f1 -d$)
ip+="/dl"
name=$(echo "$p" | cut -f2 -d$)
name+="-"
name+=$(date '+%Y-%m-%d')
name+=".dmp"
curl $ip --output backups/$name --silent
done < ips.txt
