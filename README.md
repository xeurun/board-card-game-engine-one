docker-compose run rest-php php /var/www/app/yii
docker-compose run rest-php php /var/www/app/init
docker-compose run rest-composer composer install -d /var/www/app
docker-compose up
docker-compose up -d