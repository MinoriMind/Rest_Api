git pull origin master

composer install
php bin/console cache:clear
php bin/console cache:warmup