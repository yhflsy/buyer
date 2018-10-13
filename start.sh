#! /bin/sh
apt-get install -y wget
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PROJECTNAME/app/config/cache.php -O /var/www/html/app/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PROJECTNAME/app/config/site.php -O /var/www/html/app/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PROJECTNAME/nginx/server.conf -O /etc/nginx/conf.d/server.conf

wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/clientrest/config/common.php -O /var/www/plugins/clientrest/config/common.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/clientrest/config/platform.php -O /var/www/plugins/clientrest/config/platform.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/clientrest/config/site.php -O /var/www/plugins/clientrest/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/clientrest/config/css.php -O /var/www/plugins/clientrest/config/css.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/common/init.php -O /var/www/plugins/common/init.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/common/config/redis.php -O /var/www/plugins/common/config/redis.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/common/config/report.php -O /var/www/plugins/common/config/report.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/buyer.shop/config/cache.php -O /var/www/plugins/config/buyer.shop/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/buyer.shop/config/site.php -O /var/www/plugins/config/buyer.shop/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/buyer.shopadmin/config/cache.php -O /var/www/plugins/config/buyer.shopadmin/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/buyer.shopadmin/config/site.php -O /var/www/plugins/config/buyer.shopadmin/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/buyer.shopweb/config/cache.php -O /var/www/plugins/config/buyer.shopweb/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/buyer.shopweb/config/site.php -O /var/www/plugins/config/buyer.shopweb/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/centuryship/config/cache.php -O /var/www/plugins/config/centuryship/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/centuryship/config/site.php -O /var/www/plugins/config/centuryship/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/centuryshipweb/config/cache.php -O /var/www/plugins/config/centuryshipweb/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/centuryshipweb/config/site.php -O /var/www/plugins/config/centuryshipweb/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/fleet/config/cache.php -O /var/www/plugins/config/fleet/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/fleet/config/site.php -O /var/www/plugins/config/fleet/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/ground/config/cache.php -O /var/www/plugins/config/ground/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/ground/config/site.php -O /var/www/plugins/config/ground/config/site.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/take/config/cache.php -O /var/www/plugins/config/take/config/cache.php
wget http://$DOMAIN_ENV.config.we2tu.com/php/$PLUGINNAME/config/take/config/site.php -O /var/www/plugins/config/take/config/site.php
service nginx start
php-fpm -R 
