#!/bin/sh
if [ ! -f /var/www/html/.htaccess ] || ! grep -q "RewriteEngine On" /var/www/html/.htaccess; then
  cat > /var/www/html/.htaccess << 'HTACCESS'
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
HTACCESS
  chown www-data:www-data /var/www/html/.htaccess
fi

chown -R www-data:www-data /var/www/html/wp-content/uploads
