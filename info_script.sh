#!/bin/bash
echo "--- Apache VirtualHost Configuration ---"
cat /etc/apache2/sites-enabled/abdm-bridge-le-ssl.conf | grep -A 50 "VirtualHost"
echo ""
echo "--- Directory Listing: /var/www/html/abdm-bridge-gateway/public/ ---"
ls -la /var/www/html/abdm-bridge-gateway/public/
echo ""
echo "--- .htaccess Details ---"
ls -la /var/www/html/abdm-bridge-gateway/public/.htaccess
echo ""
echo "--- Apache Rewrite Module Check ---"
apache2ctl -M | grep rewrite
echo ""
echo "--- PHP Version ---"
php -v
