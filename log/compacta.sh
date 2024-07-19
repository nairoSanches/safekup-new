#!/bin/bash

data=`date +%d-%m-%Y`

cd /var/www/html/safekup/log

zip -r $data $data

chown -R www-data:www-data *
chmod -R 777 *
