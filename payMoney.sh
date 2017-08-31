#!/bin/bash
while true ; 
do 
	php /data/www/tosee-api/artisan groupshoot:pay >> /data/www/tosee-api/storage/money_logs/pay.log  & sleep 15; 
done

